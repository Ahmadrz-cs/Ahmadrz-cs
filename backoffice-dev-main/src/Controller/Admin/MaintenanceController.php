<?php

namespace App\Controller\Admin;

use App\Form\CardCleanupType;
use App\Form\Type\QueryUserCommunicationType;
use App\Form\Type\UserCommsDeleteType;
use App\Repository\CommunicationRepository;
use App\Repository\UserRepository;
use App\Service\CardCleanupService;
use App\Service\MaintenanceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

#[Route('/maintenance')]
#[IsGranted('ROLE_TECH_OPS')]
class MaintenanceController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MaintenanceService $maintenanceService,
        private CommunicationRepository $communicationsRepository,
    ) {}

    #[Route('/user-comms', name: 'admin_maintenance_user_comms', methods: ['GET'])]
    public function userComms(): Response
    {
        $this->logger->debug('In maintenance user comms');
        return $this->render('admin/pages/maintenance/user_comms.html.twig', [
            'sizeBySubject' => $this->communicationsRepository->sizeBySubject(),
            'sizeBySubjectAndYear' => $this->communicationsRepository->sizeBySubject(
                null,
                true,
            ),
        ]);
    }

    #[Route(
        '/user-comms/cleanup',
        name: 'admin_maintenance_user_comms_cleanup',
        methods: ['GET', 'POST'],
    )]
    public function userCommsCleanup(Request $request): Response
    {
        $this->logger->debug('In maintenance user comms cleanup');

        $subjects = array_column(
            $this->communicationsRepository->sizeBySubject(),
            'subject',
        );
        sort($subjects);
        $defaultCriteria = [
            'createdAt_lt' => new \DateTime('-1 year'),
        ];
        $form = $this->createForm(UserCommsDeleteType::class, $defaultCriteria, [
            'subjectChoices' => $subjects,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var ClickableInterface $deleteButton */
                $deleteButton = $form->get('delete');
                if ($deleteButton->isClicked()) {
                    $result = $this->communicationsRepository->deleteBySubject(
                        $form->getData()['subject'],
                        $form->getData()['createdAt_lt'],
                    );
                    $this->addFlash(
                        'success',
                        "{$result} {$form->getData()['subject']} user comms deleted.",
                    );
                    return $this->redirectToRoute(
                        'admin_maintenance_user_comms',
                        [],
                        Response::HTTP_SEE_OTHER,
                    );
                } else {
                    $sizeBySubject = $this->communicationsRepository->sizeBySubject(
                        $form->getData()['createdAt_lt'],
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to delete user comms. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to delete user comms. ', [$e->getMessage()]);
            }
        }

        return $this->render('admin/pages/maintenance/user_comms_cleanup.html.twig', [
            'form' => $form->createView(),
            'sizeBySubject' => $sizeBySubject ?? [],
            'chosenSubject' => $form->getData()['subject'] ?? '',
        ]);
    }

    #[Route(
        '/user-comms/list',
        name: 'admin_maintenance_user_comms_list',
        methods: ['GET'],
    )]
    public function userCommsList(Request $request): Response
    {
        $this->logger->debug('In maintenance user comms browser');
        $form = $this->createForm(QueryUserCommunicationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->communicationsRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/maintenance/user_comms_list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/oauth2/cleanup',
        name: 'admin_maintenance_oauth2_cleanup',
        methods: ['GET', 'POST'],
    )]
    public function oauth2(Request $request): Response
    {
        $this->logger->debug('In maintenance oauth2 artifact cleanup');
        $form =
            $form = $this
                ->createFormBuilder()
                ->add('confirmation', CheckboxType::class, [
                    'label' => 'Confirm deletion of expired OAuth2 artifacts',
                    'required' => true,
                ])
                ->add('submit', SubmitType::class, ['label' => 'Clear Expired'])
                ->getForm();
        ;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $results = $this->maintenanceService->clearExpiredOAuth2Artifacts();
            $this->addFlash(
                'success',
                'Expired OAuth2 artifacts cleared: ' . json_encode($results),
            );
            $this->logger->info('Expired OAuth2 artifacts cleared', $results);
        }
        return $this->render('admin/pages/maintenance/oauth2_cleanup.html.twig', [
            'artifactSummary' => $this->maintenanceService->getOAuth2ArtifactSummary(),
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/activity-logs/cleanup',
        name: 'admin_maintenance_activity_log_cleanup',
        methods: ['GET', 'POST'],
    )]
    public function activityLogsCleanup(Request $request): Response
    {
        $this->logger->debug('In maintenance activity logs cleanup');

        $dateEnd = new \DateTime('-3 months');
        $form = $this
            ->createFormBuilder(['dateEnd' => $dateEnd])
            ->add('dateEnd', DateType::class, [
                'constraints' => [new LessThanOrEqual('-1 month')],
                'help' => 'This date is exclusive (< this date). Must be older than 1 month.',
                'label' => 'Logs older than',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('optimize', CheckboxType::class, [
                'help' => 'Recommended if deleting lots of rows (e.g. 10k+). May take some time to optimize.',
                'label' => 'Run OPTIMIZE TABLE after delete',
                'required' => false,
            ])
            ->add('search', SubmitType::class, [
                'label' => 'Search Logs',
            ])
            ->add('delete', SubmitType::class, [
                'label' => 'Delete Logs',
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $dateEnd = $form->getData()['dateEnd'];
            try {
                /** @var ClickableInterface $deleteButton */
                $deleteButton = $form->get('delete');
                if ($deleteButton->isClicked()) {
                    $result = $this->maintenanceService->deleteLogEntries($dateEnd);
                    $this->addFlash('success', "{$result} activity logs deleted.");
                    $this->logger->debug("Deleted {$result} activity log entries");
                    if ($form->getData()['optimize']) {
                        $this->maintenanceService->optimiseLogEntryTable();
                    }
                    return $this->redirectToRoute(
                        'admin_maintenance_activity_log_cleanup',
                        [],
                        Response::HTTP_SEE_OTHER,
                    );
                }
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    'Unable to delete activity logs. ' . $e->getMessage(),
                );
                $this->logger->error('Unable to delete activity logs. ', [$e->getMessage()]);
            }
        }
        $count = $this->maintenanceService->countLogEntries($dateEnd);

        return $this->render('admin/pages/maintenance/activity_logs_cleanup.html.twig', [
            'form' => $form->createView(),
            'logCount' => $count ?? null,
        ]);
    }

    #[Route(
        '/card/cleanup',
        name: 'admin_maintenance_card_cleanup',
        methods: ['GET', 'POST'],
    )]
    public function cardCleanup(
        Request $request,
        UserRepository $userRepository,
        CardCleanupService $cardCleanupService,
    ): Response {
        $this->logger->debug('In maintenance Mangopay card registration cleanup');
        $form = $this->createForm(CardCleanupType::class);

        $taskTracker = $cardCleanupService->getTaskTracker();
        if (is_null($taskTracker->getId())) {
            $this->entityManager->persist($taskTracker);
            $this->entityManager->flush();
        }

        $userToProcess = $userRepository->findNextMangopayReadyUser(
            $taskTracker->getMetadata()['lastUserId'],
        );
        if ($userToProcess === null) {
            $this->logger->notice(
                'Card cleanup round completed. Resetting task tracker for next round.',
            );
            $this->addFlash(
                'notice',
                'Card cleanup run completed. Resetting task tracker for next round.',
            );
            return $this->redirectToRoute('admin_maintenance_card_cleanup_reset');
        }

        $form->handleRequest($request);

        if (
            !$request->query->get('trackerOnly', default: false) || $form->isSubmitted()
        ) {
            ['cards' => $cards, 'pagination' => $pagination] =
                $cardCleanupService->listCardsForUser($userToProcess);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $deactivated = $cardCleanupService->cleanupCards(
                taskTracker: $taskTracker,
                cards: $cards,
                currentUserId: $userToProcess->getId(),
                totalItems: $pagination->TotalItems,
                batchSize: $form->getData()['batchSize'],
            );
            $this->entityManager->flush();

            if (count($deactivated) > 0) {
                $this->addFlash(
                    'success',
                    'Cleanup successful. Deactivated '
                        . count($deactivated)
                        . ' cards: '
                        . json_encode($deactivated, JSON_PRETTY_PRINT),
                );
            } else {
                if (count($cards) > 0) {
                    $this->addFlash(
                        'warning',
                        "Failed to deactivate some cards for user #{$userToProcess->getId()}. Checks logs for more information.",
                    );
                } else {
                    $this->addFlash(
                        'info',
                        "Cleanup successful. No cards to deactivate for user #{$userToProcess->getId()}",
                    );
                }
            }
            $this->logger->info('Successfully deactivated cards.', [
                'count' => count($deactivated),
                'ids' => $deactivated,
            ]);
            return $this->redirectToRoute('admin_maintenance_card_cleanup');
        }
        return $this->render('admin/pages/maintenance/card_cleanup.html.twig', [
            'form' => $form->createView(),
            'taskTracker' => $taskTracker,
            'userToProcess' => $userToProcess,
            'cards' => $cards ?? [],
            'pagination' => $pagination ?? [],
        ]);
    }

    #[Route(
        '/card/cleanup/reset',
        name: 'admin_maintenance_card_cleanup_reset',
        methods: ['GET'],
    )]
    public function cardCleanupReset(CardCleanupService $cardCleanupService): Response
    {
        $this->logger->debug('Resetting card cleanup task tracker');
        $taskTracker = $cardCleanupService->getTaskTracker();
        $cardCleanupService->resetTaskTracker($taskTracker);
        $this->entityManager->flush();
        return $this->redirectToRoute('admin_maintenance_card_cleanup');
    }
}
