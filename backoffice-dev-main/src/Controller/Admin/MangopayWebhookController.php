<?php

namespace App\Controller\Admin;

use App\Form\Type\MangopayHookType;
use App\Service\Mangopay\MangopayWebhookService;
use MangoPay\Hook;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/webhooks/mangopay')]
#[IsGranted('ROLE_TECH_OPS')]
class MangopayWebhookController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWebhookService $mangopayWebhookService,
    ) {}

    #[Route('', name: 'admin_webhooks_mangopay_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Showing Mangopay Webhooks');
        try {
            $pagination = new \MangoPay\Pagination($request->query->get('page', 1), 10);
            $results = $this->mangopayWebhookService->listHooks($pagination, [
                'CreationDate' => 'DESC',
            ]);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving Mangopay hooks', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error loading Mangopay hooks ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mangopay hooks', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error loading Mangopay hooks ' . $e->getMessage(),
            );
        }
        // Clamp pagination for rendering
        $pagination->Page = min($pagination->Page ?? 1, $pagination->TotalPages);
        return $this->render('admin/pages/webhooks/mangopay_index.html.twig', [
            'results' => $results ?? [],
            'pagination' => $pagination,
        ]);
    }

    #[Route(
        '/create',
        name: 'admin_webhooks_mangopay_create',
        methods: ['GET', 'POST'],
    )]
    public function create(Request $request): Response
    {
        $hook = new Hook();
        $hook->Status = \MangoPay\HookStatus::Enabled;
        $form = $this->createForm(MangopayHookType::class, $hook, ['create' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->debug('Creating new Mangopay Webhook');
                $result = $this->mangopayWebhookService->createHook($hook);
                $this->addFlash('success', 'Mangopay hook successfully created');
                return $this->redirectToRoute('admin_webhooks_mangopay_edit', [
                    'hookId' => $result->Id,
                ]);
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error creating Mangopay hooks', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay hook ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Error creating Mangopay hooks', [$e->getMessage()]);
                $this->addFlash(
                    'error',
                    'Error creating Mangopay hook ' . $e->getMessage(),
                );
            }
        }
        return $this->render('admin/pages/webhooks/mangopay_create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/{hookId}',
        name: 'admin_webhooks_mangopay_edit',
        methods: ['GET', 'POST'],
    )]
    public function edit(Request $request, string $hookId): Response
    {
        try {
            $hook = $this->mangopayWebhookService->retrieveHook($hookId);
            $form = $this->createForm(MangopayHookType::class, $hook);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $this->logger->debug('Updating Mangopay Webhook', [
                        $hook->Id,
                        $hook->EventType,
                    ]);
                    $result = $this->mangopayWebhookService->updateHook($hook);
                    $this->addFlash('success', 'Mangopay hook successfully updated');
                    return $this->redirectToRoute('admin_webhooks_mangopay_edit', [
                        'hookId' => $result->Id,
                    ]);
                } catch (\MangoPay\Libraries\ResponseException $e) {
                    $this->logger->error('Error updating Mangopay hook', [
                        $e->GetCode(),
                        $e->getMessage(),
                        $e->GetErrorDetails(),
                    ]);
                    $this->addFlash(
                        'error',
                        'Error retrieving Mangopay hook ' . $e->getMessage() . '. '
                            . $e->GetErrorDetails(),
                    );
                } catch (\Exception $e) {
                    $this->logger->error('Error updating Mangopay hook', [$e->getMessage()]);
                    $this->addFlash(
                        'error',
                        'Error updating Mangopay hook ' . $e->getMessage(),
                    );
                }
            }
            return $this->render('admin/pages/webhooks/mangopay_edit.html.twig', [
                'form' => $form->createView(),
                'hook' => $hook,
            ]);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving Mangopay hook', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error retrieving Mangopay hook ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving Mangopay hook', [$e->getMessage()]);
            $this->addFlash(
                'error',
                'Error retrieving Mangopay hook ' . $e->getMessage(),
            );
        }
        return $this->redirectToRoute('admin_webhooks_mangopay_index');
    }
}
