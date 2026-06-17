<?php

namespace App\Controller\Admin;

use App\Entity\ReportSet;
use App\Repository\ReportSetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports/sets')]
#[IsGranted('ROLE_OPERATIONS')]
class ReportSetController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ReportSetRepository $reportSetRepository,
    ) {}

    #[Route('', name: 'admin_report_set_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // $this->logger->debug('Showing report sets');
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->reportSetRepository->createQueryBuilder(
            'rs',
        )->addOrderBy('rs.createdAt', 'DESC');
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/reports/sets/index.html.twig', [
            'results' => $results ?? [],
        ]);
    }

    #[Route(
        '/{id}',
        name: 'admin_report_set_view',
        methods: ['GET'],
        requirements: ['id' => '\d+'],
    )]
    public function view(ReportSet $reportSet): Response
    {
        return $this->render('admin/pages/reports/sets/view.html.twig', [
            'reportSet' => $reportSet,
        ]);
    }
}
