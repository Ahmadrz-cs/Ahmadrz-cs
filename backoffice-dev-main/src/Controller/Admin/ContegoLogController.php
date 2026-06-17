<?php

namespace App\Controller\Admin;

use App\Repository\ContegoLogRepository;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/contegoLog')]
class ContegoLogController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ContegoLogRepository $contegoLogRepository,
    ) {}

    #[Route(path: '', name: 'admin_contegolog_index')]
    public function indexAction(Request $request)
    {
        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $queryBuilder = $this->contegoLogRepository->createQueryBuilder(
            'c',
        )->addOrderBy('c.createdAt', 'DESC');
        $adapter = new QueryAdapter($queryBuilder);
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));
        return $this->render('admin/pages/contego_logs/index.html.twig', [
            'results' => $results ?? [],
        ]);
    }
}
