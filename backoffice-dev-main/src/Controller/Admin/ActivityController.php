<?php

namespace App\Controller\Admin;

use App\Form\Type\QueryActivityLogType;
use App\Service\Manager\AuditLoggerManager;
use Gedmo\Loggable\Entity\LogEntry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity')]
#[IsGranted('ROLE_ANALYST')]
class ActivityController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private AuditLoggerManager $auditLoggerManager,
    ) {}

    #[Route('', name: 'admin_activity_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->logger->info('List activity/audit logs');
        $form = $this->createForm(QueryActivityLogType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        try {
            $results = $this->auditLoggerManager->findByWithAssociations(
                $filters ?? [],
                [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
                $filters['perPage'] ?? 10,
                $filters['page'] ?? 1,
            );
        } catch (\Throwable $th) {
            $this->addFlash(
                'error',
                'Log entries may be temporarily unavailable. ' . $th->getMessage(),
            );
        }

        return $this->render('admin/pages/activity/index.html.twig', [
            'objects' => $results ?? null,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_activity_log', methods: ['GET'])]
    public function view(
        Request $request,
        #[MapEntity(id: 'id')] LogEntry $logEntry,
    ): Response {
        return $this->render('admin/pages/activity/view.html.twig', [
            'log' => $logEntry,
        ]);
    }
}
