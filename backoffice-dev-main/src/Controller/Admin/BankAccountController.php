<?php

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Enum\ActionRequest;
use App\Entity\Enum\BankAccountTransition;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Form\BankAccountReviewType;
use App\Form\QueryBankAccountType;
use App\Form\Type\ActionConfirmationType;
use App\Form\Type\ActionRequestType;
use App\Form\Type\BankAccountType as BankAccountFormType;
use App\Repository\BankAccountRepository;
use App\Service\BankAccountService;
use App\Service\BankAccountSyncService;
use App\Service\MangopayScaService;
use App\Service\MangopayWalletService;
use App\Service\NotificationService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/bank-accounts')]
#[IsGranted('ROLE_OPERATIONS')]
class BankAccountController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private BankAccountRepository $bankAccountRepository,
        private BankAccountService $bankAccountService,
        private BankAccountSyncService $bankAccountSyncService,
        private MangopayWalletService $mangopayWalletService,
        private NotificationService $notificationService,
    ) {}

    #[Route('', name: 'admin_bank_account_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Showing bank account registrations');
        $form = $this->createForm(QueryBankAccountType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            // $this->logger->debug('filters', $filters);
        }
        $results = $this->bankAccountRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/bank_accounts/index.html.twig', [
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route('/schema', name: 'admin_bank_account_schema', methods: ['GET', 'POST'])]
    public function schema(Request $request): Response
    {
        $form = $this
            ->createFormBuilder()
            ->add('country', CountryType::class, ['preferred_choices' => ['GB']])
            ->add('currency', CurrencyType::class, [
                'preferred_choices' => ['GBP'],
                'choice_label' => function (
                    $choice,
                    string $key,
                    mixed $value,
                ): string {
                    return $value;
                },
            ])
            ->add('payoutMethod', ChoiceType::class, [
                'choices' => ['auto', 'LocalBankTransfer', 'InternationalBankTransfer'],
                'choice_label' => function (
                    $choice,
                    string $key,
                    mixed $value,
                ): string {
                    return ucfirst($value);
                },
            ])
            ->getForm();
        ;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $schema = $this->bankAccountService->getSchemaForRecipient(
                    $form->getData()['country'],
                    $form->getData()['currency'],
                    $form->getData()['payoutMethod'],
                );
                $payoutMethods = $this->bankAccountService->getPayoutMethods(
                    $form->getData()['country'],
                    $form->getData()['currency'],
                );
            } catch (\Mangopay\Libraries\ResponseException $th) {
                $schema = $th->GetErrorDetails()->Errors;
                $loadError = true;
                $this->logger->error('Unable to retrieve schema', [
                    $form->getData(),
                    $th->GetErrorDetails()->Errors,
                ]);
                $this->addFlash(
                    'error',
                    'Unable to retrieve schema: '
                    . json_encode($form->getData())
                    . '. See error details in schema section.',
                );
            } catch (\Throwable $th) {
                $schema = [];
                $loadError = true;
                $this->logger->error('Unable to retrieve schema for '
                . $form->getData()['country'], [$th->getMessage()]);
                $this->addFlash(
                    'error',
                    'Unable to retrieve schema for country code: '
                        . $form->getData()['country']
                        . $th->getMessage(),
                );
            }
        }

        return $this->render('admin/pages/bank_accounts/schema.html.twig', [
            'form' => $form,
            'schema' => $schema ?? [],
            'country' => $form->getData()['country'] ?? null,
            'currency' => $form->getData()['currency'] ?? null,
            'payoutMethod' => $form->getData()['payoutMethod'] ?? null,
            'schemaLoadError' => $loadError ?? false,
            'payoutMethods' => $payoutMethods ?? [],
        ]);
    }

    #[Route('/create', name: 'admin_bank_account_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $bankAccount = new BankAccount();
        $form = $this->createForm(BankAccountFormType::class, $bankAccount);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (BankAccountType::GB == $bankAccount->getAccountType()) {
                $bankAccount->setCountry(BankAccountType::GB->value);
                $this->addFlash(
                    'info',
                    'GB account types only allow GB as the country',
                );
            }
            $bankAccount->setFingerprint($this->bankAccountService->getFingerprint(
                $bankAccount,
            ));
            if (!$this->bankAccountService->isNotDuplicated($bankAccount)) {
                $this->addFlash('error', "Failed to create bank account registration. A similar one already exists with fingerprint {$bankAccount->getFingerprint()}.
                    You will need to reject or disable these duplicates before you can re-add them.");
                return $this->render('admin/pages/bank_accounts/create.html.twig', [
                    'form' => $form,
                ]);
            }
            if (
                empty($bankAccount->getDisplayName()) && $bankAccount->getFingerprint()
            ) {
                $bankAccount->setDisplayName($this->bankAccountService->createDisplayName(
                    $bankAccount,
                ));
            }
            $this->doctrine->getManager()->persist($bankAccount);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Successfully created bank account registration',
            );
            return $this->redirectToRoute('admin_bank_account_manage', ['id' => $bankAccount->getId()]);
        }
        return $this->render('admin/pages/bank_accounts/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_bank_account_manage', methods: ['GET', 'POST'])]
    public function manage(Request $request, BankAccount $bankAccount): Response
    {
        $form = $this->createForm(BankAccountReviewType::class, ['notifyUser' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ClickableInterface $passButton */
            $passButton = $form->get('pass');
            if ($passButton->isClicked()) {
                return $this->redirectToRoute('admin_bank_account_transition_edit', [
                    'id' => $bankAccount->getId(),
                    'transition' => BankAccountTransition::Approve->value,
                    'notify' => $form->getData()['notifyUser'],
                ]);
            }
            /** @var ClickableInterface $failButton */
            $failButton = $form->get('fail');
            if ($failButton->isClicked()) {
                return $this->redirectToRoute('admin_bank_account_transition_confirm', [
                    'id' => $bankAccount->getId(),
                    'transition' => BankAccountTransition::Reject->value,
                ]);
            }
        }
        return $this->render('admin/pages/bank_accounts/manage.html.twig', [
            'bankAccount' => $bankAccount,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_bank_account_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        BankAccount $bankAccount,
        WorkflowInterface $bankAccountStateMachine,
    ): Response {
        $form = $this->createForm(BankAccountFormType::class, $bankAccount);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (BankAccountType::GB == $bankAccount->getAccountType()) {
                $bankAccount->setCountry(BankAccountType::GB->value);
                $this->addFlash(
                    'info',
                    'GB account types only allow GB as the country',
                );
            }
            $newFingerprint = $this->bankAccountService->getFingerprint($bankAccount);
            // If the fingerprint has changed, also force update the display name
            if ($newFingerprint && $bankAccount->getFingerprint() != $newFingerprint) {
                $bankAccount->setDisplayName($this->bankAccountService->createDisplayName(
                    $bankAccount,
                ));
                // Downgrade any validation or approval if account details are changed
                if ($bankAccountStateMachine->can(
                    $bankAccount,
                    BankAccountTransition::Unapprove->value,
                )) {
                    $bankAccountStateMachine->apply(
                        $bankAccount,
                        BankAccountTransition::Unapprove->value,
                    );
                }
            }
            $bankAccount->setFingerprint($newFingerprint);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Successfully updated bank account registration',
            );
            return $this->redirectToRoute('admin_bank_account_manage', ['id' => $bankAccount->getId()]);
        }
        return $this->render('admin/pages/bank_accounts/edit.html.twig', [
            'form' => $form,
            'bankAccount' => $bankAccount,
        ]);
    }

    #[Route('/{id}/validate', name: 'admin_bank_account_validate', methods: ['GET'])]
    public function validate(BankAccount $bankAccount): Response
    {
        $validationErrors =
            $this->bankAccountService->validateWithMangopay($bankAccount);
        if ($validationErrors) {
            $this->addFlash(
                'warning',
                'Mangopay validation failed. Issues found: '
                    . json_encode($validationErrors),
            );
        } else {
            $this->addFlash('success', 'Mangopay validation passed. No issues found.');
        }
        $this->doctrine->getManager()->flush();
        return $this->redirectToRoute('admin_bank_account_manage', [
            'id' => $bankAccount->getId(),
        ]);
    }

    #[Route(
        '/{id}/clear-custom',
        name: 'admin_bank_account_clear_custom',
        methods: ['GET'],
    )]
    public function clearCustomHolder(
        Request $request,
        BankAccount $bankAccount,
    ): Response {
        $bankAccount->setAccountHolderName(null);
        $bankAccount->setAccountHolderAddress(null);
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', 'Successfully updated bank account registration');
        return $this->redirectToRoute('admin_bank_account_manage', ['id' => $bankAccount->getId()]);
    }

    #[Route(
        '/{id}/status-sync',
        name: 'admin_bank_account_status_sync',
        methods: ['GET'],
    )]
    public function mangopayStatusSync(BankAccount $bankAccount): Response
    {
        try {
            $bankAccount =
                $this->bankAccountService->syncStatusWithMangopay($bankAccount);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                "Sync with mangopay completed. Status is now {$bankAccount->getStatus()->value}.",
            );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error('Unable to get recipient from Mangopay.', [
                'errors' => $th->GetErrorDetails()->Errors,
            ]);
            $this->addFlash(
                'error',
                'Unable to get recipient from Mangopay. '
                    . json_encode($th->GetErrorDetails()->Errors),
            );
        } catch (\Throwable $th) {
            $this->logger->error('Unable to sync bank account registration status with Mangopay recipient', ['errors' =>
                $th->getMessage()]);
            $this->addFlash(
                'error',
                'Unable to sync bank account registration status with Mangopay recipient. '
                    . $th->getMessage(),
            );
        }
        return $this->redirectToRoute('admin_bank_account_manage', [
            'id' => $bankAccount->getId(),
        ]);
    }

    #[Route(
        '/{id}/request-action',
        name: 'admin_bank_account_request_action',
        methods: ['GET', 'POST'],
    )]
    public function requestAction(Request $request, BankAccount $bankAccount): Response
    {
        $actionRequests = $request->query->get('clear', false)
            ? []
            : $this->bankAccountService->actionRequestsAsEnum(
                $bankAccount->getMetadata()['actionRequests'] ?? [],
            );
        $form = $this->createForm(
            ActionRequestType::class,
            ['actionRequests' => $actionRequests],
            ['choices' => ActionRequest::bankAccount()],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $metadata = $bankAccount->getMetadata();
            if (empty($form->getData()['actionRequests'])) {
                unset($metadata['actionRequests']);
            } else {
                $metadata['actionRequests'] = $form->getData()['actionRequests'];
            }
            $bankAccount->setMetadata($metadata);
            $this->doctrine->getManager()->flush();
            if ($form->getData()['notifyUser']) {
                // Note that if no actions are requested, this does nothing
                $this->bankAccountService->sendActionRequestNotification($bankAccount);
            }
            $this->addFlash('success', 'Successfully updated action requests');
            return $this->redirectToRoute('admin_bank_account_manage', ['id' => $bankAccount->getId()]);
        }
        return $this->render('admin/pages/bank_accounts/action_requests.html.twig', [
            'form' => $form,
            'bankAccount' => $bankAccount,
        ]);
    }

    #[Route(
        '/{userId}/sync-new/{providerId}',
        name: 'admin_bank_account_create_sync',
        methods: ['GET'],
    )]
    public function createWithSync(
        #[MapEntity(id: 'userId')] User $user,
        string $providerId,
    ): Response {
        try {
            $existingRecord = $this->bankAccountRepository->findOneBy([
                'user' => $user->getId(),
                'providerId' => $providerId,
            ]);
            if ($existingRecord !== null) {
                $this->logger->warning(
                    'Mangopay bank account already associated with a registration for the user',
                );
                $this->addFlash(
                    'warning',
                    'No need to sync. Mangopay bank account already associated with a registration for the user.',
                );
                return $this->redirectToRoute('admin_bank_account_manage', [
                    'id' => $existingRecord->getId(),
                ]);
            }
            $mangopayRecord =
                $this->mangopayWalletService->retrieveRecipient($providerId);
            $bankAccount = $this->bankAccountSyncService->mapActiveMangopayRecipient(
                $user,
                $mangopayRecord,
            );
            $this->doctrine->getManager()->persist($bankAccount);
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'New bank account registration via sync with Mangopay completed.',
            );
            return $this->redirectToRoute('admin_bank_account_manage', [
                'id' => $bankAccount->getId(),
            ]);
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error('Unable to create new bank account registration via sync with Mangopay.', [
                'errors' => $th->GetErrorDetails()->Errors,
            ]);
            $this->addFlash(
                'error',
                'Unable to create new bank account registration via sync with Mangopay. '
                    . json_encode($th->GetErrorDetails()->Errors),
            );
        } catch (\Throwable $th) {
            $this->logger->error('Unable to create new bank account registration via sync with Mangopay', ['errors' =>
                $th->getMessage()]);
            $this->addFlash(
                'error',
                'Unable to create new bank account registration via sync with Mangopay. '
                    . $th->getMessage(),
            );
        }
        return $this->redirectToRoute('admin_user_dashboard_bank_accounts', [
            'id' => $user->getId(),
        ]);
    }

    #[Route(
        '/{userId}/sync-multi',
        name: 'admin_bank_account_create_sync_multi',
        methods: ['GET'],
    )]
    public function createWithSyncMulti(
        #[MapEntity(id: 'userId')] User $user,
        Request $request,
    ): Response {
        try {
            $mangopayRecipients = array_filter(
                $this->mangopayWalletService
                    ->mangopayApi
                    ->Recipients
                    ->GetUserRecipients($user->getMangoPayUserId()),
                fn(\Mangopay\Recipient $r): bool => $r->Status == 'ACTIVE',
            );
            // Batch size is capped at 10 to avoid too many Mangopay API requests
            $toSync = $this->bankAccountSyncService->filterUnsyncedRecipients(
                $this->bankAccountSyncService->getUserSyncedRecipientIds($user),
                $mangopayRecipients,
                min((int) $request->query->get('batchSize', 5), 10),
            );
            $syncedIds = [];
            foreach ($toSync as $providerId) {
                $mangopayRecord =
                    $this->mangopayWalletService->retrieveRecipient($providerId);
                $bankAccount = $this->bankAccountSyncService->mapActiveMangopayRecipient(
                    $user,
                    $mangopayRecord,
                );
                $this->doctrine->getManager()->persist($bankAccount);
                $syncedIds[] = $providerId;
            }
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Successfully synced ' . count($syncedIds) . ' recipients: '
                    . json_encode($syncedIds),
            );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error('Unable to sync with Mangopay.', [
                'errors' => $th->GetErrorDetails()->Errors,
            ]);
            $this->addFlash(
                'error',
                'Unable to sync with Mangopay. '
                    . json_encode($th->GetErrorDetails()->Errors),
            );
        } catch (\Throwable $th) {
            $this->logger->error('Unable to sync with Mangopay', ['errors' =>
                $th->getMessage()]);
            $this->addFlash(
                'error',
                'Unable to sync with Mangopay. ' . $th->getMessage(),
            );
        }
        return $this->redirectToRoute('admin_user_dashboard_bank_accounts', [
            'id' => $user->getId(),
        ]);
    }

    #[Route(
        '/mangopay/recipients/{recipientId}',
        name: 'admin_bank_account_mangopay_recipient_view',
        methods: ['GET'],
    )]
    public function viewMangopayRecipient(string $recipientId): Response
    {
        try {
            $recipient = $this->mangopayWalletService->retrieveRecipient($recipientId);

            // $this->logger->debug("Recipient", [$recipient]);
        } catch (\Throwable $th) {
            $this->logger->warning('Unable to retrieve Mangopay recipient', [$th->getMessage()]);
            throw $this->createNotFoundException(
                'Unable to retrieve Mangopay recipient. ' . $th->getMessage(),
            );
        }
        return $this->render('admin/pages/bank_accounts/mangopay/recipient_view.html.twig', [
            'recipient' => $recipient,
        ]);
    }

    /**
     * https://symfony.com/blog/new-in-symfony-6-1-improved-routing-requirements-and-utf-8-parameters
     * Replace requirements with BackedEnums
     */
    #[Route(
        '/{id}/{transition}',
        name: 'admin_bank_account_transition_edit',
        methods: ['GET'],
        requirements: [
            'transition' => 'approve|unapprove|reopen',
        ],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function editorialTransition(
        Request $request,
        BankAccount $bankAccount,
        string $transition,
    ): Response {
        try {
            $this->bankAccountService->transitionBankAccount($bankAccount, $transition);
            if (BankAccountTransition::Approve->value === $transition) {
                $this->logger->debug('Approve bank account registration', [$bankAccount->getId()]);
                $validationErrors =
                    $this->bankAccountService->validateWithMangopay($bankAccount);
                if ($validationErrors) {
                    $this->addFlash(
                        'warning',
                        'Mangopay validation failed. Issues found: '
                            . json_encode($validationErrors),
                    );
                    throw new \RuntimeException(
                        'You cannot approve an invalid bank account registration.',
                    );
                } else {
                    $this->addFlash(
                        'success',
                        'Mangopay validation passed. No issues found.',
                    );
                }
                $bankAccount->setApprovedBy($this->getUser());
                if ($request->query->get('notify', false)) {
                    $this->bankAccountService->sendApprovalNotification($bankAccount);
                }
            }
            if (BankAccountTransition::Unapprove->value === $transition) {
                $this->logger->debug('Request Changes for bank account registration', [$bankAccount->getId()]);
                $bankAccount->setApprovedBy(null);
            }
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Bank account registration successfully updated to '
                    . $bankAccount->getStatusAsString(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Could not apply state transition. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to transition bank account registration', [$e->getMessage()]);
        }
        return $this->redirectToRoute(
            'admin_bank_account_manage',
            ['id' => $bankAccount->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/{transition}',
        name: 'admin_bank_account_transition_confirm',
        methods: ['GET', 'POST'],
        requirements: ['transition' => 'reject|disable'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function confirmationTransition(
        Request $request,
        BankAccount $bankAccount,
        string $transition,
    ): Response {
        $options = [
            'reasonPlaceholder' => 'e.g. Incorrect bank details',
            'reasonHelpText' => 'Provide a reason for closing this bank account. This will be added to the description and included in the user notification if you opt-in to sending one.',
            'additionalAction' => [
                'name' => 'notifyUser',
                'label' => 'Send email notification to user',
            ],
        ];
        $form = $this->createForm(ActionConfirmationType::class, null, $options);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $reason = $form->getData()['reason'] ?? '';
            if ($reason) {
                $bankAccount->setDescription(
                    "[$reason] " . $bankAccount->getDescription(),
                );
            }
            try {
                $this->logger->debug(
                    "Bank Account {$transition}",
                    [$bankAccount->getId()],
                );
                if (BankAccountTransition::Reject->value === $transition) {
                    $this->bankAccountService->transitionBankAccount(
                        $bankAccount,
                        $transition,
                    );
                    $bankAccount->setAccountNumber(null);
                    $bankAccount->setBankIdentifierCode(null);
                }
                if (BankAccountTransition::Disable->value === $transition) {
                    $this->bankAccountService->disableBankAccount($bankAccount);
                }
                if ($form->getData()['notifyUser']) {
                    $this->bankAccountService->sendClosureNotification(
                        $bankAccount,
                        BankAccountTransition::tryFrom($transition),
                        $reason,
                    );
                }
                $bankAccount->setApprovedBy(null);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Bank account registration successfully updated to '
                        . $bankAccount->getStatusAsString(),
                );
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error retrieving or updating Mangopay bank account', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error retrieving or updating Mangopay bank account '
                        . $e->getMessage()
                        . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Could not apply state transition. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to transition bank account registration', [$e->getMessage()]);
            }
            if (in_array(
                $request->query->get('redirectRoute'),
                MonthEndController::REDIRECT_ROUTES,
            )) {
                $redirectToRoute = $request->query->get('redirectRoute');
            }
            return $this->redirectToRoute(
                'admin_bank_account_manage',
                ['id' => $bankAccount->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->render('admin/pages/bank_accounts/transition_confirm.html.twig', [
            'bankAccount' => $bankAccount,
            'form' => $form,
            'transition' => $transition,
        ]);
    }

    #[Route('/{id}/enable', name: 'admin_bank_account_enable', methods: ['GET'])]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function enable(
        BankAccount $bankAccount,
        MangopayScaService $mangopayScaService,
    ): Response {
        $this->logger->debug('Register bank account', [$bankAccount->getId()]);
        try {
            $recipient =
                $this->bankAccountService->createMangopayRecipient($bankAccount);
            // $this->logger->debug("Recipient", [$recipient]);
            $this->doctrine->getManager()->flush();

            $returnUrl = $this->generateUrl(
                route: 'admin_bank_account_enable_sca_callback',
                parameters: ['id' => $bankAccount->getId()],
                referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
            );
            return $this->redirect($mangopayScaService->getScaSessionUrl(
                $recipient,
                $returnUrl,
            ));
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error creating Mangopay recipient', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error creating Mangopay recipient ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Unable to enable bank account. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to enable bank account. ', [$e->getMessage()]);
        }

        return $this->redirectToRoute(
            'admin_bank_account_manage',
            ['id' => $bankAccount->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }

    #[Route(
        '/{id}/enable/sca-callback',
        name: 'admin_bank_account_enable_sca_callback',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_FINANCIAL_OPS')]
    public function enableScaCallback(
        BankAccount $bankAccount,
        MangopayScaService $mangopayScaService,
    ): Response {
        $this->logger->debug('Activate bank account', [$bankAccount->getId()]);
        try {
            $success = $mangopayScaService->isRecipientActivated($bankAccount->getProviderId());
            if ($success) {
                $this->bankAccountService->processActivationOutcome(
                    $bankAccount,
                    $success,
                );
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    "Recipient SCA verification successfully processed. Bank account registration in {$bankAccount->getStatus()->value} state.",
                );
            } else {
                $this->addFlash(
                    'warning',
                    "Recipient SCA verification failed to process. Bank account registration in {$bankAccount->getStatus()->value} state.",
                );
            }
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Unable to process SCA outcome. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to process SCA outcome. ', [$e->getMessage()]);
        }

        return $this->redirectToRoute(
            'admin_bank_account_manage',
            ['id' => $bankAccount->getId()],
            Response::HTTP_SEE_OTHER,
        );
    }
}
