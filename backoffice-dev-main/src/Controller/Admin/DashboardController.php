<?php

namespace App\Controller\Admin;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\TaskTrackerType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\Offering;
use App\Repository\AssetRepository;
use App\Repository\BankAccountRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TaskTrackerRepository;
use App\Repository\TradeOrderRepository;
use App\Repository\UserRepository;
use App\Service\MonthEndTaskTrackerService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class DashboardController extends AbstractController
{
    public const TODO_CACHE_TTL = 900; // 15 minutes

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private TaskTrackerRepository $taskTrackerRepository,
        private BankAccountRepository $bankAccountRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private UserRepository $userRepository,
        private MonthEndTaskTrackerService $monthEndTaskTrackerService,
        private TagAwareCacheInterface $defaultAppCache,
        private string $frontendUrl,
    ) {}

    public function rootAction()
    {
        if ($this->isGranted('ROLE_ANALYST')) {
            return $this->redirectToRoute('admin_dashboard_index');
        } else {
            return $this->redirect($this->frontendUrl . '/login');
        }
    }

    #[Route('', name: 'admin_dashboard_index', methods: ['GET'])]
    public function indexAction(Request $request): Response
    {
        /**
         * Stats needed
         * - Total active assets (with at least 1 share in circulation)
         * - Total prefunding asset (offerings that are type prefunding)
         * - Investments to settled (number of approved investments)
         * - Completed user registrations in last 30 days
         * - Pending manual review (email verified status and ob_step 5)
         * - Relisting pending approval (any offering where sell_investment is not null and status is pre-published)
         * - Current Top Yielders (VIP users)
         * - Pending top yielders applications (has words of own but is not a VIP yet)
         * - Bank account registrations pending a review
         */
        if ($request->query->get('refresh', false)) {
            $this->defaultAppCache->delete('dashboard_todos');
            return $this->redirectToRoute('admin_dashboard_index');
        }
        $todos = $this->defaultAppCache->get('dashboard_todos', function (ItemInterface $item): array {
            $item->expiresAfter(self::TODO_CACHE_TTL);

            $trackedAssets = count($this->shareTradeRepository->aggregateSharesInCirculation(
                nonZero: true,
            ));
            $prefundingAssets = count(
                $this->assetRepository->buildQueryWithAssociations([
                    'status' => [AssetStatus::Acquiring],
                ])->getResult(),
            );
            $investmentsToSettle = count(
                $this->investmentRepository->buildQueryWithAssociations([
                    'lifecycleStatus' => [InvestmentLifecycle::STATE_APPROVED],
                ])->getResult(),
            );
            $usersToReviewLast30Days = count(
                $this->userRepository->buildQueryWithAssociations([
                    'createdAt_gte' => new \DateTime('-30 days'),
                    'ob_step' => 5,
                    'lifecycleStatus' => [
                        UserLifecycle::STATE_EMAIL_VERIFIED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                        UserLifecycle::STATE_APPROVED,
                    ],
                ])->getResult(),
            );
            $usersToManuallyReview = count(
                $this->userRepository->buildQueryWithAssociations([
                    'ob_step' => 5,
                    'hasKycProfile' => 1,
                    'hasVerifiedBy' => 0,
                    'lifecycleStatus' => [
                        UserLifecycle::STATE_EMAIL_VERIFIED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                        UserLifecycle::STATE_APPROVED,
                    ],
                ])->getResult(),
            );
            $usersWithoutKycProfile = count(
                $this->userRepository->buildQueryWithAssociations([
                    'ob_step' => 5,
                    'hasKycProfile' => 0,
                    'lifecycleStatus' => [
                        UserLifecycle::STATE_EMAIL_VERIFIED,
                        UserLifecycle::STATE_REGISTRATION_COMPLETE,
                        UserLifecycle::STATE_APPROVED,
                    ],
                ])->getResult(),
            );

            $relistingsPending = $this->tradeOrderRepository->findByWithAssociations([
                'direction' => TradeDirection::Sell,
                'type' => TradeOrderType::marketTradingTypes(),
                'status' => [TradeOrderStatus::Draft, TradeOrderStatus::Submitted],
            ])->getNbResults();
            $topYielders = count(
                $this->userRepository->buildQueryWithAssociations([
                    'isVIP' => 1,
                ])->getResult(),
            );
            $topYielderApplications = count(
                $this->userRepository->buildQueryWithAssociations([
                    'isVIP' => 0,
                    'wordsOfOwn' => 1,
                ])->getResult(),
            );

            $bankAccountRegistrations = count(
                $this->bankAccountRepository->buildQueryWithAssociations([
                    'status' => [
                        BankAccountStatus::Pending,
                        BankAccountStatus::Validated,
                    ],
                ])->getResult(),
            );

            $taskTracker = $this->taskTrackerRepository->findOneBy([
                'taskTrackerType' => TaskTrackerType::Monthend,
            ], ['createdAt' => 'DESC']);
            if (is_null($taskTracker)) {
                $taskTracker = $this->monthEndTaskTrackerService->createMonthendTaskTracker(TaskTrackerType::Monthend);
                $this->doctrine->getManager()->persist($taskTracker);
                $this->doctrine->getManager()->flush();
            } else {
                $taskTracker =
                    $this->monthEndTaskTrackerService->validateTaskTracker(
                        $taskTracker,
                    );
            }

            return [
                'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
                'trackedAssets' => $trackedAssets,
                'prefundingCount' => $prefundingAssets,
                'investmentsToSettle' => $investmentsToSettle,
                'usersToReviewLast30Days' => $usersToReviewLast30Days,
                'usersToManuallyReview' => $usersToManuallyReview,
                'usersWithoutKycProfile' => $usersWithoutKycProfile,
                'relistingsPending' => $relistingsPending,
                'topYielders' => $topYielders,
                'topYielderApplications' => $topYielderApplications,
                'bankAccountRegistrations' => $bankAccountRegistrations,
                'monthendTasks' => $taskTracker->getTasks(),
            ];
        });

        return $this->render('admin/pages/dashboard/index.html.twig', [
            // 'usersCount' => $userCount,
            // 'offeringCount' => $offeringCount,
            // 'settledAmount' => round($settledAmount, 2),
            'lastChecked' => $todos['lastChecked'],
            'trackedAssets' => $todos['trackedAssets'],
            'prefundingCount' => $todos['prefundingCount'],
            'investmentsToSettle' => $todos['investmentsToSettle'],
            'usersToReviewLast30Days' => $todos['usersToReviewLast30Days'],
            'usersToManuallyReview' => $todos['usersToManuallyReview'],
            'usersWithoutKycProfile' => $todos['usersWithoutKycProfile'],
            'relistingsPending' => $todos['relistingsPending'],
            'topYielders' => $todos['topYielders'],
            'topYielderApplications' => $todos['topYielderApplications'],
            'bankAccountRegistrations' => $todos['bankAccountRegistrations'],
            'monthendTasks' => $todos['monthendTasks'],
            'refreshTime' => self::TODO_CACHE_TTL,
        ]);
    }
}
