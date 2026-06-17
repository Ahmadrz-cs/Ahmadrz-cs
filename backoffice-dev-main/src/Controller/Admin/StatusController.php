<?php

namespace App\Controller\Admin;

use App\Message\DebugLog;
use App\Service\StatusCheckerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/status')]
#[IsGranted('ROLE_ANALYST')]
final class StatusController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private TagAwareCacheInterface $defaultAppCache,
        private StatusCheckerService $statusCheckerService,
    ) {}

    #[Route('', name: 'admin_service_status', methods: ['GET'])]
    public function serviceStatus(): Response
    {
        $this->logger->info(
            'Get service statuses - pulls from cache or refresh if unavailable',
        );

        try {
            $mangopayStatus = $this->statusCheckerService->getMangopayStatus();
        } catch (\Exception $e) {
            $this->logger->error('Mangopay status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $contegoStatus = $this->statusCheckerService->getContegoStatus();
        } catch (\Exception $e) {
            $this->logger->error('Contego status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $salesforceStatus = $this->statusCheckerService->getSalesforceStatus();
        } catch (\Exception $e) {
            $this->logger->error('Salesforce status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $mailchimpStatus = $this->statusCheckerService->getMailchimpStatus();
        } catch (\Exception $e) {
            $this->logger->error('Mailchimp status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $publicDocstoreStatus =
                $this->statusCheckerService->getPublicDocumentStorageStatus();
        } catch (\Exception $e) {
            $this->logger->error('Docstore status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $privateDocstoreStatus =
                $this->statusCheckerService->getPrivateDocumentStorageStatus();
        } catch (\Exception $e) {
            $this->logger->error('Docstore status error ' . $e->getMessage());
            $this->addFlash('error', $e->getMessage());
        }

        // Flash message debugging
        // foreach (['debug', 'info', 'notice', 'success', 'warning', 'secondary', 'error', 'primary'] as $flashType) {
        //     $this->addFlash($flashType, "A message of type '{$flashType}' and a bunch of other mumbo jumbo");
        // }

        return $this->render('admin/pages/maintenance/service_status.html.twig', [
            'memoryLimit' => ini_get('memory_limit'),
            'executionTimeLimit' => ini_get('max_execution_time'),
            'mangopayStatus' => $mangopayStatus ?? null,
            'contegoStatus' => $contegoStatus ?? null,
            'salesforceStatus' => $salesforceStatus ?? null,
            'mailchimpStatus' => $mailchimpStatus ?? null,
            'publicDocstoreStatus' => $publicDocstoreStatus ?? null,
            'privateDocstoreStatus' => $privateDocstoreStatus ?? null,
        ]);
    }

    #[Route(
        '/mangopay',
        name: 'admin_service_status_refresh_mangopay',
        methods: ['GET'],
    )]
    public function mangopayStatusCheck(): Response
    {
        $this->logger->info('Refresh mangopay service status');
        $this->defaultAppCache->delete('mangopayStatus');
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route('/contego', name: 'admin_service_status_refresh_contego', methods: ['GET'])]
    public function contegoStatusCheck(): Response
    {
        $this->logger->info('Refresh contego service status');
        $this->defaultAppCache->delete('contegoStatus');
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route(
        '/salesforce',
        name: 'admin_service_status_refresh_salesforce',
        methods: ['GET'],
    )]
    public function salesforceStatusCheck(): Response
    {
        $this->logger->info('Refresh salesforce service status');
        $this->defaultAppCache->delete('salesforceStatus');
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route(
        '/mailchimp',
        name: 'admin_service_status_refresh_mailchimp',
        methods: ['GET'],
    )]
    public function mailchimpStatusCheck(): Response
    {
        $this->logger->info('Refresh mailchimp service status');
        $this->defaultAppCache->delete('mailchimpStatus');
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route(
        '/docstore',
        name: 'admin_service_status_refresh_docstore',
        methods: ['GET'],
    )]
    public function docStoreStatusCheck(): Response
    {
        $this->logger->info('Refresh docstore service status');
        $this->defaultAppCache->delete('docstorePublicStatus');
        $this->defaultAppCache->delete('docstorePrivateStatus');
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route('/all', name: 'admin_service_status_refresh_all', methods: ['GET'])]
    public function refreshAll(): Response
    {
        $this->logger->info('Refreshing all service statuses');
        $this->defaultAppCache->invalidateTags([
            StatusCheckerService::DEFAULT_CACHE_TAG,
        ]);
        return $this->redirectToRoute('admin_service_status');
    }

    #[Route(
        '/debug-log',
        name: 'admin_service_status_debug_log',
        methods: ['GET', 'POST'],
    )]
    public function debugLog(
        Request $request,
        MessageBusInterface $bus,
        TransportInterface $async,
    ): Response {
        $form = $this
            ->createFormBuilder()
            ->add('message', TextType::class, [
                'help' => 'Message to write to debug log',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Submit Message'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $bus->dispatch(new DebugLog($form->getData()['message']));
            $this->addFlash('success', 'Debug log request successfully submitted');
        }
        if ($async instanceof MessageCountAwareInterface) {
            $messageCount = $async->getMessageCount();
        }
        return $this->render('admin/pages/maintenance/debug_log.html.twig', [
            'form' => $form,
            'messageCount' => $messageCount ?? null,
        ]);
    }
}
