<?php

namespace App\Controller\Admin;

use App\Form\JobCardCleanupType;
use App\Message\CardCleanupBatchRun;
use App\Service\CardCleanupService;
use App\Service\MaintenanceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/maintenance/jobs')]
#[IsGranted('ROLE_TECH_OPS')]
class MaintenanceJobController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MaintenanceService $maintenanceService,
        private MessageBusInterface $bus,
    ) {}

    #[Route(
        '/card/cleanup',
        name: 'admin_maintenance_jobs_card_cleanup',
        methods: ['GET', 'POST'],
    )]
    public function cardCleanup(
        Request $request,
        CardCleanupService $cardCleanupService,
    ): Response {
        $this->logger->debug(
            'In maintenance Mangopay card registration cleanup job configuration',
        );
        $form = $this->createForm(JobCardCleanupType::class, [
            'jobSize' => CardCleanupBatchRun::BATCH_LIMIT,
        ]);

        $taskTracker = $cardCleanupService->getTaskTracker();
        if (is_null($taskTracker->getId())) {
            $this->entityManager->persist($taskTracker);
            $this->entityManager->flush();
        }

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($taskTracker->getMetadata()['jobInProgress']) {
                $this->addFlash(
                    'warning',
                    'Cannot submit new cleanup job. A cleanup job is already in progress.',
                );
                return $this->redirectToRoute('admin_maintenance_jobs_card_cleanup');
            }
            /**
             * @var \App\Entity\User $user
             */
            $user = $this->getUser();
            $job = new CardCleanupBatchRun(
                submittedByUserId: $user->getId(),
                autoContinue: $form->getData()['jobSize']
                > $form->getData()['batchSize'],
                batchSize: $form->getData()['batchSize'],
                jobSize: $form->getData()['jobSize'],
            );
            $taskTracker =
                $cardCleanupService->resetTaskTrackerJobCounter($taskTracker);
            $taskTracker = $cardCleanupService->setJobInProgress($taskTracker, true);
            $this->entityManager->flush();
            $this->bus->dispatch($job);
            $this->addFlash(
                'success',
                'Successfully submitted CardCleanupBatchRun job.',
            );
            $this->logger->info('Successfully submitted CardCleanupBatchRun job.', [
                'jobSize' => $job->jobSize,
                'batchSize' => $job->batchSize,
                'submittedBy' => $user->getId(),
            ]);
            return $this->redirectToRoute('admin_maintenance_jobs_card_cleanup');
        }
        return $this->render('admin/pages/maintenance/jobs/card_cleanup.html.twig', [
            'form' => $form->createView(),
            'taskTracker' => $taskTracker,
        ]);
    }

    #[Route(
        '/cancel/card-cleanup',
        name: 'admin_maintenance_jobs_cancel_card_cleanup',
        methods: ['GET'],
    )]
    public function cancelJobCardCleanup(CardCleanupService $cardCleanupService): Response
    {
        $this->logger->debug('Cancelling card cleanup job if active');
        $taskTracker = $cardCleanupService->getTaskTracker();
        $taskTracker = $cardCleanupService->setJobInProgress($taskTracker, false);
        $this->entityManager->flush();
        return $this->redirectToRoute('admin_maintenance_jobs_card_cleanup');
    }
}
