<?php

namespace App\Controller\Admin;

use App\Form\Type\QueryInvestorLeaderboardType;
use App\Form\Type\QueryVisualisationOverTime;
use App\Repository\AssetRepository;
use App\Repository\UserRepository;
use App\Service\AnalyticsService;
use App\Service\MonthEndService;
use App\Service\Util\Helper;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/analytics')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private AnalyticsService $analyticsService,
        private MonthEndService $monthEndService,
    ) {}

    #[Route('', name: 'admin_analytics_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $this->logger->debug('In analytics dashboard');

        return $this->render('admin/pages/analytics/index.html.twig', [
            'activeUsers' => $this->analyticsService->getLoginActivityLastYear(),
            'platformInvestments' => $this->analyticsService->getAumByYear(),
            'userRegistrations' =>
                $this->analyticsService->getUserRegisrationsByMonth(),
            'investorReferrals' => $this->analyticsService->getUserReferrals(),
            // 'uniqueInvestors' => $this->analyticsService->getUniqueInvestors(),
            'investorInvestmentCounts' =>
                $this->analyticsService->getInvestorInvestmentCounts(),
            'relistings' => $this->analyticsService->getRelistingsOverTimeMonth(),
            'investments' => $this->analyticsService->getInvestmentsOverTime(),
            'retailInvestments' => $this->analyticsService->getRetailInvestmentsSummary(
                'year',
            ),
            'dividends' => $this->analyticsService->getDividendSummaryByAsset(),
        ]);
    }

    #[Route('/classic', name: 'admin_analytics_classic', methods: ['GET'])]
    public function classicDashboard(): Response
    {
        $this->logger->debug('In analytics classic dashboard');

        // $this->logger->warning($this->analyticsService->getResourceCounts());

        return $this->render('admin/pages/analytics/dashboard.html.twig', [
            'resourceCounts' => $this->analyticsService->getResourceCounts(),
            'aum' => $this->analyticsService->getAumByYear(),
            'userRegistrationsYear' =>
                $this->analyticsService->getUserRegisrationsByYear(),
            'userRegistrationsMonth' =>
                $this->analyticsService->getUserRegisrationsByMonth(),
            'offerings' => $this->analyticsService->getFirstPartyOfferings(),
            'normalisedYields' => $this->analyticsService->getNormalisedYields(),
            // 'invOverTimeYear' => $this->analyticsService->getInvestmentsOverTime(date('Y')),
            'relistingOverTimeMonth' =>
                $this->analyticsService->getRelistingsOverTimeMonth(),
            'relistingOverTimeYear' =>
                $this->analyticsService->getRelistingsOverTimeYear(),
            'uniqueInvestors' => $this->analyticsService->getUniqueInvestors(),
            'onboardedInvested' => $this->analyticsService->getUsersOnboardedInvested(),
            'activeUsers' => $this->analyticsService->getLoginActivityLastYear(),
        ]);
    }

    #[Route('/investments', name: 'admin_analytics_investments', methods: ['GET'])]
    public function investments(Request $request): Response
    {
        $this->logger->debug('In investments vis');
        $filters = [
            // legacy version
            // 'filter' => 12,
            'createdAt_gte' => new \DateTime('NOW -1 year'),
            'createdAt_lt' => new \DateTime('NOW'),
        ];
        $form = $this->createForm(QueryVisualisationOverTime::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }

        $this->logger->info('gte & lt filters: ', $filters);
        $filterDates = [
            $filters['createdAt_gte']->format('Y-m-d'),
            $filters['createdAt_lt']->format('Y-m-d'),
        ];
        $this->logger->info('filter dates: ', $filterDates);
        $data = $this->analyticsService->getInvestmentsOverTime($filterDates);
        if ($request->query->get('data')) {
            return $this->json($data);
        }
        return $this->render('admin/pages/analytics/investments.html.twig', [
            'investments' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/referrals', name: 'admin_analytics_referrals', methods: ['GET'])]
    public function referrals(Request $request): Response
    {
        $this->logger->debug('In referrals vis');
        $filters = [
            'createdAt_gte' => new \DateTime('NOW -1 year'),
            'createdAt_lt' => new \DateTime('NOW'),
        ];
        $form = $this->createForm(QueryVisualisationOverTime::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }

        $this->logger->info('gte & lt filters: ', $filters);
        $filterDates = [
            $filters['createdAt_gte']->format('Y-m-d'),
            $filters['createdAt_lt']->format('Y-m-d'),
        ];
        $this->logger->info('filter dates: ', $filterDates);
        $data = $this->analyticsService->getUserReferrals($filterDates);
        if ($request->query->get('data')) {
            return $this->json($data);
        }
        return $this->render('admin/pages/analytics/referrals.html.twig', [
            'referrals' => $data,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/active-users', name: 'admin_analytics_active_users', methods: ['GET'])]
    public function activeUsers(): Response
    {
        $this->logger->debug('In analytics active users');

        return $this->render('admin/pages/analytics/active_users.html.twig', [
            'activeUsers' => $this->analyticsService->getLoginActivityLastYear(),
            'activeUsersByTime' => $this->analyticsService->getLoginActivityLastYear(
                'time',
            ),
            'accessTokenActivity' =>
                $this->analyticsService->getAuthAccessTokenActivity(),
            'accessTokenActivityByTime' => $this->analyticsService->getAuthAccessTokenActivity(
                'time',
            ),
        ]);
    }

    #[Route('/dividends', name: 'admin_analytics_dividends', methods: ['GET'])]
    public function dividends(AssetRepository $assetRepository): Response
    {
        $this->logger->debug('In analytics dividends');
        $assets = $assetRepository->findAll();
        $assets = Helper::convertArrayKeysAsIds($assets);

        return $this->render('admin/pages/analytics/dividends.html.twig', [
            'dividends' => $this->analyticsService->getDividendSummaryByAsset(),
            // 'assetOfferingMap' => $this->analyticsService->getAssetOfferingMap(),
            'assetMap' => $assets,
        ]);
    }

    #[Route(
        '/general-investments',
        name: 'admin_analytics_general_investments',
        methods: ['GET'],
    )]
    public function generalInvestments(): Response
    {
        $this->logger->debug('In analytics general investments');

        return $this->render('admin/pages/analytics/general_investments.html.twig', [
            'platformInvestmentsYear' => $this->analyticsService->getRetailInvestmentsSummary(
                'year',
            ),
            'platformInvestmentsMonth' => $this->analyticsService->getRetailInvestmentsSummary(
                'month',
            ),
            'platformInvestmentsAsset' => $this->analyticsService->getRetailInvestmentsSummary(
                'asset',
            ),
        ]);
    }

    #[Route('/investors', name: 'admin_analytics_investors', methods: ['GET'])]
    public function investors(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        $this->logger->debug('In analytics investors');
        $default = [
            'metric' => 'buys',
            'month' => new \DateTime(date('Y-m-01')),
        ];
        $form = $this->createForm(QueryInvestorLeaderboardType::class, $default);
        $form->handleRequest($request);
        // $filters = $form->getData();
        $dateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $form->getData()['month'],
        );
        $this->logger->debug('Leaderboard config: ', [$form->getData()]);
        $leaderboard = $this->analyticsService->getMetricLeaderboard(
            $form->getData()['metric'],
            $dateRange['start'],
            $dateRange['end'],
        );
        $userIds = array_column($leaderboard, 'user');
        $users = $userRepository->buildQueryWithAssociations([
            'id' => $userIds,
        ])->getResult();
        $users = Helper::convertArrayKeysAsIds($users);
        return $this->render('admin/pages/analytics/investors.html.twig', [
            'dateRange' => $dateRange,
            'form' => $form,
            'leaderboard' => $leaderboard,
            'userIdMap' => $users,
        ]);
    }
}
