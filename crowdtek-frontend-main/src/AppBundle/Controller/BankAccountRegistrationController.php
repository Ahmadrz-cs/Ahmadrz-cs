<?php

namespace AppBundle\Controller;

use AppBundle\Entity\BankAccount;
use AppBundle\Entity\Enum\BankAccountFormatType;
use AppBundle\Entity\Enum\BankAccountStatus;
use AppBundle\Form\BankAccountRegistrationType;
use AppBundle\Form\UserAddressUpdateType;
use ClientBundle\Service\BankAccountService;
use ClientBundle\Service\DocumentService;
use ClientBundle\Service\InvestmentServiceV2;
use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;

#[Route(path: '/my-profile/bank-accounts')]
class BankAccountRegistrationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private SluggerInterface $slugger,
        private BankAccountService $bankAccountService,
        private DocumentService $documentService,
        private InvestmentServiceV2 $investmentService,
        private ScaService $scaService,
        private UserService $userService,
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
    }

    #[Route(path: '', name: 'bank_account_registrations_index', methods: ['GET'])]
    public function index(Request $request, DocumentService $documentService): Response
    {
        $this->logger->info("IN bank account registration index");

        $this->userService->refreshUserInfo();
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        if (array_key_exists('documents', $userInfo)) {
            $mostRecentProofOfAddress = $documentService->findMostRecentDocument(
                $userInfo['documents'],
                'proof_of_address'
            );
        }
        try {
            $linkingRestrictions = $this->bankAccountService->checkLinkingRestrictions();
            $lastSync = $this->bankAccountService->getLastSync();
            if ($lastSync === null) {
                $this->logger->debug("Syncing legacy mangopay bank accounts");
                $this->bankAccountService->syncMangopayLegacyBankAccounts();
            } else {
                $this->logger->debug("Already synced legacy mangopay bank accounts");
            }
            $linkedAccounts = $this->bankAccountService->listBankAccounts();
        } catch (\Exception $e) {
            $this->logger->debug("User cannot add bank account yet: " . $e->getMessage());
        }
        // return $this->redirectToRoute('profile');
        return $this->render('@AppBundle/Profile/bank_accounts/index.html.twig', [
            'linkedAccounts' => $linkedAccounts ?? [],
            'linkingRestrictions' => $linkingRestrictions,
            'mostRecentProofOfAddress' => $mostRecentProofOfAddress ?? [],
        ]);
    }

    #[Route(path: '/new', name: 'bank_account_registrations_new', methods: ['GET'])]
    public function registerNewTypePicker(): Response
    {
        $this->logger->info("IN new bank account registration type picker");
        if ($this->bankAccountService->checkLinkingRestrictions()) {
            $this->addFlash("warning", "You cannot link new bank accounts while your profile has pending actions");
            return $this->redirectToRoute("bank_account_registrations_index");
        }
        try {
            $linkedAccounts = $this->bankAccountService->listBankAccounts();
            if (count($linkedAccounts) >= 3) {
                $this->addFlash("warning", "You have already linked 3 or more bank accounts and cannot add any more");
                return $this->redirectToRoute("bank_account_registrations_index");
            }
        } catch (\Exception $e) {
            $this->logger->error("Issuing retrieving linked accounts: " . $e->getMessage());
        }
        return $this->render('@AppBundle/Profile/bank_accounts/new.html.twig');
    }

    #[Route(path: '/new/{type}', name: 'bank_account_registrations_new_type', methods: ['GET', 'POST'])]
    public function registerNewForType(Request $request, BankAccountFormatType $type): Response
    {
        $this->logger->info("IN new bank account registration for type {$type->value}");
        if ($this->bankAccountService->checkLinkingRestrictions()) {
            $this->addFlash("warning", "You cannot link new bank accounts while your profile has pending actions");
            return $this->redirectToRoute("bank_account_registrations_index");
        }
        try {
            $linkedAccounts = $this->bankAccountService->listBankAccounts();
            if (count($linkedAccounts) >= 3) {
                $this->addFlash("warning", "You have already linked 3 or more bank accounts and cannot add any more");
                return $this->redirectToRoute("bank_account_registrations_index");
            }
        } catch (\Exception $e) {
            $this->logger->error("Issuing retrieving linked accounts: " . $e->getMessage());
        }
        $bankAccount = new BankAccount();
        $form = $this->createForm(
            type: BankAccountRegistrationType::class,
            data: $bankAccount,
            options: ['accountType' => $type]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $bankAccount->country = match ($type) {
                    BankAccountFormatType::GB => 'GB',
                    default => substr($bankAccount->accountNumber, 0, 2), // derive from IBAN
                };

                /** @var UploadedFile $bankStatement */
                $bankStatement = $form->get('bankStatement')->getData();
                $originalFilename = pathinfo($bankStatement->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $fileType = $bankStatement->guessExtension();
                $fileName = $safeFilename . '_' . time() . '.' . $fileType;
                $byteArray = file_get_contents($bankStatement);
                $fileData = [
                    'file_name' => $fileName,
                    'file_type' => $bankStatement->getMimeType(),
                    'document_content' => base64_encode($byteArray),
                    'tag' => 'bank_statement'
                ];
                $this->documentService->create($fileData);

                // A notification will be sent on successful creation
                // So creation should go after document upload
                $this->bankAccountService->addNewBankAccount($bankAccount);

                $this->addFlash("success", "Successfully submitted new bank account registration");
                $this->logger->info("Successfully submitted new bank account registration");
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->addFlash("error", "Unable to submit new bank account registration. Check the bank account has not already been linked and try again. Please contact us if issues persist.");
            }
            return $this->redirectToRoute(
                'bank_account_registrations_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }
        // return $this->redirectToRoute('profile');
        return $this->render("@AppBundle/Profile/bank_accounts/new_form.html.twig", [
            'form' => $form,
            'accountType' => $type->value,
        ]);
    }

    #[Route(path: '/update-address', name: 'bank_account_registrations_edit_address', methods: ['GET', 'POST'])]
    public function updateAddress(Request $request, SluggerInterface $slugger, DocumentService $documentService): Response
    {
        $this->logger->info("IN bank account registration update address");
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        if (array_key_exists('documents', $userInfo)) {
            $mostRecentProofOfAddress = $documentService->findMostRecentDocument(
                $userInfo['documents'],
                'proof_of_address'
            );
        }
        $form = $this->createForm(type: UserAddressUpdateType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile $bankStatement */
                $bankStatement = $form->get('proofOfAddress')->getData();
                $originalFilename = pathinfo($bankStatement->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $fileType = $bankStatement->guessExtension();
                $fileName = $safeFilename . '_' . time() . '.' . $fileType;
                $byteArray = file_get_contents($bankStatement);
                $fileData = [
                    'file_name' => $fileName,
                    'file_type' => $bankStatement->getMimeType(),
                    'document_content' => base64_encode($byteArray),
                    'tag' => 'proof_of_address'
                ];
                $this->documentService->create($fileData);

                // Attempt to clear any actionRequests for proof_of_address
                $cleared = $this->bankAccountService->clearProofOfAddressActionRequests();
                $this->logger->debug("Cleared proof_of_address actionRequests for bank account registrations", $cleared);

                $this->addFlash("success", "Successfully submitted new proof of address");
                $this->logger->info("Successfully submitted new proof of address");
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->addFlash("error", "Unable to submit new proof of address. Please contact us if issues persist.");
            }
            return $this->redirectToRoute(
                'bank_account_registrations_index',
                [],
                Response::HTTP_SEE_OTHER
            );
        }
        return $this->render('@AppBundle/Profile/bank_accounts/update_address.html.twig', [
            'form' => $form,
            'mostRecentProofOfAddress' => $mostRecentProofOfAddress ?? [],
        ]);
    }

    #[Route(path: '/{id}', name: 'bank_account_registrations_manage_single', methods: ['GET', 'POST'])]
    public function manageSingle(Request $request, string $id): Response
    {
        $this->logger->info("IN bank account registration manage single for ID {$id}");
        if (Uuid::isValid($id) || is_numeric($id)) {
            try {
                $linkedAccount = $this->bankAccountService->retrieveLinkedAccount($id);
            } catch (\Exception $e) {
                $this->logger->error("Issuing retrieving linked account: " . $e->getMessage());
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
        $form = $this->createFormBuilder()
            ->add('confirm', CheckboxType::class, [
                'label' => 'I confirm I want to unlink this bank account',
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug("Deactivating linked bank account ID {$id}");
                $this->bankAccountService->unlinkBankAccount($linkedAccount);
                $this->addFlash("success", "Successfully unlinked bank account.");
            } catch (\Throwable $th) {
                $this->logger->error("Error when unlinking bank account", [$th->getMessage()]);
                $this->addFlash("error", "Could not unlink bank account. Please try again or contact us if issue persists.");
            }
            return $this->redirectToRoute('bank_account_registrations_index');
        }

        return $this->render('@AppBundle/Profile/bank_accounts/view.html.twig', [
            'linkedAccount' => $linkedAccount,
            'form' => $form,
        ]);
    }

    #[Route(path: '/{id}/activate', name: 'bank_account_registrations_activate', methods: ['GET'])]
    public function activate(string $id): Response
    {
        $this->logger->info("IN bank account registration activate for ID {$id}");
        if (Uuid::isValid($id) || is_numeric($id)) {
            try {
                $linkedAccount = $this->bankAccountService->retrieveLinkedAccount($id);
            } catch (\Exception $e) {
                $this->logger->error("Issuing retrieving linked account: " . $e->getMessage());
            }
        } else {
            throw new NotFoundHttpException();
        }

        if ($linkedAccount->status !== BankAccountStatus::Approved) {
            $this->logger->notice("Cannot activate bank account that has not been approved: " . $e->getMessage());
            $this->addFlash('warning', 'Link bank accounts cannot be activated until they have been approved.');
            return $this->redirectToRoute('bank_account_registrations_index');
        }

        try {
            $activation = $this->bankAccountService->activateBankAccount($linkedAccount);
        } catch (\Exception $e) {
            $this->logger->error("Issuing activating bank account: " . $e->getMessage());
            $this->addFlash('error', 'Unable to start SCA verification session for bank account activation.');
            return $this->redirectToRoute('bank_account_registrations_index');
        }

        if (!empty($activation->pendingUserAction)) {
            $returnUrl = $this->router->generate(
                name: 'bank_account_registrations_activate_callback',
                parameters: ['id' => $linkedAccount->uuid],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL
            );
            $queryParams = http_build_query([
                'returnUrl' => $returnUrl
            ]);
            $scaSessionUrl = $activation->pendingUserAction['redirectUrl'] . "&{$queryParams}";
            if (
                str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['sandbox'])
                || str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['prod'])
            ) {
                return $this->redirect($scaSessionUrl);
            } else {
                $this->addFlash('error', 'Unable to start SCA verification session for bank account activation.');
            }
        }
        return $this->redirectToRoute('bank_account_registrations_index');
    }

    #[Route(path: '/{id}/activate-callback', name: 'bank_account_registrations_activate_callback', methods: ['GET'])]
    public function activateCallback(
        string $id,
        #[MapQueryParameter]
        ?string $controlStatus = null,
    ): Response {
        $this->logger->info("IN bank account registration activate for ID {$id}");
        if (Uuid::isValid($id) || is_numeric($id)) {
            try {
                $linkedAccount = $this->bankAccountService->retrieveLinkedAccount($id);
            } catch (\Exception $e) {
                $this->logger->error("Issuing retrieving linked account: " . $e->getMessage());
            }
        } else {
            throw new NotFoundHttpException();
        }
        $scaOutcome = $this->scaService->isScaSuccess($controlStatus);
        try {
            $this->bankAccountService->processScaResult($linkedAccount, $scaOutcome);
        } catch (\Exception $e) {
            $this->logger->error("Issuing processing SCA outcome for bank account activation: " . $e->getMessage());
        }
        if ($scaOutcome) {
            $this->addFlash("success", "Successfully activated linked bank account. You can now use it for withdrawals.");
        }

        return $this->redirectToRoute('bank_account_registrations_index');
    }
}
