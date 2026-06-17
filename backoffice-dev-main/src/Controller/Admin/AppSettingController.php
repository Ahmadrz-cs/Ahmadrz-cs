<?php

namespace App\Controller\Admin;

use App\Entity\Enum\ScaStatus;
use App\Form\AppSettingForm;
use App\Service\AppSettingService;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayScaService;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/settings')]
#[IsGranted('ROLE_TECH_OPS')]
final class AppSettingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AppSettingService $appSettingService,
    ) {}

    #[Route('', name: 'admin_app_setting', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->logger->info('Loading app settings config');
        $appSettings = $this->appSettingService->getMultipleRaw();
        $form = $this->createForm(
            AppSettingForm::class,
            $this->appSettingService->convertToKv($appSettings),
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Updating app settings config');
            $missingSettings = $this->appSettingService->setup();
            if ($missingSettings !== []) {
                $this->logger->info('Set up missing settings: ', $missingSettings);
                $this->addFlash(
                    'notice',
                    'Set up the following missing app settings: '
                        . json_encode($missingSettings),
                );
            }
            $this->appSettingService->setMultiple($form->getData());
            $this->addFlash('success', 'App settings successfully updated');
            // Refresh the list of app settings after saving
            $appSettings = $this->appSettingService->getMultipleRaw();
        }
        return $this->render('admin/pages/settings/config.html.twig', [
            'form' => $form,
            'appSettings' => $appSettings,
        ]);
    }

    #[Route(
        '/mangopay-client',
        name: 'admin_app_setting_mangopay_client',
        methods: ['GET'],
    )]
    public function mangopayClient(MangopayWalletService $walletService): Response
    {
        $this->logger->info('Loading app settings - Mangopay Client');

        try {
            $client = $walletService->retrieveClient();
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Unable to retrieve Mangopay client.', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Unable to retrieve Mangopay client. ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->addFlash(
                'error',
                'Unable to retrieve Mangopay client.. ' . $e->getMessage(),
            );
            $this->logger->error('Unable to retrieve Mangopay client. ', [$e->getMessage()]);
        }

        return $this->render('admin/pages/settings/mangopay_client.html.twig', [
            'client' => $client ?? null,
        ]);
    }

    #[Route(
        '/superadmin/mangopay-sca',
        name: 'admin_app_setting_superadmin_sca',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function superadminSca(
        UserManagerV2 $userManager,
        MangopayWalletService $walletService,
        MangopayScaService $mangopayScaService,
        #[MapQueryParameter] bool $startSession = false,
    ): Response {
        $this->logger->info('Loading app settings config - Superadmin Mangopay SCA');

        $superadmin = $userManager->getSuperAdmin();

        try {
            if ($startSession) {
                $action = 'start Mangopay SCA or proxy consent session';
                if ($superadmin->getScaStatus() == ScaStatus::Active) {
                    $this->logger->info(
                        'Starting superadmin Mangopay proxy consent session',
                    );
                    $scaSessionResponse = $walletService->manageUserScaConsent($superadmin->getMangoPayUserId());
                } else {
                    $this->logger->info('Starting superadmin Mangopay SCA enrollment');
                    $scaSessionResponse = $walletService->enrollUserSca($superadmin->getMangoPayUserId());

                    // Note that enrolment outcome is handled by webhook in src/Controller/Webhooks/MangopayController.php
                }
                $returnUrl = $this->generateUrl(
                    route: 'admin_app_setting_superadmin_sca',
                    parameters: [],
                    referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
                );
                return $this->redirect($mangopayScaService->getScaSessionUrl(
                    $scaSessionResponse,
                    $returnUrl,
                ));
            }
            $action = 'load Mangopay SCA statuses';
            $scaStatus = $walletService->retrieveScaStatus($superadmin->getMangoPayUserId());
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error(
                "Unable to {$action}.",
                [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ],
            );
            $this->addFlash(
                'error',
                "Unable to {$action}. " . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->addFlash('error', "Unable to {$action}. " . $e->getMessage());
            $this->logger->error("Unable to {$action}. ", [$e->getMessage()]);
        }

        return $this->render('admin/pages/settings/superadmin_sca.html.twig', [
            'user' => $superadmin,
            'scaStatus' => $scaStatus ?? null,
        ]);
    }
}
