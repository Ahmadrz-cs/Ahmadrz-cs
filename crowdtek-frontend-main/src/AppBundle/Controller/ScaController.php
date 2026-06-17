<?php

namespace AppBundle\Controller;

use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/sca')]
class ScaController extends AbstractController
{
    public const array MANGOPAY_SCA_URLS = [
        'sandbox' => 'https://sca.sandbox.mangopay.com',
        'prod' => 'https://sca.mangopay.com',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private ScaService $scaService,
        private UserService $userService,
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
    }

    #[Route(path: '/enrollment', name: 'sca_enrollment', methods: ['GET'])]
    public function enrollment(#[MapQueryParameter] ?string $flow = null): Response
    {
        $this->logger->info("IN SCA enrollment");
        $this->userService->refreshUserInfo();
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        if (!$this->scaService->canScaEnroll($userInfo)) {
            $this->logger->debug("User does not need to be enrolled in SCA");
            $this->addFlash('info', 'You are not eligible for SCA enrollment at this moment.');
            return $this->redirectToRoute('profile');
        }
        $returnRoute = $flow == "onboarding" ? "OB_sca_callback" : "sca_enrollment_callback";
        $scaEnrollmentUrl = $this->scaService->startScaEnrollmentSession($this->router->generate(
            name: $returnRoute,
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        ));
        $this->logger->debug($scaEnrollmentUrl);

        if (
            str_contains($scaEnrollmentUrl, self::MANGOPAY_SCA_URLS['sandbox'])
            || str_contains($scaEnrollmentUrl, self::MANGOPAY_SCA_URLS['prod'])
        ) {
            return $this->redirect($scaEnrollmentUrl);
        } else {
            $this->addFlash('error', 'Unable to start new SCA enrollment session.');
            return $this->redirectToRoute('profile');
        }
    }

    #[Route(path: '/enrollment/callback', name: 'sca_enrollment_callback', methods: ['GET'])]
    public function enrollmentCallback(
        #[MapQueryParameter]
        ?string $controlStatus = null,
    ): Response {
        $this->logger->info("IN SCA enrollment callback");
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

            if ($userInfo['mangopay_wallet_id'] ?? false) {
                $this->addFlash(
                    'success',
                    'SCA enrollment completed. Full e-wallet functionality is now available.'
                );
            } else {
                $this->addFlash(
                    'success',
                    'SCA enrollment completed, but unable to setup e-wallet. Please contact support for assistance.'
                );
            }
        } else {
            $this->logger->info(
                'SCA enrollment failed',
                ['controlStatus' => $controlStatus]
            );
            $this->addFlash('error', 'SCA enrollment failed. Please try again or contact support.');
        }
        return $this->redirectToRoute('profile');
    }

    // #[Route(path: '/error', name: 'verification_error', methods: ['GET'])]
    // public function error(): Response
    // {
    //     $this->logger->info("IN verification error");
    //     return $this->render('@AppBundle/Verifications/error.html.twig');
    // }
}
