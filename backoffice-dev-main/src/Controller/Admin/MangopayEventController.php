<?php

namespace App\Controller\Admin;

use App\Form\Type\QueryMangopayEventsType;
use App\Service\Mangopay\MangopayWebhookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events/mangopay')]
#[IsGranted('ROLE_TECH_OPS')]
class MangopayEventController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWebhookService $mangopayWebhookService,
    ) {}

    #[Route('', name: 'admin_events_mangopay_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Showing Mangopay events');

        $pagination = new \MangoPay\Pagination();
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('Date', 'DESC');
        $queryConfig = [
            'page' => $pagination->Page,
            'perPage' => $pagination->ItemsPerPage,
            'filters' => null,
        ];
        $form = $this->createForm(QueryMangopayEventsType::class, $queryConfig);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $queryConfig = $form->getData();
            $pagination->Page = $queryConfig['page'];
            $pagination->ItemsPerPage = $queryConfig['perPage'];
        }
        try {
            $results = $this->mangopayWebhookService->listEvents(
                $pagination,
                $queryConfig['filters'],
                [
                    'CreationDate' => 'DESC',
                ],
            );
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error retrieving events', [
                $e->GetCode(),
                $e->getMessage(),
                $e->GetErrorDetails(),
            ]);
            $this->addFlash(
                'error',
                'Error retrieving events ' . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->logger->error('Error retrieving events', [$e->getMessage()]);
            $this->addFlash('error', 'Error retrieving events ' . $e->getMessage());
        }
        // Clamp pagination for rendering
        $pagination->Page = min($pagination->Page ?? 1, $pagination->TotalPages);
        return $this->render('admin/pages/events/mangopay_events.html.twig', [
            'form' => $form->createView(),
            'results' => $results ?? [],
            'pagination' => $pagination,
        ]);
    }
}
