<?php

namespace AppBundle\Controller;

use AppBundle\Entity\DirectDebit;
use AppBundle\Form\DirectDebitActiveType;
use AppBundle\Form\DirectDebitAmountType;
use AppBundle\Form\SetupDirectDebitType;
use AppBundle\Util\BusinessDays;
use Carbon\Carbon;
use ClientBundle\Service\CrowdTekService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DirectDebitController extends AbstractController
{
    private $params = [];
    private $user = null;

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private CrowdTekService $crowdTekService,
        private UserService $userService,
    ) {
        $this->containerInitialized();
    }

    public function containerInitialized()
    {
        $this->logger->info("==================IN containerInitialized=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        // if (!$authenticated) {
        //     $verifyEmail = $this->_request->query->get('verify_email', 0);
        //     header('Location: ' . $this->generateUrl('login', array('verify_email' => $verifyEmail)));
        //     exit;
        // }
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }

        // get userInfo from session set during login - only call getUserInfo if you need a refresh after making changes
        // Note: profileAction has sync - so any redirects to /my-profile/profile after an update or change will trigger sync
        // $this->user = $this->userService->getUserInfo();
        $this->user = $this->requestStack->getSession()->get('userInfo');
    }

    /**
     * @Route("/my-profile/direct-debit/setup", name="direct_debit_setup")
     */
    public function directDebitDebitSetup(Request $request): Response
    {
        $this->logger->info("==================IN directDebitdSetup=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();
        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $directDebitCheck = $this->userService->getDirectDebitCheck();

        if ($directDebitCheck['outcome'] === 'success') {
            $mandateStatus = $directDebitCheck['data']['DirectDebit']['status'];
            if ($mandateStatus === "CREATED") {
                return $this->redirect($directDebitCheck['data']['DirectDebit']['mandate_confirm']);
            }
            return $this->redirect($this->generateUrl('direct_debit_summary'));
        }

        $user = $this->userService->getUserInfo();

        $firstName = $user['given_name'];
        $lastName = $user['family_name'];
        $address1 = $user['address']['building'];
        $address2 = $user['address']['street_address'];
        $address3 = $user['address']['address_locality'];
        $city = $user['address']['city'];
        $postcode = $user['address']['postal_code'];
        $country = $user['address']['country'];

        $time = new \DateTime();

        $currentDay = $time->format('d');

        if ($currentDay <= 15) {
            $firstDate = \DateTime::createFromFormat('d', '20');
            $secondDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P1M'));
            $thirdDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P2M'));
        } else {
            $firstDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P1M'));
            $secondDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P2M'));
            $thirdDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P3M'));
        }

        $nextPaymentDate = $firstDate->format('d-m-Y');
        $secondPaymentDate = $secondDate->format('d-m-Y');
        $thirdPaymentDate = $thirdDate->format('d-m-Y');

        // Load bank accounts
        $bankAccountsRes = $this->userService->getBankAccounts($this->user['id']);

        $active_bank_accounts = [];

        if (isset($bankAccountsRes['data']['bank_accounts'])) {
            foreach ($bankAccountsRes['data']['bank_accounts'] as $bankAccount) {
                if ($bankAccount['type'] === 'GB' && $bankAccount['active'] == true) {
                    $bankAccountStr = $bankAccount['type'];
                    $bankAccountStr .= " " . $bankAccount['account_number'];
                    $bankAccountStr .= " " . join('-', str_split($bankAccount['sort_code'], 2));
                    $active_bank_accounts[$bankAccountStr] = $bankAccount['id'];
                }
            }
        }

        $this->logger->info(
            "=======bank accounts Passed======== " .
                json_encode($active_bank_accounts)
        );

        $directDebit = new DirectDebit();

        $form = $this->createForm(SetupDirectDebitType::class, $directDebit, [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address1' => $address1,
            'address2' => $address2,
            'address3' => $address3,
            'city' => $city,
            'postcode' => $postcode,
            'country' => $country,
            'bankAccounts' => $active_bank_accounts
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = [
                'addressCheck' => $directDebit->getAddressCheck(),
                'bankAccountType' => "uk bank account",
                'accountId' => $directDebit->getBankAccountId(),
                //'bankAccountType' => $directDebit->getBankAccountType(),
                //'accountIban' => $directDebit->getAccountIban(),
                //'sortBic' => $directDebit->getSortBic(),
                'amount' => $directDebit->getAmount(),
                'confirmUrl' => $this->generateUrl('direct_debit_summary', [], UrlGeneratorInterface::ABSOLUTE_URL),
                'nextPaymentDate' => $nextPaymentDate,
            ];

            $this->logger->info("Form data valid and passed to createDirectDebitBankAccount ======== " . json_encode($data));

            $result = $this->userService->setupDirectDebitBankAccountAndMandate($data);

            if ($result['outcome'] === 'success') {
                return $this->redirect($result["data"]["confirm_mandate_url"]);
            } else {
                $this->addFlash('error', 'Please re-enter your Bank Account and/or Sort Code');
            }
        }


        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = $form->getErrors(true, false);
            $this->logger->info("ERROR ======= field is: " . $errors);
        }

        return $this->render('@AppBundle/Profile/setup_direct_debit.html.twig', [
            'form' => $form->createView(),
            'paymentDate' => $nextPaymentDate,
            'secondPaymentDate' => $secondPaymentDate,
            'thirdPaymentDate' => $thirdPaymentDate,
            'currentDay' => $currentDay,
        ]);
    }

    /**
     * @Route("/my-profile/direct-debit/details", name="direct_debit_details")
     */
    public function directDebitDetails(Request $request): Response
    {
        $this->logger->info("==================IN directDebitDetails=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();
        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $directDebitCheck = $this->userService->getDirectDebitCheck();

        if ($directDebitCheck['outcome'] === 'success') {
            $mandateStatus = $directDebitCheck['data']['DirectDebit']['status'];
            if ($mandateStatus === "CREATED") {
                return $this->redirect($directDebitCheck['data']['DirectDebit']['mandate_confirm']);
            };

            return $this->render('@AppBundle/Profile/direct_debit_billing.html.twig', [
                'accountIban' => $directDebitCheck['data']['DirectDebit']['account_iban'],
                'sortBic' => $directDebitCheck['data']['DirectDebit']['sort_bic'],
            ]);
        } elseif (isset($directDebitCheck['data']['api_error_code'])) {
            if ($directDebitCheck['data']['api_error_code'] === 124 || $directDebitCheck['data']['api_error_code'] === 920) {
                return $this->redirectToRoute('direct_debit_setup');
            }
        } else {
            return $this->redirectToRoute('profile');
        }
    }

    /**
     * @Route("/my-profile/direct-debit", name="direct_debit_summary")
     */
    public function directDebitSummary(Request $request): Response
    {
        $this->logger->info("==================IN directDebitSetttings=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();
        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $directDebitCheck = $this->userService->getDirectDebitCheck();

        if ($directDebitCheck['outcome'] === 'success') {
            $mandateStatus = $directDebitCheck['data']['DirectDebit']['status'];
            if ($mandateStatus === "CREATED") {
                return $this->redirect($directDebitCheck['data']['DirectDebit']['mandate_confirm']);
            };

            $activeForm = $this->createForm(DirectDebitActiveType::class, null, [
                'active' => $directDebitCheck['data']['DirectDebit']['active'],
            ]);
            $amountForm = $this->createForm(DirectDebitAmountType::class, null, [
                'amount' => $directDebitCheck['data']['DirectDebit']['amount'],
            ]);

            $activeForm->handleRequest($request);
            $amountForm->handleRequest($request);

            if ($activeForm->isSubmitted() && $activeForm->isValid()) {
                $updateStatus = $this->userService->updateDirectDebitStatus($activeForm->getData());

                if ($updateStatus['outcome'] === 'success') {
                    return new Response(json_encode(['status' => 'success']));
                }
            }

            if ($amountForm->isSubmitted() && $amountForm->isValid()) {
                $updateAmount = $this->userService->updateDirectDebitAmount($amountForm->getData());

                $this->logger->info("======== Outcome: " . $updateAmount['outcome']);

                if ($updateAmount['outcome'] === 'success') {
                    $this->addFlash('success', 'Direct Debit amount has been updated');
                }
            }

            $time = new \DateTime();
            $dateCreated = new \DateTime($directDebitCheck['data']['DirectDebit']['date_created']['date']);
            $currentDay = $time->format('d');
            $currentMonth = $time->format('m');

            if ($currentDay <= 15) {
                $nextPaymentDate = \DateTime::createFromFormat('d', '20');
            } else {
                $dayCreated = $dateCreated->format('d');
                $monthCreated = $dateCreated->format('m');
                if ($monthCreated === $currentMonth && $dayCreated > 15) {
                    $nextPaymentDate = \DateTime::createFromFormat('d', '20')->add(new \DateInterval('P1M'));
                } elseif ($monthCreated < $currentMonth) {
                    $nextPaymentDate = \DateTime::createFromFormat('d', '20');
                }
            }

            $dayDirectDebitCreated = $dateCreated->format('d-m-Y');
            $nextPayment = $nextPaymentDate->format('d-m-Y');

            return $this->render('@AppBundle/Profile/direct_debit_summary.html.twig', [
                'form' => $activeForm->createView(),
                'amountForm' => $amountForm->createView(),
                'nextPaymentDate' => $nextPayment,
                'dateCreated' => $dayDirectDebitCreated,
                'mandateUrl' => $directDebitCheck['data']['DirectDebit']['mandate_url'],
                'mandateStatus' => $directDebitCheck['data']['DirectDebit']['status'],
                'directDebitStatus' => $directDebitCheck['data']['DirectDebit']['active'],
                'currentDay' => $currentDay
            ]);
        } elseif (isset($directDebitCheck['data']['api_error_code'])) {
            if ($directDebitCheck['data']['api_error_code'] === 124 || $directDebitCheck['data']['api_error_code'] === 920) {
                return $this->render('@AppBundle/Profile/direct_debit_dash.html.twig', []);
            }
        } else {
            return $this->redirectToRoute('profile');
        }
    }

    /**
     * @Route("/my-profile/direct-debit/cancel", name="direct_debit_cancel")
     */
    public function directDebitCancel(Request $request): Response
    {
        $this->logger->info("==================IN directDebitCancel=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();
        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $directDebitCheck = $this->userService->getDirectDebitCheck();

        if ($directDebitCheck['outcome'] === 'success') {
            $mandateStatus = $directDebitCheck['data']['DirectDebit']['status'];
            if ($mandateStatus === "CREATED") {
                return $this->redirect($directDebitCheck['data']['DirectDebit']['mandate_confirm']);
            }

            $warningMessage = false;

            if (!empty($directDebitCheck['data']['DirectDebit']['lastSettlementDate']['date'])) {
                $currentDate = new Carbon();
                $numOfDays = BusinessDays::numOfBusniessDays(strtok($directDebitCheck['data']['DirectDebit']['lastSettlementDate']['date'], ' '), $currentDate->toDateString());
                if ((0 <= $numOfDays) && ($numOfDays <= 6)) {
                    $warningMessage = true;
                }
            }

            $form = $this->createFormBuilder()
                ->add('cancel', SubmitType::class, ['label' => 'Cancel Direct Debit'])
                ->getForm();

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $directDebitCancel = $this->userService->cancelDirectDebit();

                if ($directDebitCancel['outcome'] === 'success') {
                    return $this->render('@AppBundle/Profile/direct_debit_cancel_confirm.html.twig', [
                        'warningMessage' => $warningMessage
                    ]);
                }
            }

            return $this->render('@AppBundle/Profile/direct_debit_cancel.html.twig', [
                'form' => $form->createView(),
                'warningMessage' => $warningMessage
            ]);
        } elseif (isset($directDebitCheck['data']['api_error_code'])) {
            if ($directDebitCheck['data']['api_error_code'] === 124 || $directDebitCheck['data']['api_error_code'] === 920) {
                return $this->redirectToRoute('direct_debit_setup');
            }
        } else {
            return $this->redirectToRoute('profile');
        }
    }
}
