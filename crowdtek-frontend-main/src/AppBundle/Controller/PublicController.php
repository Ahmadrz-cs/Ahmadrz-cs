<?php

namespace AppBundle\Controller;

use AppBundle\Util\Util;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PublicService;
use ClientBundle\Service\UserService;
use ClientBundle\Service\VerificationService;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PublicController extends AbstractController
{
    private array $params = [];

    public function __construct(
        private LoggerInterface $logger,
        private ApiClient $client,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private PublicService $publicService,
        private UserService $userService,
        private string $network,
    ) {}

    /**
     * @Route("/healthcheck", name="healthcheck")
     */
    public function healthcheck(): Response
    {
        return new Response();
    }

    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request): Response
    {
        $this->logger->info("==================IN loginAction=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if ($authenticated) {
            // $this->addFlash('errors', "You're already logged in");
            return $this->redirectToRoute('homepage');
        }

        /**
         * Generate url for logging in
         * Redirect to that generated url
         * Separate callback url that saves everything to session
         */

        // $token = $this->requestStack->getSession()->set('auth_csrf', $this->csrfTokenManager->getToken('auth_csrf'));
        $token = $this->csrfTokenManager->getToken('auth_csrf');
        $authUrl = $this->publicService->getAuthCodeUrl($token->getValue());

        return $this->redirect($authUrl);
    }


    /**
     * @Route("/auth/callback", name="auth_callback")
     */
    public function authCallbackAction(
        Request $request,
        OnboardingService $onboardingService,
        VerificationService $verificationService,
    ): Response {
        $this->logger->info("==================IN authCallbackAction=====================");

        $code = $request->query->get('code');
        $state = $request->query->get('state');

        if ($this->isCsrfTokenValid('auth_csrf', $state)) {
            try {
                $authentication = $this->publicService->getAccessTokenWithAuthCode($code);
                // $this->logger->info(json_encode($authentication));
                if (!isset($authentication['access_token'])) {
                    throw new \Exception('Error logging in');
                }
                //check if user is not block
                $request->headers->set('Authorization', 'Bearer ' . $authentication['access_token']);
                $this->requestStack->getSession()->set('jwt_token', $authentication['access_token']);
                $this->requestStack->getSession()->set('refresh_token', $authentication['refresh_token']);

                $userRes = $this->userService->getUserInfo();
                if (isset($userRes['outcome']) && $userRes['outcome'] == 'error') {
                    $this->addFlash('errors', $userRes['data']['user_message']);
                    return $this->redirect($this->generateUrl('homepage'));
                } else {
                    $this->requestStack->getSession()->set('authenticated', true);
                    $this->requestStack->getSession()->set('userInfo', $userRes);

                    $this->userService->setBalance(true);

                    if (isset($userRes['registration_complete'])) {
                        if ($userRes['registration_complete'] == true) {
                            $this->requestStack->getSession()->set('ob_complete', true);
                        } else {
                            $this->requestStack->getSession()->set('ob_complete', false);
                        }
                    }

                    if ($this->requestStack->getSession()->has('ob_complete') && $this->requestStack->getSession()->get('ob_complete') == false) {
                        //    $this->addFlash('errors','You have not completed your Sign Up, please complete your Sign Up in order to invest');
                        return $this->redirect($this->generateUrl('Onboarding'));
                    }

                    // sync MPwallet balance with Salesforce - requires user to be onboarded before doing sync
                    // $userInfo = Util::getUserInfoArray($userRes['info']);
                    $mpBalance = str_replace(' ', '', $this->requestStack->getSession()->get('balance'));

                    // $syncInfo = [
                    //     "MPWalletBalance__c" => $mpBalance,
                    //     "last_login__c" => date(DATE_ATOM, time())
                    // ];

                    try {
                        $this->logger->debug('Syncing with salesforce: ', ["extraFields" => ["MPWalletBalance__c" => $mpBalance]]);
                        // Note that the token must be manually set as the Yielders API client
                        // will be using the anonymous token in the current request during login
                        $this->client->authenticatedUser()->salesforceSync([
                            'headers' => ['Authorization' => 'Bearer ' . $this->requestStack->getSession()->get('jwt_token')],
                            'json' => ["extraFields" => ["MPWalletBalance__c" => $mpBalance]],
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error("Unable to update Salesforce object with wallet amount at login. " . $e);
                    }
                    if ($onboardingService->needsCheckup($this->requestStack->getSession()->get('userInfo'))) {
                        $obp = $onboardingService->getOnboardingProfileFromSession();
                        $nextStep = $onboardingService->getNextStep($obp);
                        $currentDt = new \DateTime();
                        if ($nextStep == 'checkup_risk' && !($currentDt > $obp->cooloffEnd)) {
                            return $this->redirectToRoute('homepage');
                        }
                        return $this->redirectToRoute('checkup_index');
                    }
                    if ($verificationService->needsIdentityVerification()) {
                        return $this->redirectToRoute('verification_index');
                    }

                    return $this->redirect($this->generateUrl('homepage'));
                }
            } catch (\Exception $e) {
                // if exception caught after authentication set - logout
                if ($this->requestStack->getSession()->get('authenticated')) {
                    $this->requestStack->getSession()->set('authenticated', false);
                    $this->tokenStorage->setToken(null);
                    $this->requestStack->getSession()->invalidate();
                }
                if ($authentication['message'] == 'Account is disabled.') {
                    $this->addFlash('errors', 'Your account is currently unavailable. Please contact the team on <a href="mailto:team@yielders.co.uk">team@yielders.co.uk</a> for further assistance.');
                    return $this->redirect($this->generateUrl('login'));
                } else {
                    $this->addFlash('errors', 'Username or Password is not correct, please try again.');
                    return $this->redirect($this->generateUrl('login'));
                }
            }
        }

        // clear auth_csrf token from session now we're done with it
        $this->requestStack->getSession()->remove('auth_csrf');
        return $this->redirect($this->generateUrl('homepage'));
    }


    /**
     * Logout
     *
     * @Route("/logout", name="logout")
     */
    public function signOutAction(Request $request): Response
    {
        $this->logger->info("==================IN signOutAction=====================");

        $required = $request->query->get('required', '');
        if ($this->requestStack->getSession()->has('authenticated') && $this->requestStack->getSession()->get('authenticated')) {
            $this->requestStack->getSession()->set('authenticated', false);
            $this->tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            return $this->redirect($this->publicService->getOauthLogoutUrl());
        }

        if ($required === 'login') {
            return $this->redirectToRoute('login');
        } else {
            return $this->redirectToRoute('homepage');
        }
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/verify-email", name="verify_email")
     */
    public function verifyEmailAction(Request $request): Response
    {
        $this->logger->info("==================IN verifyEmailAction=====================");

        $network = $this->network;

        $id = $request->get('id');
        $signedUrl = $request->getUri();

        $params = [
            'id' => $id,
            'signedUrl' => $signedUrl,
        ];
        $response = $this->publicService->verifyEmail($network, $params);
        if (!empty($response['outcome']) && $response['outcome'] == 'success') {
            $authenticated = $this->requestStack->getSession()->get('authenticated');
            if ($authenticated) {
                $this->addFlash('info', 'Thank you for verifying your email address, please complete your registration');
            }
            return $this->redirect($this->generateUrl('Onboarding', ['verify_email' => 1]));
        } else {
            $this->addFlash('errors', $response['data']['user_message']);
            return $this->redirect($this->generateUrl('homepage'));
        }
    }

    // /**
    //  * @Route("/put-s3-url", name="put_s3aws")
    //  *
    //  * @return Response
    //  */
    // // Is this route ever used?
    // public function s3AwsAction(Request $request)
    // {
    //     $this->logger->info("==================IN s3AwsAction=====================");

    //     $fileName = $request->get('name');
    //     $fileType = $request->get('type');

    //     //dump($request);

    //     //$this->logger->info($request);


    //     $fileObject = Util::alphaNumCodeGenerator();
    //     $parameters = ['file_name' => $fileName, 'file_type' => $fileType, 'file_object' => $fileObject];
    //     $ret = $this->publicService->getS3URL($this->network, $parameters);

    //     // CT LOGGING CHANGE.
    //     $this->logger->error($ret);
    //     $kernelRootDir = $this->container->getParameter('upload_dir');
    //     // CT LOGGING CHANGE.
    //     $this->logger->info($kernelRootDir);

    //     return new Response($ret['url']['url'], 200);

    //     //return new Response('http://web/uploads', 200);
    // }

    /**
     * resend verify email
     *
     * @Route("/resend-verify-email", name="resend_verify")
     */
    public function resendVerifyEmailAction(): Response
    {
        $this->logger->info("==================IN resendVerifyEmailAction=====================");

        $user = $this->userService->getUserInfo();
        $url = $this->generateUrl('verify_email', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $data = ['url' => $url, 'email' => $user['email']];
        $res = $this->userService->resendVerifyEmail($data);
        if (!empty($res['outcome']) && $res['outcome'] == 'success') {
            $this->addFlash('info', 'We have re-sent the verify email, please check your inbox.');
        } else {
            $this->addFlash('errors', $res['data']['user_message']);
        }
        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/forgot-password", name="forgot_password")
     */
    public function forgotPasswordAction(Request $request): Response
    {
        $this->logger->info("==================IN forgotPasswordAction=====================");

        $network = $this->network;
        $form = $this->createFormBuilder()
            ->add('email', TextType::class, ['required' => true])
            ->add('submit', SubmitType::class, [])
            ->getForm();
        $errorCode = '';
        $result = '';

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $forgotPwdData = $form->getData();
            $url = $this->generateUrl('app_reset_pwd', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $urlArray = ['url' => $url];
            $forgotPwdData = array_merge($forgotPwdData, $urlArray);
            $api2 = $this->publicService->forgotPwd($network, $forgotPwdData);

            if ($api2['outcome'] == 'success') {
                $result = 'success';
            } else {
                $errorCode = $api2['data']['user_message'];
            }
        }
        $this->params['menu_item'] = 'forgot_password';
        $this->params['form'] = $form->createView();
        $this->params['error_code'] = $errorCode;
        $this->params['result'] = $result;
        return $this->render('@AppBundle/Public/forgot_password.html.twig', $this->params);
    }

    /**
     * @Route("/reset-pwd", name="app_reset_pwd")
     */
    public function resetPwdAction(Request $request): Response
    {
        $this->logger->info("==================IN resetPwdAction=====================");

        $network = $this->network;
        $token = $request->get('token');
        $form = $this->createFormBuilder()
            ->add('password', PasswordType::class, ['required' => true])
            ->add('password_confirm', PasswordType::class, ['required' => true])
            ->add('submit', SubmitType::class, [])
            ->getForm();
        $errorCode = '';
        $result = '';

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $resetPwdData = $form->getData();
            $parameters = ['token' => $token];
            $resetPwdData = array_merge($resetPwdData, $parameters);

            $api2 = $this->publicService->resetPwd($network, $resetPwdData);

            if ($api2['outcome'] == 'success') {
                $result = 'success';
            } else {
                $errorCode = $api2['data']['user_message'];
            }
        }
        return $this->render('@AppBundle/Public/reset_pwd.html.twig', [
            'form' => $form->createView(),
            'error_code' => $errorCode,
            'result' => $result,
        ]);
    }
}
