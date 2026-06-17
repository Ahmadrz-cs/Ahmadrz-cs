<?php

namespace App\Controller\Admin;

use App\Repository\WebhookEventRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/webhooks')]
#[IsGranted('ROLE_TECH_OPS')]
class WebhookController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private WebhookEventRepository $webhookEventRepository,
    ) {}

    #[Route('/recent-events', name: 'admin_webhooks_recent_events', methods: ['GET'])]
    public function events(Request $request): Response
    {
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->webhookEventRepository->createQueryBuilder(
            'whe',
        )->addOrderBy('whe.lastReceived', 'DESC');
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/webhooks/recent_events.html.twig', [
            'results' => $results ?? [],
        ]);
    }
}
