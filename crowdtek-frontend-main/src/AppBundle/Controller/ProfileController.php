<?php

namespace AppBundle\Controller;

use AppBundle\AppBundle;
use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\UserCategorisation;
use AppBundle\Form\BankAccountWithdrawalType;
use AppBundle\Form\CategoryHnwType;
use AppBundle\Form\CategoryRestrictedType;
use AppBundle\Form\CategorySophisticatedType;
use AppBundle\Form\ChangePasswordType;
use AppBundle\Form\ContactPreferencesType;
use AppBundle\Form\UserCategorisationType;
use AppBundle\Form\UserType;
use AppBundle\Util\Util;
use ClientBundle\Service\BankAccountService;
use ClientBundle\Service\CrowdTekService;
use ClientBundle\Service\DocumentService;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PublicService;
use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use ClientBundle\Service\VerificationService;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ProfileController extends AbstractController
{
    private $user;
    private array $params = [];

    public function __construct(
        private LoggerInterface $logger,
        private ApiClient $client,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private UrlGeneratorInterface $router,
        private FormFactoryInterface $formFactory,
        private CrowdTekService $crowdTekService,
        private DocumentService $documentService,
        private PublicService $publicService,
        private UserService $userService,
        private string $network,
        private string $adminEmail,
        private string $tempDir,
    ) {
        $this->containerInitialized();
    }

    //Common checks
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
     * @Route("/my-profile", name="my_profile")
     */
    public function myProfileAction(): Response
    {
        $this->logger->info("==================IN myProfileAction=====================");

        return $this->redirectToRoute('profile');
    }

    /**
     * @Route("/my-profile/dashboard", name="profile")
     */
    public function profileDashboardAction(
        Request $request,
        OnboardingService $onboardingService,
        VerificationService $verificationService,
        ScaService $scaService,
    ): Response {
        $this->logger->info("==================IN profileAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        // sync userInfo in session
        $userInfo = $this->userService->getUserInfo();
        $this->requestStack->getSession()->set('userInfo', $userInfo);

        if ($userInfo["mangopay_user_id"] && $userInfo["mangopay_wallet_id"] && $userInfo["sca_status"] == "active") {
            // Check if sca requirement changed when attempting to retrieve the transactions
            $scaRequired = $this->requestStack->getSession()->get('walletScaRequired');
        }

        return $this->render('@AppBundle/Profile/dashboard.html.twig', [
            'userInfo' => $userInfo,
            'needsCheckup' => $onboardingService->needsCheckup(
                $this->requestStack->getSession()->get('userInfo'),
            ),
            'canScaEnroll' => $scaService->canScaEnroll(
                $this->requestStack->getSession()->get('userInfo'),
            ),
            'needsVerification' => $verificationService->needsIdentityVerification(),
            'pageinfo' => "Dashboard",
            'scaRequired' => $scaRequired ?? null,
        ]);
    }

    /**
     * @Route("/my-profile/feedback", name="feedback")
     */
    public function profileFeedbackAction(Request $request): Response
    {
        $this->logger->info("==================IN profileFeedback=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $this->params['userInfo'] = $this->user;

        $this->params['user_info'] = $this->userService->getUserInfo(); // retrieve latest version of user
        $this->params['pageinfo'] = "Feedback";
        $this->requestStack->getSession()->set('userInfo', $this->params['user_info']); // sync userInfo in session
        return $this->render('@AppBundle/Profile/feedback.html.twig', $this->params);
    }

    /**
     * @Route("/my-profile/profile", name="profile_edit")
     */
    public function profileAction(Request $request): Response
    {
        $this->logger->info("==================IN profileAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $user = $this->requestStack->getSession()->get('userInfo'); // get user data from current session

        $this->params['user_info'] = $this->userService->getUserInfo(); // retrieve latest version of user
        $this->params['pageinfo'] = 'General Information';
        $this->requestStack->getSession()->set('userInfo', $this->params['user_info']); // sync userInfo in session
        return $this->render('@AppBundle/Profile/user_profile.html.twig', $this->params);
    }

    /**
     * @Route("/my-profile/password-security", name="password_security")
     */
    public function passwordSecurityAction(Request $request): Response
    {
        $this->logger->info("==================IN passwordSecurityAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        //$form = $this->createForm(ProfileType::class);
        $formChangePassword = $this->createForm(ChangePasswordType::class);
        $this->params['userInfo'] = $this->userService->getUserInfo();
        //$this->params['form'] = $form->createView();
        $this->params['pageinfo'] = 'Security';
        $this->params['formChangePassword'] = $formChangePassword->createView();
        return $this->render('@AppBundle/Profile/password_security.html.twig', $this->params);
    }


    /**
     * @Route("/my-profile/apply-top-yielder", name="apply-top-yielder")
     */
    public function applyTopYielderAction(Request $request): Response
    {
        $this->logger->info("==================IN applyTopYielderAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        $maxCharacters = 800;

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $proofOfFunds = [];
        if (isset($this->user['file'])) {
            foreach ($this->user['file'] as $file) {
                if (isset($file['proof_of_funds'])) {
                    $proofOfFunds = $file['proof_of_funds'];
                }
            }
        }
        $hasWallet = $this->requestStack->getSession()->has('wallet_id');
        if (!$this->user['has_been_approved'] || !$this->user['registration_complete'] || !$hasWallet) {
            return $this->redirectToRoute('homepage');
        }
        // $form = $this->createForm(new UserType($this->user));
        $form = $this->createForm(UserType::class, null, ['user' => $this->user]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $userFormData = $form->getData();
                $userData = Util::arrayFilterRecursive($userFormData);


                if (isset($userData['POIFile1'])) {
                    $data = [
                        'info' => ['words_of_your_own' => isset($userData['info']['words_of_your_own']) ? $userData['info']['words_of_your_own'] : ''],
                    ];

                    // CROWDTEK CHANGE.
                    /** @var UploadedFile $file */
                    $files = [
                        $form['POIFile1']->getData(),
                        $form['POIFile2']->getData(),
                        $form['POIFile3']->getData(),
                    ];

                    foreach ($files as $file) {
                        if (isset($file)) {
                            // CROWDTEK LOGGING CHANGE.
                            $this->logger->debug('FILE NAME:' . $file->getClientOriginalName());
                            $this->logger->debug('FILE NAME:' . $file->getFilename());
                            $this->logger->debug('FILE EXT:' . $file->guessExtension());

                            // CROWDTEK LOGGING CHANGE.
                            $this->logger->debug('User had loaded up a proof of funds');

                            //Convert document to byte array
                            $byteArray = file_get_contents($file);

                            // CROWDTEK LOGGING CHANGE.
                            $this->logger->debug('preparing to build a document request');

                            $fileData = [
                                'file_name' => $file->getClientOriginalName(),
                                'file_type' => $file->getMimeType(),
                                'document_content' => base64_encode($byteArray),
                                'tag' => 'proof_of_funds',

                            ];

                            $data['documents'] = [$fileData];
                            $response = $this->userService->update($this->network, $data);
                        }
                    }

                    if (!empty($response['outcome']) && $response['outcome'] == 'success') {
                        $this->addFlash('info', 'Thank you for your application, we will be in touch with you soon with the results.');

                        // Send notification email to admin users
                        $templateData = [];
                        $templateData['message_subject'] = 'Top Yielders Application';
                        $messageBody = 'Hi Admin,<br /><br />';
                        $messageBody .= $userData['info']['words_of_your_own'];
                        $messageBody .= '<br /><br />';
                        $messageBody .= 'Thanks & Regards, <br />';
                        $messageBody .= $this->user['family_name'] . ' ' . $this->user['given_name'];
                        $messageBody .= '<br />';
                        $messageBody .= $this->user['email'];

                        $templateData['message_body'] = $messageBody;
                        $templateData['recipient_email'] = $this->adminEmail;
                        $templateData['recipient_name'] = 'Admin Yielders';
                        $templateData['copy_to_admins'] = 1;
                        $this->userService->sendCustomClientEmail($templateData);
                    } else {
                        $this->addFlash('errors', "An issue has occured, please try again or if the problem persists contact us at team@yielders.co.uk");
                    }
                    return $this->redirect($this->generateUrl('apply-top-yielder'));
                }
            } else {
                $this->addFlash('info', 'Your Top Yielder application letter cannot be empty or over 800 characters');
            }
        }

        $this->params['userInfo'] = $this->user;
        $this->params['form'] = $form->createView();
        $this->params['proofOfFunds'] = $proofOfFunds;
        $this->params['pageinfo'] = 'Top Yielders';
        $this->params['maxCharacters'] = $maxCharacters;

        return $this->render('@AppBundle/Profile/apply_top_yielder.html.twig', $this->params);
    }

    /**
     * @Route("/my-profile/transactions", name="transactions_list")
     */
    public function transactionsListAction(Request $request): Response
    {
        $this->logger->info("==================IN myWalletAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirectToRoute('Onboarding');
        }

        $userRes = $this->userService->getUserInfo();
        $this->requestStack->getSession()->set('userInfo', $userRes);

        // Need a Mangopay user and wallet id to be able to pull any transactions
        if (!($userRes["mangopay_user_id"] && $userRes["mangopay_wallet_id"] && $userRes["sca_status"] == "active")) {
            $this->logger->debug("User missing Mangopay wallet or not enrolled with SCA");
            $this->addFlash(
                "info",
                "You must have a Mangopay wallet and be enrolled with Strong Customer Authentication (SCA) to view your wallet.",
            );
            return $this->redirectToRoute('profile');
        }

        // Refresh wallet balance and check wallet sca verification requirement
        $this->userService->setBalance();
        $scaRequired = $this->requestStack->getSession()->get('walletScaRequired');

        // Transaction and Pagination related
        $queryOptions = $request->query->all();
        if ((array_key_exists('page', $queryOptions)) && ($queryOptions['page'] > 0)) {
            // Specifically protect against special case page=0 which is a "getAll" mode
            // Which is intended for API testing purposes only
            $page = $queryOptions['page'];
        } else {
            $page = 1;
        }

        $transactions = $this->userService->getTransactionsSca([
            'page' => $page,
            'orderby' => 'creation',
            'sort' => 'desc',
        ]);

        // Check if sca requirement changed when attempting to retrieve the transactions
        $scaRequired = $this->requestStack->getSession()->get('walletScaRequired');

        return $this->render('@AppBundle/Profile/transaction_list.html.twig', [
            'page' => $page,
            'page_size' => 10,
            'transactions' => $this->convertTagsToDict($transactions ?? []),
            'wallet' => ['id' => $this->requestStack->getSession()->get('wallet_id')],
            'pageinfo' => 'Transaction History',
            'scaRequired' => $scaRequired,
        ]);
    }

    /**
     * @Route("/my-profile/wallet/sca-verification", name="wallet_access_sca_verification")
     */
    public function walletAccessScaSession(Request $request): Response
    {
        $userRes = $this->userService->getUserInfo();
        $this->requestStack->getSession()->set('userInfo', $userRes);

        $redirectToRoute = match ($request->query->get('from', null)) {
            'transaction' => 'transactions_list',
            default => 'profile',
        };

        // Need a Mangopay user and wallet id and be enrolled with SCA to do verification
        if (!($userRes["mangopay_user_id"] && $userRes["mangopay_wallet_id"] && $userRes["sca_status"] == "active")) {
            $this->logger->debug("User missing Mangopay wallet or not enrolled with SCA");
            $this->addFlash(
                "info",
                "You must have a Mangopay wallet and be enrolled with Strong Customer Authentication (SCA) to view your wallet.",
            );
            return $this->redirectToRoute($redirectToRoute);
        }

        try {
            $response = $this->client->mangopayWallet()->retrieveWallet(['query' => ['sca' => true]]);
            $responseBody = $this->client->getContent($response);
            $this->logger->debug("Response", $responseBody);

            if (array_key_exists('data', $responseBody)) {
                if (401 == $response->getStatusCode()) {
                    if (
                        isset($responseBody['data']['user_message'])
                        && str_contains($responseBody['data']['user_message'], "SCA required")
                    ) {
                        $returnUrl = $this->router->generate(
                            name: $redirectToRoute,
                            referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
                        );
                        $queryParams = http_build_query(['returnUrl' => $returnUrl]);
                        $scaSessionUrl = $responseBody['data']['redirect_url'] . "&{$queryParams}";
                        if (
                            str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['sandbox'])
                            || str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['prod'])
                        ) {
                            return $this->redirect($scaSessionUrl);
                        } else {
                            // Invalid SCA session url so cannot perform SCA verification
                            $this->logger->error("Invalid SCA session url", [$scaSessionUrl]);
                            $this->addFlash("error", "Unable to start SCA verification session. Please try again or contact us if issue persists.");
                        }
                    }
                }

                if (200 == $response->getStatusCode()) {
                    $this->logger->debug("Already SCA verified");
                    $this->userService->setBalance();
                    $this->requestStack->getSession()->set('walletScaRequired', false);
                    $this->addFlash("success", "SCA verification already completed");
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Unable to retrieve SCA session for wallet access", [$e->getMessage()]);
            $this->addFlash("error", "Unable to start SCA verification session. Please try again or contact us if issue persists.");
        }
        return $this->redirectToRoute($redirectToRoute);
    }

    /**
     * @Route("/my-profile/categorisation", name="profile_categorisation")
     */
    public function categorisation(Request $request, OnboardingService $onboardingService): Response
    {
        $this->logger->info("IN checkup categorisation");
        $userCategorisation = new UserCategorisation();
        $obp = $onboardingService->getOnboardingProfileFromSession();
        $userCategorisation->category = $obp->category ?? UserCategory::Restricted;
        if ($userCategorisation->category == UserCategory::None) {
            $userCategorisation->category = UserCategory::Restricted;
        }
        $form = $this->createForm(UserCategorisationType::class, $userCategorisation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug("Category chosen", [$form->get('category')->getData()]);
            return $this->redirectToRoute('profile_categorisation_confirm', [
                'category' => $userCategorisation->category->value,
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->render('@AppBundle/Profile/categorisation.html.twig', [
            'form' => $form,
            'obp' => $obp,
            'currentCategory' => $obp->category,
        ]);
    }

    /**
     * @Route("/my-profile/categorisation/{category}", name="profile_categorisation_confirm")
     */
    public function confirmCategorisation(Request $request, UserCategory $category, OnboardingService $onboardingService): Response
    {
        $this->logger->info("IN checkup confirm categorisation");
        $userCategorisation = new UserCategorisation();
        $userCategorisation->category = $category;
        $formClass = match ($category) {
            UserCategory::Restricted => CategoryRestrictedType::class,
            UserCategory::Sophisticated => CategorySophisticatedType::class,
            UserCategory::HighNetWorth => CategoryHnwType::class,
            default => null,
        };
        $template = match ($category) {
            UserCategory::Restricted => 'categorisation_restricted',
            UserCategory::Sophisticated => 'categorisation_sophisticated',
            UserCategory::HighNetWorth => 'categorisation_hnw',
            default => null,
        };
        if (is_null($formClass)) {
            return $this->redirectToRoute('profile_categorisation_confirm', [
                'category' => UserCategory::Restricted->value,
            ], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm($formClass);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userCategorisation->details = $form->getData();
            try {
                $onboardingService->addUserCategorisation($userCategorisation);
                $this->userService->refreshUserInfo();
                $this->addFlash('success', 'Investor type successfully updated');
                return $this->redirectToRoute('profile_categorisation');
            } catch (\Exception $e) {
                $this->logger->error("Issue adding user categorisation", [$e->getMessage()]);
            }
            // $this->logger->debug("Category chosen", $userCategorisation->details);
        }
        return $this->render("@AppBundle/Profile/{$template}.html.twig", [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/my-profile/contact-preferences", name="contact_preferences")
     */
    public function contactPreferencesAction(Request $request): Response
    {
        $this->logger->info("==================IN contactPreferencesAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $this->params['user_info'] = $this->userService->getUserInfo(); // retrieve latest version of user
        $this->requestStack->getSession()->set('userInfo', $this->params['user_info']); // sync userInfo in session
        $this->params['pageinfo'] = 'Contact Preferences';

        $existingPreference = boolval($this->params['user_info']['gdpr_accepted']);

        $form = $this->createForm(ContactPreferencesType::class, ['gdpr_accepted' => $existingPreference]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('GDPR update in progress');
            $formData = $form->getData();
            if ($formData['gdpr_accepted'] == 1) {
                $userUpdateData = [
                    'gdpr_accepted' => 1,
                    'info' => [
                        'contact_via_email' => 1,
                    ],
                ];
            } else {
                $userUpdateData = [
                    'gdpr_accepted' => 0,
                    'info' => [
                        'contact_via_email' => 0,
                    ],
                ];
            }
            $response = $this->userService->update($this->network, $userUpdateData);
            if (!empty($response['outcome']) && $response['outcome'] == 'success') {
                $userRes = $this->userService->getUserInfo();
                $this->requestStack->getSession()->set('userInfo', $userRes);
                $this->addFlash('info', 'Your contact preferences have been successfully updated.');
                try {
                    $this->logger->debug('Syncing with salesforce');
                    $this->client->authenticatedUser()->salesforceSync();
                } catch (\Exception $e) {
                    $this->logger->error("Unable to update Salesforce object" . $e);
                    $this->addFlash('errors', 'CRM contact error');
                }
            } else {
                $this->addFlash('errors', $response['data']['user_message']);
            }
            return $this->redirectToRoute('contact_preferences');
        }

        return $this->render('@AppBundle/Profile/contact_preferences.html.twig', [
            'params' => $this->params,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/add-funds-new", name="add_fund_new")
     */
    public function addFundNewAction(Request $request): Response
    {
        // Check user is logged in
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // $this->userService->setBalance();

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        //Check to see if a user can add funds
        $userStatusResponse = $this->userService->getComplianceCheckStatus();

        $this->logger->warning(json_encode($userStatusResponse));

        if ($userStatusResponse['data']['user']['registration_complete'] || $userStatusResponse['data']['details']['contego_status'] != 'RED') {
            $form = $this->getPayInForm($request);

            $response = $this->render('@AppBundle/Profile/add_funds.html.twig', [
                'form' => $form->createView(),
            ]);

            return $response;
        } else {
            $this->addFlash('errors', "You are unable to add funds at this time. If the issue persists, please contact the Yielders team on +44 2072054650 or email on <a href='mailto:team@yielders.co.uk'>team@yielders.co.uk</a>");
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
        }
    }

    /**
     * @Route("/withdraw-funds", name="withdraw_funds")
     */
    public function withdrawFundsAction(
        Request $request,
        BankAccountService $bankAccountService,
    ): Response {
        // Check user is logged in
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        // refresh user info
        $userRes = $this->userService->getUserInfo();
        $this->requestStack->getSession()->set('userInfo', $userRes);
        $linkingRestrictions = $bankAccountService->checkLinkingRestrictions();

        // Need a Mangopay user and wallet id to be able to pull any accounts
        // No SCA just means you can't add new bank accounts
        // Can still make withdrawals with existing linked bank accounts (before SCA was a thing)
        if ($userRes["mangopay_user_id"] && $userRes["mangopay_wallet_id"]) {
            try {
                // Conditionally sync bank accounts if never done so before
                $lastSync = $bankAccountService->getLastSync();
                if ($lastSync === null) {
                    $this->logger->debug("Syncing legacy mangopay bank accounts");
                    $bankAccountService->syncMangopayLegacyBankAccounts();
                } else {
                    $this->logger->debug("Already synced legacy mangopay bank accounts");
                }
                $linkedAccounts = $bankAccountService->listBankAccounts(activeOnly: true);
                $choices = $bankAccountService->convertLinkAccountsToChoices($linkedAccounts);
            } catch (\Exception $e) {
                $this->addFlash("error", "Unable to load linked bank accounts. Please try again or contact us if issue persists.");
                $this->logger->error("Issuing retrieving linked accounts: " . $e->getMessage());
            }
        }

        $form = $this->createForm(
            type: BankAccountWithdrawalType::class,
            options: ['linkedAccounts' => $choices ?? []],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $params = [
                'wallet_id' => $userRes["mangopay_wallet_id"],
                'amount' => $form->getData()['amount'],
                'bank_account_id' => $form->getData()['account'],
            ];
            $this->logger->debug("Withdrawal with context", $params);
            $res = $this->userService->payoutBankWire($params);

            if (!empty($res['outcome']) && $res['outcome'] == 'success') {
                $this->addFlash('info', 'Create payout successful.');
            } else {
                $this->addFlash('errors', isset($res['data']['user_message']) ? $res['data']['user_message'] : 'Cannot create payout.');
            }
            $this->userService->setBalance();
            return $this->redirectToRoute("transactions_list");
        }

        return $this->render('@AppBundle/Profile/withdraw_funds.html.twig', [
            'form' => $form,
            'linkedAccounts' => $linkedAccounts ?? [],
            'linkingRestrictions' => $linkingRestrictions,
        ]);
    }


    /**
     * @Route("/transaction-export", name="transaction_export")
     */
    public function transactionExportAction(Request $request): Response
    {
        $this->logger->info("==================IN transactionExportAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $userInfo = $this->userService->getUserInfo();
        $this->requestStack->getSession()->set('userInfo', $userInfo);

        if ($userInfo["mangopay_user_id"] && $userInfo["mangopay_wallet_id"] && $userInfo["sca_status"] == "active") {
            // Check if sca requirement changed when attempting to retrieve the transactions
            $scaRequired = $this->requestStack->getSession()->get('walletScaRequired');
            if ($scaRequired) {
                $this->addFlash("info", "SCA verification is required to access your transactions");
                return $this->redirectToRoute('transactions_list');
            }
        }

        return $this->render('@AppBundle/Profile/transaction_export.html.twig', $this->params);
    }

    /**
     * @Route("/share-holder-certificate", name="share_holder_certificate")
     */
    public function shareHolderCertificateAction(Request $request): Response
    {
        $this->logger->info("==================IN shareHolderCertificateAction=====================");

        return $this->render('@AppBundle/Profile/holder_certificate.html.twig', []);
    }

    /**
     * @Route("/update-profile", name="update_profile")
     */
    public function updateProfileAction(Request $request): Response
    {
        $this->logger->info("==================IN updateProfileAction=====================");

        $formData = $request->request->all();
        $formParams = $request->request->keys();
        $redirectRoute = 'profile';
        $userUpdateData = [];

        // just updating investor type
        if (count(array_intersect(['investor_type'], $formParams)) == 1) {
            $this->logger->info("Update investor type");
            $redirectRoute = 'self_cert';
            $userUpdateData = [
                'info' => [
                    'cxb_worth_investor' => null,
                    'cxb_sophisticated_investor' => null,
                    'cxb_restricted_investor' => null,
                ],
            ];
            switch ($formData['investor_type']) {
                case "cxb_restricted_investor":
                    $userUpdateData['info']['cxb_restricted_investor'] = true;
                    break;
                case "cxb_sophisticated_investor":
                    $userUpdateData['info']['cxb_sophisticated_investor'] = true;
                    break;
                case "cxb_worth_investor":
                    $userUpdateData['info']['cxb_worth_investor'] = true;
                    break;
            }
        }

        // just updating basic profile info
        elseif (count(array_intersect(['email', 'phone_1', 'company_name', 'company_registered_address_1', 'company_postcode'], $formParams)) == 5) {
            $this->logger->info("Update user info");
            $redirectRoute = 'profile_edit';
            $userUpdateData = [
                'email' => $formData['email'],
                'phone_1' => $formData['phone_1'],
                'info' => [
                    'company_name' => $formData['company_name'],
                    'company_registered_address_1' => $formData['company_registered_address_1'],
                    'company_postcode' => $formData['company_postcode'],
                ],
            ];
        }
        // unknown update request
        else {
            $this->logger->info("Update profile post without correct params");
            return $this->redirectToRoute($redirectRoute);
        }

        // $this->logger->info("Update body: " . json_encode($userUpdateData));

        $response = $this->userService->update($this->network, $userUpdateData);
        if (!empty($response['outcome']) && $response['outcome'] == 'success') {
            $userRes = $this->userService->getUserInfo();
            $this->requestStack->getSession()->set('userInfo', $userRes);
            $this->addFlash('info', 'Your profile has been successfully updated.');
        } else {
            $this->addFlash('errors', $response['data']['user_message']);
        }
        return $this->redirectToRoute($redirectRoute);
    }

    /**
     * LEGACY - Update contact
     *
     * @Route("/update-contact", name="update_contact")
     */
    public function updateContactAction(Request $request): Response
    {
        $this->logger->info("==================IN updateContactAction=====================");

        $response = $this->userService->update($this->network, $request->request->all());
        if (!empty($response['outcome']) && $response['outcome'] == 'success') {
            //update top profile icon
            // $this->requestStack->getSession()->set('userInfo', $this->user); // don't need to set session userInfo here, done in profileAction()
            $this->addFlash('info', "Your contact updated successful");
        } else {
            $this->addFlash('errors', $response['data']['user_message']);
        }
        return $this->redirect($this->generateUrl('profile'));
    }

    /**
     * @Route("/my-profile/change-password", name="change_password")
     * @author Thinh Nguyen
     * @param Request $request
     * @return \AppBundle\Controller\JsonResponse
     */
    public function changePasswordAction(Request $request): Response
    {
        $this->logger->info("==================IN changePasswordAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $form = $this->createForm(ChangePasswordType::class);
        $response = ['success' => false];

        if ($request->isMethod('POST')) {
            $form->submit($request->request->all()[$form->getName()]);
            if ($form->isValid()) {
                $formData = $form->getData();
                $params = [
                    'current_password' => $formData['current_password'],
                    'new_password' => $formData['password'],
                    'new_password_confirm' => $formData['password'],
                ];
                // Call api
                $result = $this->userService->changePassword($params);
                if (!empty($result['outcome']) && $result['outcome'] == 'success') {
                    //Update user session
                    $network = $this->network;
                    $authentication = $this->publicService->authenticate($network, $this->user['email'], $params['new_password']);
                    if (!$authentication == false) {
                        // set authenticated
                        $this->requestStack->getSession()->set('authenticated', true);
                        $this->requestStack->getSession()->set('userInfo', $this->user);
                    }
                }
                $response = [
                    'success' => $result['outcome'],
                    'msg' => isset($result['data']['user_message']) ? $result['data']['user_message'] : ''
                ];
            }
        }

        return new JsonResponse($response);
    }

    // /**
    //  * @Route("/create-pdf-certificate/{offering_id}", name="create_pdf_certificate")
    //  *
    //  * @param $offering_id
    //  * @return array|Response
    //  */
    // public function createPDFCertificate($offering_id)
    // {
    //     $this->logger->info("==================IN createPDFCertificate=====================");

    //     $authenticated = $this->requestStack->getSession()->get('authenticated');
    //     if (!$authenticated) {
    //         return $this->redirectToRoute('login');
    //     }

    //     if (empty($offering_id)) {
    //         return [];
    //     }
    //     $offering = $this->offeringService->get($offering_id);
    //     $data = [];
    //     if (!empty($offering['outcome']) && $offering['outcome'] == 'success') {
    //         $offering = $offering['data']['offering'];
    //         $investments = $this->legacyInvestmentService->selfInvestments();
    //         $invested = [];
    //         $investmentAmount = 0;
    //         if (!empty($investments['outcome']) && $investments['outcome'] == 'success') {
    //             $investmentsList = $investments['data']['list'];
    //             foreach ($investmentsList as $investment) {
    //                 if ($investment['offering_id'] == $offering_id && $investment['life_cycle_stage'] == 4) {
    //                     $invested[] = $investment;
    //                     $investmentAmount += $investment['investment_amount'];
    //                 }
    //             }
    //         }
    //         $user = $this->userService->getUser();
    //         if (!empty($user['outcome']) && $user['outcome'] == 'success') {
    //             $user = $user['data']['user'];
    //         }
    //         // Get organization details
    //         $orgDetails = $this->organizationService->getOne($offering['organization_id']);
    //         $pricePerShare = Util::getInfo($orgDetails['data']['organization'], 'price_per_share', 1);
    //         $offering['number_of_shares'] = ($investmentAmount / $pricePerShare);
    //         $data['offering'] = $offering;
    //         $data['invested'] = $invested;
    //         $data['user'] = $user;
    //     }

    //     $content = $this->render('@AppBundle/Profile/holder_certificate.html.twig', [
    //         'data' => $data,
    //     ]);
    //     $html = iconv("UTF-8", "UTF-8//IGNORE", $content);
    //     $unique = Util::alphaNumCodeGenerator(20);
    //     $storage_name = $unique . '_share_certificate.pdf';
    //     $mPDF = new \Mpdf\Mpdf(['tempDir' => $this->tempDir]);
    //     $mPDF->WriteHTML($html);
    //     $pdfData = $mPDF->Output($storage_name, 'S');

    //     //return pdf file to user
    //     $response = new Response();
    //     // Set headers
    //     $response->headers->set('Cache-Control', 'private');
    //     $response->headers->set('Content-Type', 'application/pdf');
    //     $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($storage_name) . '";');
    //     $response->sendHeaders();
    //     $response->setContent($pdfData);

    //     return $response;
    // }

    /**
     * @Route("/add-funds", name="add_fund")
     */
    public function addFundAction(Request $request): Response
    {
        $this->logger->info("==================IN addFundAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        return $this->redirectToRoute('add_fund_new');
    }


    /**
     * This route outputs a file
     *
     * @Route("/transaction-history", name="transaction_history")
     */
    public function transactionHistoryAction(Request $request): Response
    {
        $this->logger->info("==================IN transactionHistoryAction=====================");

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        // $this->logger->info("Query params: " . json_encode($request->query->all()));

        $userInfo = $this->requestStack->getSession()->get('userInfo');

        $startDate = strtotime($request->query->get('start'));
        $endDate = strtotime($request->query->get('end'));

        $parameters = [
            'orderby' => 'creation',
            'sort' => 'desc',
            'page' => 1 // default export 1 page (10 transactions)
        ];

        if ($startDate && $endDate) {
            $parameters['page'] = 0; // only do full export if we have valid date range
            $parameters['start'] = $startDate;
            $parameters['end'] = $endDate + 86399; // add unixtime for rest of the day
        }
        $this->logger->info("API Query params: " . json_encode($parameters));

        $transactions = $this->convertTagsToDict(
            $this->userService->getTransactionsSca($parameters),
        );

        $filename = "export_transaction_history" . date("Y_m_d_His") . ".csv";
        $response = $this->render('@AppBundle/Profile/transaction_history.html.twig', [
            'transactions' => $transactions,
            'wallet' => ['id' => $userInfo['mangopay_wallet_id']],
        ]);
        $response->setStatusCode(200);
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Description', 'Submissions Export');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    // /**
    //  * @Route("/investment-documents", name="investment_documents")
    //  */
    // public function investmentDocumentsAction(Request $request): Response
    // {
    //     $this->logger->info("==================IN investmentDocumentsAction=====================");

    //     $authenticated = $this->requestStack->getSession()->get('authenticated');
    //     if (!$authenticated) {
    //         return $this->redirectToRoute('login');
    //     }

    //     // Check if a user has completed their on boarding stages, if not redirect them to onboarding
    //     $ObResponse = $this->crowdTekService->checkUserRegistered();

    //     if ($ObResponse == false) {
    //         return $this->redirect($this->generateUrl('Onboarding'));
    //     }

    //     $hasWallet = $this->requestStack->getSession()->has('wallet_id');
    //     if (!$this->user['has_been_approved'] || !$this->user['registration_complete'] || !$hasWallet) {
    //         return $this->redirectToRoute('homepage');
    //     }
    //     $network = $this->network;
    //     $offeringId = $request->request->get('offering_id', 0);
    //     $offering = $this->offeringService->get($offeringId);
    //     $investments = $this->offeringService->listInvestmentsByOfferingId($network, $offeringId);

    //     $response = [];
    //     if (!empty($investments['outcome']) && $investments['outcome'] == 'success') {
    //         $investmentsList = $investments['data']['list'];
    //         foreach ($investmentsList as $investment) {
    //             // Load documents
    //             $documents = $this->legacyInvestmentService->listDocuments($investment['id']);
    //             if (isset($documents['data']['list']) && count($documents['data']['list']) > 0) {
    //                 $response = array_merge($response, $documents['data']['list']);
    //             }
    //         }
    //     }

    //     $this->params['offering'] = $offering['data']['offering'];
    //     $this->params['res'] = $response;
    //     return $this->render('@AppBundle/Profile/investment_documents.html.twig', $this->params);
    // }


    // filters a list of dicts [{},{},{}] with a key value pair ["k"=>"v"]
    protected function filterArraysByKvPair($accounts_list, $filter)
    {
        $this->logger->info("==================IN filterArraysByKvPair=====================");
        /**
         * Loop through the list of dicts
         * Filter out the dicts that don't contain the key:value pair $filter
         */
        return array_filter($accounts_list, function ($account) use ($filter) {
            return !array_diff_assoc($filter, $account);
        });
    }

    // for parsing Mangopay transaction tags
    protected function convertTagsToDict($transactions, $delimeter = ";")
    {
        $this->logger->info("==================IN convertTagsToDict=====================");
        /**
         * Go through Tag field of each transaction and convert the string into K:V dict
         *
         */

        $convertedTransactions = [];
        foreach ($transactions as $transaction) {
            if ($transaction['Tag']) {
                $tags = explode($delimeter, $transaction['Tag']);
                $transaction['Tag'] = []; // convert Tag into array type (we've already got the contents in $tags)
                foreach ($tags as $tag) {
                    // check if they are k:v pairs in that format, otherwise, just add to array as value
                    // won't handle tags not in k:v format for now (i.e. keyless tags)
                    if (count(explode(":", $tag, 2)) == 2) {
                        $tagElements = explode(":", $tag, 2);
                        $transaction['Tag'][trim($tagElements[0])] = $tagElements[1];
                    }
                }
            } else {
                $transaction['Tag'] = []; // convert Tag into array type (it's empty anyway!)
            }
            if ($transaction['Nature'] == "REFUND") {
                $transaction['Tag']["Type"] = "Refund"; // For refunds, ensure Type is always Refunds, not anything else
            }
            $convertedTransactions[] = $transaction;
        }
        return $convertedTransactions;
    }

    /**
     * @Route("/my-profile/payin-success", name="MyProfilePayInSuccess")
     *
     * May need to use this if we want a different payinsuccess for onboarding ?
     *
     */
    public function payinSuccessAction(Request $request)
    {
        $authenticated = $this->requestStack->getSession()->get('authenticated');

        $this->logger->debug("Payin success query params", $request->query->all());

        $amount = $this->requestStack->getSession()->get('tempAmount');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        // You have to get the status of the last transaction here for the user to get the correct message here
        $transactionId = $request->get('transactionId');
        $this->logger->warning('Transaction id=[' . $transactionId . ']');

        // Get the payin transaction to confirm status
        try {
            $this->logger->debug("Checking payin status");
            $response = $this->client->mangopayWallet()->retrievePayin($transactionId);
            $payin = $this->client->getContent($response);

            // Not 200 status code, so something went wrong on API end
            if (200 != $response->getStatusCode()) {
                $this->logger->error("Error retrieving payin with transaction id {$transactionId}", [
                    'statusCode' => $response->getStatusCode(),
                ]);
                $this->addFlash('warning', "Unable to verify status of payin. Please check your transaction history.");
                return $this->redirectToRoute('transactions_list');
            }

            // Got the payin successfully from the API
            // A successful response does not necessarily mean the payin itself was successful
            // So check the payin status
            if ($payin['data']['status'] == "SUCCEEDED") {
                $this->addFlash('info', "Successfully added £" . $amount . " to your wallet");
                $this->userService->setBalance();
            } else {
                $errorMessage = $this->handleMangoPayErrorCode($payin['data']['result_code']);
                $this->addFlash('warning', "Unable to process payin: {$errorMessage}");
                return $this->redirectToRoute('transactions_list');
            }
        } catch (\Exception $e) {
            // Some other issue preventing us from checking the payin status
            $this->logger->error("Error retrieving payin with transaction id {$transactionId}");
            $this->addFlash('warning', "Unable to verify status of payin. Please check your transaction history.");
            return $this->redirectToRoute('transactions_list');
        }

        // return $this->redirect('/my-profile');
        return $this->render('@AppBundle/Profile/payin_success.html.twig', [
            'payin' => $payin['data'],
        ]);
    }

    private function getPayInForm(Request $request)
    {
        /*
         * The post of this form goes directly to the Mangopay servers
         * The response url is defined in 'returnURL'
         *
         * The mangopay server responds to our returnURL with a response containing 'data'
         * The 'data' field can then be used to process the payin
         *
         */

        //1 - Generate card registration link
        $response = $this->userService->registerUserCardWithMangoPay();

        if ($this->isApiRequestSuccessful($response) === false) {
            $this->addFlash('errors', $response['data']['user_message']);
            return $this->redirectToRoute('homepage');
        } else {
            //Store cardId in session to be used after the mangopay post form
            $this->requestStack->getSession()->set('tempCardId', $response['data']['card_registration']['id']);
            $this->logger->debug("Setting tempCardId=[" . $response['data']['card_registration']['id'] . ']');
        }

        //The processed form will redirect to /tempCardPayin
        $form = $this->formFactory->createNamedBuilder('')
            //->add('cardNumber',TextType::class)
            //  ->add('cardExpirationDate',DateType::class)
            ->add('amount', IntegerType::class, [
                'data' => 100,
                'invalid_message' => 'You entered an invalid value, it should include %num% letters',
                'label' => 'Amount',
            ])
            //->add('cardHolder',TextType::class, array(
            //    'label' => 'Card Holder'
            //))
            ->add('cardNumber', NumberType::class, [
                'label' => 'Card Number',
            ])
            ->add('cardCvx', NumberType::class, [
                'label' => 'CVV',
            ])
            ->add('cardExpirationDate', HiddenType::class, [
                'label' => 'Card Expiration Date',
            ])
            ->add('expiryDatePicker', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y') + 10),
                'format' => 'yyyy-MM-dd',
                'placeholder' => [
                    'year' => 'YYYY',
                    'month' => 'MM',
                    'day' => false,
                ],
                'label' => 'Card Expiration Date',
            ])
            ->add('data', HiddenType::class, ['data' => $response['data']['card_registration']['preregistration_data']])
            ->add('accessKeyRef', HiddenType::class, ['data' => $response['data']['card_registration']['access_key']])
            ->add('returnURL', HiddenType::class, ['data' => $this->getHostUrl($request) . '/my-profile/invest-now'])
            ->add('addFunds', SubmitType::class)
            ->setAction($response['data']['card_registration']['card_registration_url'])
            ->setMethod('POST')
            ->getForm();

        return $form;
    }

    private function getHostUrl(Request $request)
    {
        // Get the hostname that will be used for the return url
        $host = $this->container->get('router')->getContext()->getHost();

        // Get the protocol that will be used for the return url (in prod this should always be https)
        $protocol = null;
        if ($request->isSecure()) {
            $this->logger->debug("-Using return url https://" . $host);
            $protocol = "https://";
        } else {
            $this->logger->debug("-Using return url http://" . $host);
            $protocol = "http://";
        }

        return $protocol . $host;
    }

    private function handleApiResponse($apiResponse)
    {
        //Get the user details
        $userData = $this->requestStack->getSession()->get('userInfo');

        if (empty($apiResponse['outcome'])) {
            //$this->addFlash('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }

        if ($apiResponse['outcome'] == 'fail') {
            //$this->addFlash('errors', $apiResponse['data']['user_message']);
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], [" . json_encode($apiResponse) . "]");
            //return $this->render('@AppBundle/Onboarding/generic-failure.html.twig', array('response_error' => $contegoRes['data']['user_message']));

            throw new \Exception($apiResponse['data']['user_message']);
        } elseif ($apiResponse['outcome'] == 'success') {
            $this->logger->debug("SUCCESS API response for user=[" . $userData['email'] . "], [" . json_encode($apiResponse) . "]");
        } else {
            //Somethimg unexpected happened
            //$this->addFlash('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }
    }

    public function isApiRequestSuccessful($response)
    {
        if ($response === null) {
            $this->logger->error("--+++++++---" . $response['data']['devMessage']);

            return false;
        }

        if (!empty($response['outcome']) && $response['outcome'] == 'success') {
            return true;
        } else {
            $user = $this->requestStack->getSession()->get('userInfo');
            $this->logger->error("USER [" . $user['email'] . "] - " . $response['data']['devMessage']);
            return false;
        }
    }




    /**
     * @Route("/my-profile/invest-now", name="MyProfileInvestNow")
     */
    public function investNowAction(Request $request)
    {
        // Check user is logined
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $user = $this->requestStack->getSession()->get('userInfo');

        $tempCardId = $this->requestStack->getSession()->get('tempCardId');

        // Check the tempCardId is set
        if (empty($tempCardId)) {
            $this->addFlash('errors', "Session does not contain valid data!");
            return $this->redirectToRoute('homepage');
        }

        //Make sure $request has data otherwise something went wrong in the post to mangopay
        // Get the data variable, this is the response from mangopay directly
        $data = $request->query->get('data') == null ? 0 : $request->query->get('data');

        $this->logger->debug("USER [" . $user['email'] . "], CardId= - " . $tempCardId);
        $this->logger->debug("USER [" . $user['email'] . "], mangopay data= - " . $data);


        //Set some parameters for card registration
        $params['card_registration_id'] = $tempCardId;
        $params['data'] = $data;

        $response = $this->userService->registerCardWithMangoPay($params);

        if ($this->isApiRequestSuccessful($response) === false) {
            $this->logger->debug("USER [" . $user['email'] . "], API registerCardWithMangoPay = - " . json_encode($response));

            //As this response is coming from mangopay we get back a errorCode in the url and we need to translate this into the appropriate message
            $errorMessage = $this->handleMangoPayErrorCode($request->query->get('errorCode'));

            $form = $this->getPayInForm($request);

            $displayErrorMessage =
                '<div class="faild">
                                <i class="fas fa-frown-open" style="color: red"></i>
                                <h4>Sorry your payment request failed</h4><br>
                                <p>' . $errorMessage . '</p>
                                <p>You payment has been unsuccessful. Please try again. Alternatively, if the issue persists, please contact the Yielders team on +44 2072054650 or email on <a href="mailto:team@yielders.co.uk?subject=Onboarding Payin Failed">team@yielders.co.uk</a></p>

                            </div>';

            $this->addFlash('errors', $displayErrorMessage);

            return $this->render('@AppBundle/Profile/add_funds.html.twig', [
                'form' => $form->createView(),
            ]);
        }

        $this->logger->debug("USER [" . $user['email'] . "], API registerCardWithMangoPay = - " . json_encode($response));

        // Now you have the card_id and you can do a payin, just need to get the amount that we set in the session earlier
        // and what the return url post 3DS will be
        $payinData['amount'] = $this->requestStack->getSession()->get('tempAmount');
        $payinData['SecureModeReturnURL'] = $this->getHostUrl($request) . '/my-profile/payin-success';
        $payinData['userId'] = $user['id'];
        $payinData['ipAddress'] = $request->getClientIp();
        $payinData['browserInfo']['acceptHeader'] = $request->headers->get('Accept');
        $payinData['browserInfo']['userAgent'] = $request->headers->get('User-Agent');
        $payinData['browserInfo']['language'] = $request->getLocale();
        $payinData['browserInfo']['screenWidth'] = $this->requestStack->getSession()->get('screenWidth') ?? '';
        $payinData['browserInfo']['screenHeight'] = $this->requestStack->getSession()->get('screenHeight') ?? '';
        $payinData['browserInfo']['colorDepth'] = $this->requestStack->getSession()->get('colorDepth') ?? '';
        $payinData['browserInfo']['timeZoneOffset'] = $this->requestStack->getSession()->get('timeZoneOffset') ?? '';
        $payinData['browserInfo']['javaEnabled'] = $this->requestStack->getSession()->get('javaEnabled') ?? '';
        $payinData['browserInfo']['javascriptEnabled'] = $this->requestStack->getSession()->get('javascriptEnabled') ?? '';

        $payinResponse = $this->userService->payinWithRegisteredCardMangoPay($response['data']['card_id'], $payinData);

        $this->logger->warning(json_encode($payinResponse));

        //  We don't get SecureModeRedirectURL if we don't go through 3DSx§
        if (empty($payinResponse['data']['SecureModeRedirectURL'])) {
            //this should return the next page in the wizard/success message for payin
            return $this->redirectToRoute('MyProfilePayInSuccess', [
                'transactionId' => $payinResponse['data']['Id'] ?? "",
                'status' => $payinResponse['data']['Status'] ?? "",
            ]);
        } else {
            //To complete the payin with a 3DS card you need to redirect to 3DS password page
            return $this->redirect($payinResponse['data']['SecureModeRedirectURL']);
        }
    }

    private function handleMangoPayErrorCode($errorCode)
    {
        switch ($errorCode) {
            case "02625":
                return "[Invalid card number]";
            case "02626":
                return "[Invalid date]";
            case "02627":
                return "[Invalid CCV number]";
            case "02628":
                return "[Transaction refused]";
            case "01902":
                return "[This card is not active]";
            case "02624":
                return "[Card expired]";
            default:
                return "[Unable to process payment " . $errorCode . "]";
        }
    }

    /**
     * @Route("/add-funds/bankwire", name="add_funds_bankwire")
     */
    public function addFundsBankwireAction(Request $request)
    {
        $this->logger->info("==================IN addFundsBankwireAction=====================");

        // Check user is logged in
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdTekService->checkUserRegistered();
        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        //Check to see if a user can add funds
        $userStatusResponse = $this->userService->getComplianceCheckStatus();
        // $this->logger->info(json_encode($userStatusResponse));
        if (!($userStatusResponse['data']['user']['registration_complete'] || $userStatusResponse['data']['details']['contego_status'] != 'RED')) {
            $this->addFlash('errors', "You are unable to add funds at this time. If the issue persists, please contact the Yielders team on +44 2072054650 or email on <a href='mailto:team@yielders.co.uk'>team@yielders.co.uk</a>");
            $referer = $request->headers->get('referer');
            return $this->redirect($referer);
        }

        $userInfo = $this->requestStack->getSession()->get('userInfo');
        if (!($userInfo["mangopay_user_id"] && $userInfo["mangopay_wallet_id"])) {
            $this->logger->debug("User missing Mangopay wallet or not enrolled with SCA");
            $this->addFlash(
                "info",
                "You must have a Mangopay wallet to deposit funds",
            );
            return $this->redirectToRoute('profile');
        }

        $defaultData = ['amount' => 100];
        $form = $this->createFormBuilder($defaultData)
            ->add('amount', NumberType::class, [
                'constraints' => [new GreaterThanOrEqual(100)],
                'html5' => true,
                'label' => 'Amount (£)',
                'required' => true,
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $amount = $formData['amount'];
            $this->requestStack->getSession()->set('tempAmount', $amount);
            $data = [
                'amount' => $amount,
                'wallet_id' => $userInfo["mangopay_wallet_id"],
            ];
            $response = $this->userService->payinBankWire($data);
            if (!empty($response['outcome']) && $response['outcome'] == 'success') {
                $this->logger->info("Bankwire transfer instruction created");
                // $this->logger->info("Bankewire acc", $response['data']);
                return $this->render('@AppBundle/Profile/add_funds_bankwire_details.html.twig', [
                    'amount' => $amount,
                    'bankAccount' => $response['data']['bank_account'],
                ]);
            }
            $this->logger
                ->error("Unable to create payin bankwire", $response);
            $this->addFlash(
                "errors",
                "An error occured when trying to create a bankwire transfer instruction. If error persists, please contact us.",
            );
        }
        return $this->render('@AppBundle/Profile/add_funds_bankwire.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/my-profile/browserinfo/set", name="browser_info_set")
     * @param Request $request
     */
    public function browserInfoSetAction(Request $request)
    {
        $screenWidth = $request->request->get('screenWidth');
        $screenHeight = $request->request->get('screenHeight');
        $colorDepth = $request->request->get('colorDepth');
        $timeZoneOffset = $request->request->get('timeZoneOffset');
        $javaEnabled = $request->request->get('javaEnabled');
        $javascriptEnabled = $request->request->get('javascriptEnabled');

        $this->requestStack->getSession()->set('screenWidth', $screenWidth);
        $this->requestStack->getSession()->set('screenHeight', $screenHeight);
        $this->requestStack->getSession()->set('colorDepth', $colorDepth);
        $this->requestStack->getSession()->set('timeZoneOffset', $timeZoneOffset);
        $this->requestStack->getSession()->set('javaEnabled', $javaEnabled);
        $this->requestStack->getSession()->set('javascriptEnabled', $javascriptEnabled);

        return new JsonResponse('Browser info saved to session');
    }
}
