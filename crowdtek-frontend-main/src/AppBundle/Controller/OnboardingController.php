<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 24/07/18
 * Time: 14:53
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\ScaStatus;
use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\ObStepConstant as obStep;
use AppBundle\Entity\UserCategorisation;
use AppBundle\Entity\UserCustomInfo;
use AppBundle\Entity\UserCustomInfo as UserInfo;
use AppBundle\Entity\UserEntity;
use AppBundle\Entity\UserEntity as User;
use AppBundle\Form\CategoryHnwType;
use AppBundle\Form\CategoryRestrictedType;
use AppBundle\Form\CategorySophisticatedType;
use AppBundle\Form\Onboarding\CreateComplianceFlow;
use AppBundle\Form\Onboarding\CreateRegulationFlow;
use AppBundle\Form\Onboarding\CreateUserFlow;
use AppBundle\Form\UserAssessmentType;
use AppBundle\Form\UserCategorisationType;
use AppBundle\Util;
use ClientBundle\Service\CrowdTekService;
use ClientBundle\Service\DocumentService;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PublicService;
use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/onboarding')]
class OnboardingController extends AbstractController
{
    protected $userEntity;
    protected $userInfo;
    protected $questionnaireAttempt = 0;

    public function __construct(
        private LoggerInterface $logger,
        private ApiClient $client,
        private RequestStack $requestStack,
        private FormFactoryInterface $formFactory,
        private CrowdTekService $crowdTekService,
        private DocumentService $documentService,
        private PublicService $publicService,
        private UserService $userService,
        private OnboardingService $onboardingService,
        private ScaService $scaService,
        private string $network,
        private string $recaptchaSiteKey,
        private string $recaptchaSecret,
    ) {
        $this->userEntity = new User();
        $this->userInfo = new UserInfo();
    }

    #[Route(path: '/', name: 'Onboarding', methods: ['GET'])]
    public function indexAction(Request $request): Response
    {
        /**
         * If logged in, load user info and get their onboarding step
         * Then redirect to relevant page for their onboarding step/progress
         * Otherwise, redirect to the sign-up page
         */
        $isAuthenticated = $this->requestStack->getSession()->get('authenticated');
        if ($isAuthenticated == 1) {
            $this->getSelfUserUpdate();
            $userData = $this->requestStack->getSession()->get('userInfo');
            $obStep = $userData["ob_step"];
            return match ($obStep) {
                1 => $this->redirect($this->generateUrl('OBemail_verification')),
                2 => $this->redirect($this->generateUrl('OBregulation_preference')),
                3 => $this->redirect($this->generateUrl('OBregulation_knowledge')),
                4 => $this->redirect($this->generateUrl('OBcompliance')),
                5 => $this->redirect($this->generateUrl('OBcomplete')),
                default => $this->redirect($this->generateUrl('OBemail_verification')),
            };
        }
        return $this->redirect($this->generateUrl('OBsign_up'));
    }

    #[Route(path: '/sign-up', name: 'OBsign_up', methods: ['GET', 'POST'])]
    public function signUpOnboardingAction(Request $request, CreateUserFlow $flow): Response
    {
        // Guard clause to prevent users who are already logged in
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if ($authenticated) {
            $this->addFlash('errors', 'User already logged in');
            return $this->redirectToRoute('Onboarding');
        }

        $formData = $this->userEntity;
        $userCustInfo = $this->userInfo;

        $flow->bind($formData);
        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);
            if (
                !($flow->getCurrentStepNumber() > 1) &&
                !$this->captchaverify($request->get('g-recaptcha-response'))
            ) {
                $this->addFlash(
                    'errors',
                    'Invalid captcha code',
                );
                return $this->redirect($this->generateUrl('Onboarding'));
            }

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                $formData->setUrl($this->generateUrl('verify_email', [], UrlGeneratorInterface::ABSOLUTE_URL));

                $user_creation_fail_msg = 'We are currently unable to create your account. Please try again later or contact us at team@yielders.co.uk';

                /**
                 * - Create CMS user - needs response to be true (no exceptions thrown on error, just error messages)
                 * - Try to login - exception handling for login action - this also syncs with Salesforce if successful
                 * - If all above are okay, proceed to next step of onboarding
                 */
                try {
                    $response = $this->crowdTekService->createCMSUser($formData);
                    if ($response === true) {
                        try {
                            $this->logUserIn($request, $formData);
                        } catch (\Exception $e) {
                            $this->addFlash('errors', 'Caught exception: ' . $e->getMessage());
                        }
                        return $this->redirect($this->generateUrl('Onboarding'));
                    }
                } catch (\Exception $e) {
                    $this->logger->error("===== Error response - " . $e . " =====");
                    $this->addFlash('errors', $user_creation_fail_msg);
                }
            }
        } else {
            $errors = $form->getErrors(true);
            $numOfErrors = \iterator_count($errors);

            if ($numOfErrors > 0) {
                $errorMessages = "<ul>";
                foreach ($errors as $error) {
                    $errorMessages .= "<li>" . $error->getMessage() . "</li>";
                }
                $errorMessages .= "</ul>";

                if ($numOfErrors == 1) {
                    $this->addFlash('errors', "1 error prohibited user registration "
                        . "<br>" . $errorMessages);
                } else {
                    $this->addFlash('errors', $numOfErrors . " errors prohibited user registration " .
                        "<br>" . $errorMessages);
                }
            }
        }

        $recaptcha_site_key = $this->recaptchaSiteKey;

        return $this->render(
            '@AppBundle/Onboarding/sign-up.html.twig',
            [
                'form' => $form->createView(),
                'flow' => $flow,
                'recaptcha_site_key' => $recaptcha_site_key,
            ],
        );
    }

    #[Route(path: '/email-verification', name: 'OBemail_verification', methods: ['GET', 'POST'])]
    public function emailVerificationOnboardingAction(): Response
    {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $userData = $this->requestStack->getSession()->get('userInfo');

        $obStep = $userData["ob_step"];

        if ($obStep != 1) {
            // return $this->redirectToRoute('Onboarding');

            switch ($obStep) {
                case 2:
                    return $this->redirect($this->generateUrl('OBregulation_preference'));
                case 3:
                    return $this->redirect($this->generateUrl('OBregulation_knowledge'));
                case 4:
                    return $this->redirect($this->generateUrl('OBcompliance'));
                case 5:
                    return $this->redirect($this->generateUrl('OBcomplete'));
                default:
                    break;
            }
        }

        return $this->render('@AppBundle/Onboarding/email-verification.html.twig');
    }

    #[Route(path: '/regulation-preference', name: 'OBregulation_preference', methods: ['GET', 'POST'])]
    public function regulationOnboardingAction(CreateRegulationFlow $flow): Response
    {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $userData = $this->requestStack->getSession()->get('userInfo');

        $obStep = $userData["ob_step"];

        if ($obStep != 2) {
            return $this->redirectToRoute('Onboarding');
        }

        $formData = $this->userInfo;

        // $flow = $this->get('yielders.form.flow.onboardingRegulation'); // must match the flow's service id
        $flow->bind($formData);

        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                $fatca = $formData->isFatca();
                if ($fatca) {
                    $formData->setFatca($fatca);
                } else {
                    $formData->setFatca(0);
                }
                $response = $this->crowdTekService->updateUserInfoFields($formData, 2);

                if ($response === true) {
                    $this->crowdTekService->updateUserOBStep(obStep::STEP3_INT);

                    $this->logger->debug('Syncing with salesforce');
                    $this->client->authenticatedUser()->salesforceSync();

                    return $this->redirect($this->generateUrl('Onboarding')); // redirect when done
                } else {
                    $this->addFlash('errors', $response);
                }
            }
        }

        // Dynamic dating for T&C's

        // default show this year
        $year = date("Y");
        if (time() < strtotime(date("Y") . '-01-30')) {
            // show last year in format YYYY
            $year = date("Y", strtotime("-1 years"));
        }


        return $this->render(
            '@AppBundle/Onboarding/regulation-preference.html.twig',
            [
                'form' => $form->createView(),
                'flow' => $flow,
                'year' => $year,
            ],
        );
    }

    /**
     * See https://gitlab.com/yielders2/crowdtek-frontend/-/issues/1219#note_2043258939
     * for the repurposing of this route as a redirector
     *
     * Legacy OB steps kept in place to minimise disruption, new routes added as redirect logic
     */
    #[Route(path: '/regulation-knowledge', name: 'OBregulation_knowledge', methods: ['GET', 'POST'])]
    public function knowledgeOnboardingAction(): Response
    {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $userData = $this->requestStack->getSession()->get('userInfo');

        $obStep = $userData["ob_step"];

        if ($obStep != 3) {
            return $this->redirectToRoute('Onboarding');
        }

        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        if (empty($obp->category)) {
            return $this->redirectToRoute('OB_categorisation');
        }
        if (!$obp->assessmentPassed) {
            if ($this->onboardingService->canTakeAssessment($obp)) {
                return $this->redirectToRoute('OB_assessment');
            } else {
                // Not allowed to retake yet
                return $this->redirectToRoute('OB_assessment_fail');
            }
        }
        // If both category has been set and assessment is passed
        $this->crowdTekService->updateUserOBStep(obStep::STEP4_INT);
        return $this->redirectToRoute('OBcompliance');
    }

    #[Route(path: '/categorisation', name: 'OB_categorisation', methods: ['GET', 'POST'])]
    public function categorisation(Request $request): Response
    {
        $this->logger->info("IN onboarding categorisation");
        $userCategorisation = new UserCategorisation();
        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        $userCategorisation->category = $obp->category ?? UserCategory::Restricted;
        if ($userCategorisation->category == UserCategory::None) {
            $userCategorisation->category = UserCategory::Restricted;
        }
        $form = $this->createForm(UserCategorisationType::class, $userCategorisation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug("Category chosen", [$form->get('category')->getData()]);
            return $this->redirectToRoute('OB_categorisation_confirm', [
                'category' => $userCategorisation->category->value,
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->render('@AppBundle/Onboarding/categorisation.html.twig', [
            'form' => $form,
            'obp' => $obp,
            'currentCategory' => $obp->category,
        ]);
    }

    #[Route(path: '/categorisation/{category}', name: 'OB_categorisation_confirm', methods: ['GET', 'POST'])]
    public function confirmCategorisation(Request $request, UserCategory $category): Response
    {
        $this->logger->info("IN onboarding confirm categorisation");
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
            return $this->redirectToRoute('OB_categorisation_confirm', [
                'category' => UserCategory::Restricted->value,
            ], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm($formClass);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userCategorisation->details = $form->getData();
            try {
                $this->onboardingService->addUserCategorisation($userCategorisation);
                $this->userService->refreshUserInfo();
                return $this->redirectToRoute(
                    'OB_assessment',
                    [],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Exception $e) {
                $this->logger->error("Issue adding user categorisation", [$e->getMessage()]);
                $this->addFlash('error', 'Unable to set investor type. Please try again or contact team@yielders.co.uk if issues persist.');
            }
            $this->logger->debug("Category chosen", $userCategorisation->details);
        }
        return $this->render("@AppBundle/Onboarding/{$template}.html.twig", [
            'form' => $form,
        ]);
    }

    #[Route(path: '/assessment', name: 'OB_assessment', methods: ['GET'])]
    public function assessment(Request $request): Response
    {
        $this->logger->info("IN onboarding assessment");
        return $this->render('@AppBundle/Onboarding/assessment.html.twig');
    }

    #[Route(path: '/assessment/quiz', name: 'OB_assessment_quiz', methods: ['GET', 'POST'])]
    public function assessmentQuiz(Request $request): Response
    {
        $this->logger->info("IN onboarding assessment quiz");

        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        if ($obp->assessmentPassed) {
            return $this->redirectToRoute('OBcompliance');
        }
        if (!$this->onboardingService->canTakeAssessment($obp)) {
            return $this->redirectToRoute('OB_assessment_fail');
        }

        // $questionnaire = $this->onboardingService->getQuestionnaire();
        // $userAssessment = $this->onboardingService->prepareQuestionnaire($questionnaire);
        $userAssessment = $this->onboardingService->getCurrentUserAssessment();
        // $this->logger->debug("Questionnaire", [$userAssessment->responses[0]->question->choices]);

        $form = $this->createForm(UserAssessmentType::class, null, ['responses' => $userAssessment->responses]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $this->logger->debug("Questionnaire", [$userAssessment]);
            $userAssessment->responses = $form->getData();
            $userAssessment->complete = true;
            try {
                $response = $this->onboardingService->addUserAssessment($userAssessment);
                $this->userService->refreshUserInfo();
                $this->logger->info("Assessment result", $response);
                if ($response['passed']) {
                    $this->crowdTekService->updateUserOBStep(obStep::STEP4_INT);
                    return $this->redirectToRoute('OBcompliance');
                } else {
                    return $this->redirectToRoute('OB_assessment_fail');
                }
            } catch (\Exception $e) {
                $this->logger->error("Issue adding user assessment", [$e->getMessage()]);
                $this->addFlash('error', 'Unable to process questionnaire');
            }
            return $this->redirectToRoute('OB_assessment');
        }
        return $this->render('@AppBundle/Onboarding/assessment_quiz.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/assessment/fail', name: 'OB_assessment_fail', methods: ['GET'])]
    public function assessmentFail(Request $request): Response
    {
        $this->logger->info("IN onboarding completion");
        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        return $this->render('@AppBundle/Onboarding/assessment_fail.html.twig', [
            'obp' => $obp,
            'canTakeAssessment' => $this->onboardingService->canTakeAssessment($obp),
        ]);
    }

    #[Route(path: '/compliance', name: 'OBcompliance', methods: ['GET', 'POST'])]
    public function complianceOnboardingAction(Request $request, CreateComplianceFlow $flow): Response
    {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $user = $this->requestStack->getSession()->get('userInfo');

        $obStep = $user["ob_step"];

        if ($obStep != 4) {
            return $this->redirectToRoute('Onboarding');
        }

        $this->requestStack->getSession()->set('hide_add_funds', false);

        // $user_info = Util\Util::getUserInfoArray($user['info']);

        $formData = $this->userEntity;

        // $flow = $this->get('yielders.form.flow.onboardingCompliance'); // must match the flow's service id
        $flow->bind($formData);

        $form = $flow->createForm();
        if ($flow->isValid($form)) {
            $flow->saveCurrentStepData($form);

            if ($flow->nextStep()) {
                // form for the next step
                $form = $flow->createForm();
            } else {
                $formData->setBirthDate($formData->getBirthDate()->format('Y-m-d'));

                // $this->logger->info("FormData ". json_encode((array)$formData));

                $response = $this->crowdTekService->patchUser($formData);

                if ($response == 1) {
                    try {
                        $this->uploadDocuments($formData->getDocument());
                    } catch (\Exception $e) {
                        $this->logger->error("Problem uploading documents to backoffice -" . $e);
                        return $this->render('@AppBundle/Onboarding/generic-failure.html.twig', ['response_error' => $e->getMessage()]);
                    }

                    try {
                        // Register the user with Mangopay if they aren't already
                        if (empty($user['mangopay_user_id'])) {
                            $this->logger->debug("Registering mangopay account for user=[" . $user['email'] . "]");
                            // Switch to using createMangopayUserSca() for proper SCA flow
                            // Note that you cannot create a wallet for SCA users until they have successfully enrolled in SCA
                            $registerMangopayUser = $this->userService->createMangopayUserSca();
                            $this->handleApiResponse($registerMangopayUser);
                        }

                        if (($user['sca_status'] ?? "inactive") != ScaStatus::Active->value) {
                            $this->logger->info("User to enroll with SCA");
                            return $this->render('@AppBundle/Onboarding/sca.html.twig');
                        } else {
                            $this->logger->info("User already enrolled with SCA");
                            return $this->peformUserKycChecks(scaEnrolled: true);
                        }
                    } catch (\Exception $exception) {
                        $this->logger->debug("Mangopay user setup failed");
                        return $this->render(
                            '@AppBundle/Onboarding/generic-failure.html.twig',
                            ['response_error' => $exception->getMessage()],
                        );
                    }
                } else {
                    $this->logger->error("Error when updating user", [$response]);
                    $this->addFlash('errors', $response);
                }
            }
        }
        return $this->render(
            '@AppBundle/Onboarding/compliance.html.twig',
            [
                'form' => $form->createView(),
                'flow' => $flow,
                'has_valid_ref' => false,
            ],
        );
    }

    #[Route(path: '/sca-callback', name: 'OB_sca_callback', methods: ['GET'])]
    public function enrollmentCallback(
        #[MapQueryParameter]
        ?string $controlStatus = null,
    ): Response {
        $this->logger->info("IN onboarding SCA enrollment callback");
        $scaOutcome = $this->scaService->isScaSuccess($controlStatus);
        $this->scaService->processScaEnrollmentResult($scaOutcome);

        if ($scaOutcome) {
            $this->logger->debug('SCA enrollment successful');

            // Check if a mangopay wallet needs to be created here
            $this->userService->refreshUserInfo();
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            if (!($userInfo['mangopay_wallet_id'] ?? false)) {
                $this->logger->debug("Creating mangopay wallet for user#" . $userInfo['id']);
                $responseBody = $this->userService->createMangopayWallet();
                if ($responseBody['outcome'] ?? false && $responseBody['outcome'] == 'success') {
                    $this->logger->debug('Mangopay wallet creation success:', [$responseBody]);
                    $this->userService->refreshUserInfo();
                    $userInfo = $this->requestStack->getSession()->get('userInfo');
                    $this->userService->setBalance();
                } else {
                    $this->logger->error('Mangopay wallet creation failed:', [$responseBody]);
                }
            }

            // Perform KYC checks that would've been done in the compliance submission
            return $this->peformUserKycChecks(scaEnrolled: true);
        } else {
            $this->logger->info(
                'SCA enrollment failed',
                ['controlStatus' => $controlStatus],
            );
            return $this->render('@AppBundle/Onboarding/sca-failure.html.twig');
        }
    }

    private function peformUserKycChecks(?bool $scaEnrolled = null): Response
    {
        // Could modify message if after SCA enrollment?
        try {
            $this->logger->debug("Doing Mangopay KYC for user");
            $checkMangopayUser = $this->userService->checkMangopayKYC();
            $this->handleApiResponse($checkMangopayUser);
        } catch (\Throwable $th) {
            /**
             * Redirect to failure onboarding failure message saying (don't bother with flash messages)
             * - SCA enrollment was successful - may want to mentioned you don't need to do it again?
             * - The KYC verification failed with Mangopay, so you'll need to try again
             *   - Link to KYC document requirements in help centre?
             * - If it keeps failing, contact support for help
             */
            $this->logger->error("FAILED Mangopay check for user, ERROR - [" . $th->getMessage() . "]");
            return $this->render(
                '@AppBundle/Onboarding/generic-failure.html.twig',
                ['response_error' => $th->getMessage()],
            );
        }

        try {
            $this->logger->debug("Doing contego KYC for user");
            $contegoKyc = $this->contegoKYCCheck();
            $this->handleApiResponse($contegoKyc);
        } catch (\Throwable $th) {
            $this->logger->error("FAILED Contego check for user, ERROR - [" . $th->getMessage() . "]");
            return $this->render(
                '@AppBundle/Onboarding/generic-failure.html.twig',
                ['response_error' => $th->getMessage()],
            );
        }

        // Both Mangopay and Contego KYC submitted without errors
        $this->crowdTekService->updateUserOBStep(obStep::STEP5_INT);
        $this->client->authenticatedUser()->salesforceSync();
        $this->userService->setBalance();
        $this->userService->refreshUserInfo();
        return $this->redirect($this->generateUrl('Onboarding'));
    }

    private function contegoKYCCheck()
    {
        //Get the user details
        $userData = $this->requestStack->getSession()->get('userInfo');

        //Check to see if user already has contego data in back end
        //Outcome=error if they do not
        $this->logger->debug("Checking if to see if contego data exists in backoffice for user=[" . $userData['email'] . "]");
        $userContegoCheck = $this->userService->contegoCheckOnLatestRef();
        $this->logger->debug("API response for user=[" . $userData['email'] . "], [" . json_encode($userContegoCheck) . "] SUCCESS!!");
        //Can't do handleApiResponse here as we are expecting an error outcome from the back
        //@TODO Change back end to respond with outcome other than error

        if ($userContegoCheck['outcome'] == 'error') {
            //User does'nt have any contego data in backoffice, go and do a new request to contego
            $this->logger->debug("Attempting Contego check for user=[" . $userData['email'] . "]");
            $userContegoCheck = $this->userService->contegoCheck();
            $this->handleApiResponse($userContegoCheck);
        }

        //Now lets check the RAG status
        if ($userContegoCheck['data']['ContegoScore']['rag'] == 'RED') {
            $this->logger->debug("Doing contego Document check for KYC=RED user=[" . $userData['email'] . "]");

            //We want to do the 2nd document pass for the user but we want to send back the first response (RED) as the onboarding status
            $userContegoCheck2ndPass = $this->contegoKYCCheck2ndPass();
        } elseif ($userContegoCheck['data']['ContegoScore']['rag'] == 'AMBER') {
            $this->logger->debug("Doing contego Document check for KYC=AMBER user=[" . $userData['email'] . "]");
            $userContegoCheck = $this->contegoKYCCheck2ndPass();
        } elseif ($userContegoCheck['data']['ContegoScore']['rag'] == 'GREEN') {
            // User can add funds
            // User can invest
            // User can add funds > £1500
            // User can invest > £1500
        } elseif ($userContegoCheck['data']['ContegoScore']['rag'] == 'WAITING') {
            //??? what do do if we get rag waiting, assuming for now we will do same as Red
            $this->logger->debug("Doing contego Document check for KYC=WAITING user=[" . $userData['email'] . "]");
            $userContegoCheck = $this->contegoKYCCheck2ndPass();
        } else {
            //Something unexpected happend
            throw new \Exception("Invalid response from contego API");
        }

        // By this point we should have the contego data for the user
        // Now check Contego KYC if this is a corporate invester
        $corporateInvestor = Util\Util::getInfo($userData, 'corporate_investor', 0);


        /*
         * Do the Company check if investing as a company
         * The USER settings post a company check are the same as a RED contego user even if the user came back as GREEN for individual check
         */
        if ($corporateInvestor) {
            $this->logger->debug("Attempting Contego Company check for user=[" . $userData['email'] . "]");
            $companyContegoCheck = $this->userService->contegoCheckCompany();
            $this->handleApiResponse($companyContegoCheck);

            $this->logger->info("Hiding add funds for corporate user=[" . $userData['email'] . "]");
            $this->requestStack->getSession()->set('hide_add_funds', true);

            //Set the USER contego check rag to RED
            $userContegoCheck['data']['ContegoScore']['rag'] = "RED";

            // User CONNOT add funds
            // User sees 4 hour message
            // User CANNOT invest
            // User CANNOT add funds > £1500
            // User CANNOT invest > £1500
        }

        // At the moment we are only return the contego user check to be used in email to user/admin see CrowdtekService->reviewUserComplianceStatus
        return $userContegoCheck;
    }

    /*
     * This function is called after a contego user has a AMBER or RED status on the initial check
     * We call $this->userService->contegoCheckDoc(); in
     */
    private function contegoKYCCheck2ndPass()
    {
        //Get the user details
        $user = $this->requestStack->getSession()->get('userInfo');

        $this->logger->debug("Doing contego Document check for user=[" . $user['email'] . "]");
        $userContegoDocumentCheck = $this->userService->contegoCheckDoc();
        $this->handleApiResponse($userContegoDocumentCheck);

        //Now lets check the RAG status
        if ($userContegoDocumentCheck['data']['ContegoScore']['rag'] == 'RED') {
            // User CONNOT add funds
            // User sees 4 hour message
            // User CANNOT invest
            // User CANNOT add funds > £1500
            // User CANNOT invest > £1500

            return $userContegoDocumentCheck;
        } elseif ($userContegoDocumentCheck['data']['ContegoScore']['rag'] == 'AMBER') {
            // User can add funds
            // User sees 4 hour message
            // User can invest
            // User CANNOT add funds > £1500
            // User CANNOT invest > £1500

            return $userContegoDocumentCheck;
        } elseif ($userContegoDocumentCheck['data']['ContegoScore']['rag'] == 'GREEN') {
            // User can add funds
            // User can invest
            // User can add funds > £1500
            // User can invest > £1500

            return $userContegoDocumentCheck;
        } elseif ($userContegoDocumentCheck['data']['ContegoScore']['rag'] == 'WAITING') {
            //As per https://gitlab.com/yielders2/business/issues/394 WAITING user is to be treated as AMBER
            // User can add funds
            // User sees 4 hour message
            // User can invest
            // User CANNOT add funds > £1500
            // User CANNOT invest > £1500

            return $userContegoDocumentCheck;
        } else {
            //Something unexpected happend
            throw new \Exception("Invalid response from contego API");
        }
    }

    /**
     * Helper for APIv1 request responses
     */
    private function handleApiResponse($apiResponse)
    {
        //Get the user details
        $userData = $this->requestStack->getSession()->get('userInfo');

        if (empty($apiResponse['outcome'])) {
            //$this->requestStack->getSession()->getFlashBag()->add('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }

        if ($apiResponse['outcome'] == 'fail') {
            //$this->requestStack->getSession()->getFlashBag()->add('errors', $apiResponse['data']['user_message']);
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], [" . json_encode($apiResponse) . "]");
            //return $this->render('@AppBundle/Onboarding/generic-failure.html.twig', array('response_error' => $contegoRes['data']['user_message']));

            throw new \Exception($apiResponse['data']['user_message']);
        } elseif ($apiResponse['outcome'] == 'success') {
            $this->logger->debug("SUCCESS API response for user=[" . $userData['email'] . "], [" . json_encode($apiResponse) . "]");
        } else {
            //Somethimg unexpected happened
            //$this->requestStack->getSession()->getFlashBag()->add('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }
    }

    /**
     * @Route("/complete", name="OBcomplete")
     */
    public function completeAction(ScaService $scaService): Response
    {
        // Check user is logged in
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            return $this->redirectToRoute('login');
        }

        $user = $this->requestStack->getSession()->get('userInfo');

        $obStep = $user["ob_step"];

        if ($obStep != 5) {
            return $this->redirectToRoute('Onboarding');
        }
        return $this->render('@AppBundle/Onboarding/onboarding-complete.html.twig');
    }

    /**
     * @Route("/resend-email-verification", name="resend_email_verification")
     */
    public function resendEmailVerificationAction(): Response
    {
        $userData = $this->requestStack->getSession()->get('userInfo');

        $email = $userData['email'];
        $url = $this->generateUrl('verify_email', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $response = $this->crowdTekService->resendEmailVerification($url, $email);

        if ($response == 1) {
            $this->addFlash('info', "Email resent");
            return $this->redirect($this->generateUrl('Onboarding'));
        } else {
            $this->addFlash('errors', $response);
            return $this->redirect($this->generateUrl('Onboarding'));
        }
    }

    /**
     * @Route("/setAmount", name="setAmount")
     */
    public function setAmount(Request $request): Response
    {
        $amount = $request->request->get('amount');
        $this->requestStack->getSession()->set('tempAmount', $amount);

        $this->logger->error("====================================================");
        $this->logger->error(json_encode($amount));
        $this->logger->error("====================================================");


        //        $amount = $request->request->get('amount');
        //        $this->requestStack->getSession()->set('tempAmount', $amount);


        if ($this->requestStack->getSession()->get('tempAmount') == $amount) {
            $info = "amount has been set";
            return new JsonResponse($info);
        }

        return $this->render('@AppBundle/Onboarding/onboarding-complete.html.twig');
    }

    public function uploadDocuments($userDocs)
    {
        // proof of ID Document
        /** @var UploadedFile $POI */
        $POI = $userDocs['proof_of_id'];
        $POIFileName = $POI->getClientOriginalName();
        $POIExtension = pathinfo($POIFileName, PATHINFO_EXTENSION);

        // proof of Address Document
        /** @var UploadedFile $POA */
        $POA = $userDocs['proof_of_address'];
        $POAFileName = $POA->getClientOriginalName();
        $POAExtension = pathinfo($POAFileName, PATHINFO_EXTENSION);

        // proof of Business Document
        /** @var UploadedFile $POB */
        $POB = $userDocs['proof_of_business'];
        if ($POB) {
            $POBFileName = $POB->getClientOriginalName();
            $POBExtension = pathinfo($POBFileName, PATHINFO_EXTENSION);
        }
        ;

        $allowedExtensions = ["pdf", "jpeg", "jpg", "png"];

        if (isset($POI)) {
            $this->logger->debug('FILE NAME:' . $POI->getClientOriginalName());
            $this->logger->debug('FILE NAME:' . $POI->getFilename());
            $this->logger->debug('FILE EXT:' . $POI->guessExtension());

            $this->logger->debug('User had loaded up a POI');

            //Convert document to byte array
            $byteArray = file_get_contents($POI);

            $this->logger->debug('preparing to build a document request for POI');
            $this->logger->info('POI file extension:   ' . $POIExtension);

            if (in_array(strtolower($POIExtension), $allowedExtensions)) {
                $fileData = [
                    'file_name' => $POI->getClientOriginalName(),
                    'file_type' => $POI->getMimeType(),
                    'document_content' => base64_encode($byteArray),
                    'tag' => 'proof_of_identity',

                ];

                $res = $this->documentService->create($fileData);
            } else {
                $this->logger->error("POI upload error, unsupported file type detected: " . $POIExtension);
                throw new \Exception("You have uploaded an unsupported file type for your proof of identity. We currently only accept the following file types: JPEG, PNG, PDF");
            }
            ;

            if (empty($res['outcome']) || $res['outcome'] == 'fail') {
                $this->logger->error("POI upload error: " . json_encode($res));
                throw new \Exception("Document upload error: unable to save document");
            }
        }

        if (isset($POA)) {
            $this->logger->debug('FILE NAME:' . $POA->getClientOriginalName());
            $this->logger->debug('FILE NAME:' . $POA->getFilename());
            $this->logger->debug('FILE EXT:' . $POA->guessExtension());

            $this->logger->debug('User had loaded up a POA');

            //Convert document to byte array
            $byteArray = file_get_contents($POA);

            $this->logger->debug('preparing to build a document request for POA');
            $this->logger->info('POA file extension:' . $POAExtension);

            if (in_array(strtolower($POAExtension), $allowedExtensions)) {
                $fileData = [
                    'file_name' => $POA->getClientOriginalName(),
                    'file_type' => $POA->getMimeType(),
                    'document_content' => base64_encode($byteArray),
                    'tag' => 'proof_of_address',

                ];

                $res = $this->documentService->create($fileData);
            } else {
                $this->logger->error("POA upload error, unsupported file type detected: " . $POAExtension);
                throw new \Exception("You have uploaded an unsupported file type for your proof of address. We currently only accept the following file types: JPEG, PNG, PDF");
            }

            if (empty($res['outcome']) || $res['outcome'] == 'fail') {
                $this->logger->error("POA upload error: " . json_encode($res));
                throw new \Exception("Document upload error: unable to save document");
            }
        }

        if (isset($POB)) {
            $this->logger->debug('FILE NAME:' . $POB->getClientOriginalName());
            $this->logger->debug('FILE NAME:' . $POB->getFilename());
            $this->logger->debug('FILE EXT:' . $POB->guessExtension());

            $this->logger->debug('User had loaded up a POB');

            //Convert document to byte array
            $byteArray = file_get_contents($POB);

            $this->logger->debug('preparing to build a document request for POB');
            $this->logger->info('POB file extension:' . $POBExtension);

            if (in_array(strtolower($POBExtension), $allowedExtensions)) {
                $fileData = [
                    'file_name' => $POB->getClientOriginalName(),
                    'file_type' => $POB->getMimeType(),
                    'document_content' => base64_encode($byteArray),
                    'tag' => 'proof_of_business',
                ];

                $res = $this->documentService->create($fileData);
            } else {
                $this->logger->error("POB upload error, unsupported file type detected: " . $POBExtension);
                throw new \Exception("You have uploaded an unsupported file type for your proof of business. We currently only accept the following file types: JPEG, PNG, PDF");
            }


            if (empty($res['outcome']) || $res['outcome'] == 'fail') {
                $this->logger->error("POB upload error: " . json_encode($res));
                throw new \Exception("Document upload error: unable to save document");
            }
        }
    }

    /**
     * Log user in and save in session
     *
     * @throws \Exception
     */
    public function logUserIn(Request $request, User $user): Response
    {
        try {
            $authentication = $this->publicService->authenticate($this->network, $user->getEmail(), $user->getPassword());
            //check if user is not block
            $request->headers->set('Authorization', 'Bearer ' . $authentication['access_token']);
            $this->requestStack->getSession()->set('jwt_token', $authentication['access_token']);

            // Sync on login during onboarding - usually only after initial sign-up
            $this->logger->debug('Syncing with salesforce');
            // Note that the token must be manually set as the Yielders API client
            // will be using the anonymous token in the current request during sign up
            $this->client->authenticatedUser()->salesforceSync([
                'headers' => ['Authorization' => 'Bearer ' . $this->requestStack->getSession()->get('jwt_token')],
                'json' => [],
            ]);

            $userRes = $this->userService->getUserInfo();
            if (isset($userRes['outcome']) && $userRes['outcome'] == 'error') {
                $this->addFlash('errors', $userRes['data']['user_message']);
                return $this->redirect($this->generateUrl('homepage'));
            } else {
                $this->requestStack->getSession()->set('authenticated', true);
                $this->requestStack->getSession()->set('userInfo', $userRes);

                return $this->redirect($this->generateUrl('homepage'));
            }
        } catch (\Exception $e) {
            $this->addFlash('errors', 'Logging User in error: ' . $authentication);
            return $this->redirect($this->generateUrl('homepage'));
        }
    }

    /**
     * Get self user data and update session
     *
     * @throws \Exception
     */
    public function getSelfUserUpdate(): Response
    {
        //        $authentication = $this->publicService->authenticate($this->network, $user->getEmail(), $user->getPassword());

        //if (!empty($authentication['outcome']) && $authentication['outcome'] == 'success') {
        if ($this->requestStack->getSession()->get('authenticated') == 1) {
            $userRes = $this->userService->getUserInfo();
            if (isset($userRes['outcome']) && $userRes['outcome'] == 'error') {
                $this->addFlash('errors', $userRes['data']['user_message']);
                return $this->redirect($this->generateUrl('homepage'));
            } else {
                $this->requestStack->getSession()->set('authenticated', true);
                $this->requestStack->getSession()->set('userInfo', $userRes);

                return $this->redirect($this->generateUrl('homepage'));
            }
        } else {
            $this->addFlash('errors', 'User not found in session so could not get SELF User');
            return $this->redirect($this->generateUrl('homepage'));
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

    # get success response from recaptcha and return it to controller
    public function captchaverify($recaptcha)
    {
        $this->logger->debug("In  captchaverify ========== ");


        $url = "https://www.google.com/recaptcha/api/siteverify";
        $secret = $this->recaptchaSecret;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            "secret" => $secret,
            "response" => $recaptcha,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response);


        $this->logger->debug("DATA ================ " . json_encode($data));

        return $data->success;
    }
}
