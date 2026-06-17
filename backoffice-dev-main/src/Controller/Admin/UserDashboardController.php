<?php

namespace App\Controller\Admin;

use App\Entity\ContegoLog;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\ScaStatus;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Offering;
use App\Entity\User;
use App\Form\KycRestrictionsType;
use App\Form\Type\QueryInvestmentType;
use App\Form\Type\QueryInvestorStatementType;
use App\Form\Type\QueryOfferingType;
use App\Form\Type\QueryPayoutType;
use App\Form\Type\QueryTradeOrderType;
use App\Form\Type\QueryTradeType;
use App\Repository\AssetRepository;
use App\Repository\ContegoLogRepository;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\AnalyticsService;
use App\Service\BankAccountSyncService;
use App\Service\DivestmentService;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayScaService;
use App\Service\MangopayWalletService;
use App\Service\MonthEndService;
use App\Service\ShareholdingService;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\Collections\CollectionAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/users/{id}/dashboard')]
#[IsGranted('ROLE_ANALYST')]
class UserDashboardController extends AbstractController
{
    // List of routes that other controllers should be allowed to redirect to
    public const REDIRECT_ROUTES = [
        'admin_user_dashboard_overview',
        'admin_user_dashboard_kyc',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private PayoutRepository $payoutRepository,
        private HoldingRepository $holdingRepository,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private ContegoLogRepository $contegoLogRepository,
        private UserManagerV2 $userManager,
        private AnalyticsService $analyticsService,
        private MonthEndService $monthEndService,
        private MangopayWalletService $walletService,
        private ShareholdingService $shareholdingService,
        private DivestmentService $divestmentService,
    ) {}

    #[Route('', name: 'admin_user_dashboard_overview', methods: ['GET'])]
    public function overview(User $user): Response
    {
        $this->logger->info('User dashboard - overview');
        $mangopayUser = $this->userManager->getMangopayUser($user);
        return $this->render('admin/pages/users/dashboard/overview.html.twig', [
            'user' => $user,
            'mangopayUser' => $mangopayUser,
        ]);
    }

    #[Route('/onboarding', name: 'admin_user_dashboard_onboarding', methods: ['GET'])]
    public function onboarding(User $user): Response
    {
        $this->logger->info('User dashboard - onboarding');
        return $this->render('admin/pages/users/dashboard/onboarding.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/investments', name: 'admin_user_dashboard_investments', methods: ['GET'])]
    public function investments(Request $request, User $user): Response
    {
        $this->logger->info('User dashboard - investments');
        $form = $this->createForm(QueryInvestmentType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->investmentRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'userId' => $user->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/users/dashboard/investments.html.twig', [
            'user' => $user,
            'investments' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/relistings', name: 'admin_user_dashboard_relistings', methods: ['GET'])]
    public function relistings(Request $request, User $user): Response
    {
        $this->logger->info('User dashboard - relistings');
        $form = $this->createForm(QueryOfferingType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->offeringRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'investmentUser' => $user->getId(),
                'sell_investment' => 1,
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/users/dashboard/relistings.html.twig', [
            'user' => $user,
            'offerings' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/payments', name: 'admin_user_dashboard_payments', methods: ['GET'])]
    public function payments(Request $request, User $user): Response
    {
        $this->logger->info('User dashboard - payments');
        $form = $this->createForm(QueryPayoutType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->payoutRepository->findByWithAssociations(
            array_merge($filters ?? [], [
                'userId' => $user->getId(),
            ]),
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/users/dashboard/payments.html.twig', [
            'user' => $user,
            'payments' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/documents', name: 'admin_user_dashboard_documents', methods: ['GET'])]
    public function documents(User $user): Response
    {
        $this->logger->info('User dashboard - documents');
        return $this->render('admin/pages/users/dashboard/documents.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/kyc', name: 'admin_user_dashboard_kyc', methods: ['GET'])]
    public function kyc(User $user): Response
    {
        $this->logger->info('User dashboard - kyc');
        $userContegoLogs = $this->contegoLogRepository->findBy([
            'user' => $user->getUserIdentifier(),
        ], [
            'createdAt' => 'DESC',
        ]);
        $userContegoLogs = array_filter(
            $userContegoLogs,
            fn(ContegoLog $log) => $log->getRAG() != 'WAITING',
        );
        return $this->render('admin/pages/users/dashboard/kyc.html.twig', [
            'user' => $user,
            'kycState' => $this->userManager->getKycState($user),
            'contegoLogs' => $userContegoLogs,
        ]);
    }

    #[Route(
        '/kyc/restrictions',
        name: 'admin_user_dashboard_kyc_restrictions',
        methods: ['GET', 'POST'],
    )]
    public function kycRestrictions(Request $request, User $user): Response
    {
        $this->logger->info('User dashboard - kyc - edit restrictions');
        $form = $this->createForm(KycRestrictionsType::class, $user->getKycProfile());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash(
                'success',
                'Successfully update user KYC account restrictions',
            );
            return $this->redirectToRoute('admin_user_dashboard_kyc', [
                'id' => $user->getId(),
            ]);
        }
        return $this->render('admin/pages/users/dashboard/kyc_restrictions.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/portfolio', name: 'admin_user_dashboard_portfolio', methods: ['GET'])]
    public function portfolio(User $user): Response
    {
        $this->logger->info('User dashboard - portfolio');

        $tradeBasedHoldings = $this->shareTradeRepository->aggregateUserShareholdingsByAsset(
            $user->getId(),
        );
        $tradeBasedHoldings =
            $this->shareholdingService->annotateAggregateShareholdings(
                $tradeBasedHoldings,
            );

        $currentHoldings = $this->holdingRepository->getShareHoldings([
            'currentHolding' => 1,
            'capitalRepayments' => false,
            'userId' => $user->getId(),
        ]);

        $formerHoldings = $this->holdingRepository->getShareHoldings([
            'currentHolding' => 0,
            'capitalRepayments' => false,
            'userId' => $user->getId(),
        ]);

        $relistings = $this->offeringRepository->buildQueryWithAssociations([
            'investmentUser' => $user->getId(),
            'sell_investment' => 1,
        ])->getResult();

        $offeredHoldings = [];
        /** @var Offering[] $relistings */
        foreach ($relistings as $listing) {
            $offeredHoldings[$listing->getAsset()->getId()] =
                (int) $listing->getNoOfShares() - (int) $listing->getSharesSold();
        }
        $dividendSummary = $this->payoutRepository->getDividendSummaryByAsset(
            $user->getId(),
        );
        $dividendSummary = array_combine(
            array_column($dividendSummary, 'assetId'),
            $dividendSummary,
        );
        // $this->logger->debug('dividends', $dividendSummary);

        $assetIds = array_merge(
            array_column($currentHoldings, 'assetId'),
            array_column($formerHoldings, 'assetId'),
            array_column($tradeBasedHoldings, 'assetid'),
        );

        $assets = $this->assetRepository->findBy([
            'id' => $assetIds,
        ]);
        $assets = Helper::convertArrayKeysAsIds($assets);

        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'userId' => $user->getId(),
                'status' => [TradeOrderStatus::Active, TradeOrderStatus::Completed],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary = $this->divestmentService->compileRepaymentProgress(
            $prefunderSellOrders,
            QueryGrouping::Asset,
        );

        $sellOrderAggregate = $this->shareTradeRepository->aggregateUserTradeOrdersByAsset(
            userId: $user->getId(),
            direction: TradeDirection::Sell,
            orderStatuses: TradeOrderStatus::nonCancelledStates(),
            orderTypes: TradeOrderType::circulatingSellTypes(),
        );
        $sellOrderAggregate = array_combine(
            array_column($sellOrderAggregate, 'assetId'),
            $sellOrderAggregate,
        );
        // $this->logger->debug('sellorderagg', $sellOrderAggregate);

        return $this->render('admin/pages/users/dashboard/portfolio.html.twig', [
            'user' => $user,
            'tradeHoldings' => $tradeBasedHoldings,
            'currentHoldings' => $currentHoldings,
            'offeredHoldings' => $offeredHoldings,
            'formerHoldings' => $formerHoldings,
            'dividendSummary' => $dividendSummary,
            'repaymentSummary' => $repaymentSummary,
            'sellOrderAggregate' => $sellOrderAggregate,
            'assets' => $assets,
            // there ought to be a better way of getting the yield info that from analytics service
            'assetOfferingMap' => $this->analyticsService->getAssetOfferingMap(),
        ]);
    }

    #[Route(
        '/share-trades',
        name: 'admin_user_dashboard_share_trades',
        methods: ['GET'],
    )]
    public function shareTrades(Request $request, User $user): Response
    {
        $this->logger->debug('User dashboard - share trades');
        $form = $this->createForm(QueryTradeType::class, null, [
            'user_filters' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->shareTradeRepository->findByWithAssociations(
            array_merge($filters ?? [], ['userId' => $user->getId()]),
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/users/dashboard/share_trades.html.twig', [
            'user' => $user,
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route(
        '/trade-orders',
        name: 'admin_user_dashboard_trade_orders',
        methods: ['GET'],
    )]
    public function tradeOrders(Request $request, User $user): Response
    {
        $this->logger->debug('User dashboard - share trades');
        $form = $this->createForm(QueryTradeOrderType::class, null, [
            'user_filters' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->tradeOrderRepository->findByWithAssociations(
            array_merge($filters ?? [], ['userId' => $user->getId()]),
            [
                $filters['orderBy'] ?? 'createdAt' =>
                    $filters['orderDirection'] ?? 'DESC',
            ],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );

        return $this->render('admin/pages/users/dashboard/trade_orders.html.twig', [
            'user' => $user,
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route('/statements', name: 'admin_user_dashboard_statements', methods: ['GET'])]
    public function statements(Request $request, User $user): Response
    {
        $this->logger->info('User dashboard - monthly statements');
        $default = ['month' => new \DateTime()];
        $form = $this->createForm(QueryInvestorStatementType::class, $default);
        $form->handleRequest($request);
        $dateRange = $this->monthEndService->getMonthEndDateRangeFromDateTime(
            $form->getData()['month'],
        );
        $dividends = $this->payoutRepository->findUserPayoutsInDateRange(
            $user->getId(),
            0,
            $dateRange['start'],
            $dateRange['end'],
        );
        $divestments = $this->payoutRepository->findUserPayoutsInDateRange(
            $user->getId(),
            1,
            $dateRange['start'],
            $dateRange['end'],
        );
        $settlements = $this->investmentRepository->findUserSettlementsInDateRange(
            $user->getId(),
            $dateRange['start'],
            $dateRange['end'],
        );
        $relistings = $this->investmentRepository->findUserSalesInDateRange(
            $user->getId(),
            $dateRange['start'],
            $dateRange['end'],
        );
        return $this->render('admin/pages/users/dashboard/statements.html.twig', [
            'user' => $user,
            'dividends' => $dividends,
            'divestments' => $divestments,
            'settlements' => $settlements,
            'relistings' => $relistings,
            'dateRange' => $dateRange,
            'form' => $form,
        ]);
    }

    #[Route(
        '/bank-accounts',
        name: 'admin_user_dashboard_bank_accounts',
        methods: ['GET'],
    )]
    public function bankAccounts(
        User $user,
        BankAccountSyncService $bankAccountSyncService,
        #[MapQueryParameter]
        bool $loadBankAccounts = false,
        #[MapQueryParameter]
        bool $loadRecipients = false,
    ): Response {
        $this->logger->info('User dashboard - bank accounts');

        $pagination = new \MangoPay\Pagination();
        $pagination->Page = 1;
        $pagination->ItemsPerPage = 10;
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('CreationDate', 'DESC');
        $filterBankAccounts = new \MangoPay\FilterBankAccounts();
        $filterBankAccounts->Active = 'true';
        $mangopayBankAccounts = [];
        $mangopayRecipients = [];

        if ($this->isGranted('ROLE_FINANCIAL_OPS')) {
            try {
                if ($user->getMangoPayUserId()) {
                    if ($loadBankAccounts) {
                        $mangopayBankAccounts = $this->walletService->mangopayApi->Users->GetBankAccounts(
                            $user->getMangoPayUserId(),
                            $pagination,
                            $sorting,
                            $filterBankAccounts,
                        );
                    }
                    if ($loadRecipients) {
                        $mangopayRecipients = $this->walletService->mangopayApi->Recipients->GetUserRecipients(
                            $user->getMangoPayUserId(),
                            $pagination,
                            $sorting,
                        );
                        $mangopayRecipients = array_filter(
                            $mangopayRecipients,
                            fn(\Mangopay\Recipient $r): bool => $r->Status == 'ACTIVE',
                        );
                        $toSync = $bankAccountSyncService->filterUnsyncedRecipients(
                            $bankAccountSyncService->getUserSyncedRecipientIds($user),
                            $mangopayRecipients,
                        );
                    }
                } else {
                    $this->addFlash(
                        'warning',
                        'User is not registered with Mangopay yet',
                    );
                }
            } catch (\MangoPay\Libraries\ResponseException $e) {
                $this->logger->error('Error retrieving bank accounts', [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ]);
                $this->addFlash(
                    'error',
                    'Error retrieving bank accounts ' . $e->getMessage() . '. '
                        . $e->GetErrorDetails(),
                );
            } catch (\Exception $e) {
                $this->logger->error('Error retrieving bank accounts', [$e->getMessage()]);
                $this->addFlash(
                    'error',
                    'Error retrieving bank accounts ' . $e->getMessage(),
                );
            }
        }
        return $this->render('admin/pages/users/dashboard/bank_accounts.html.twig', [
            'user' => $user,
            'mangopayBankAccounts' => $mangopayBankAccounts,
            'mangopayRecipients' => $mangopayRecipients,
            'recipientsToSync' => $toSync ?? [],
            'pagination' => $pagination,
        ]);
    }

    #[Route('/status-logs', name: 'admin_user_dashboard_status_logs', methods: ['GET'])]
    public function statusLogs(User $user): Response
    {
        $this->logger->debug('User dashboard - status logs');
        return $this->render('admin/pages/users/dashboard/status_logs.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/event-logs', name: 'admin_user_dashboard_event_logs', methods: ['GET'])]
    public function eventLogs(Request $request, User $user): Response
    {
        $this->logger->debug('User dashboard - event logs');

        $filters = [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ];
        $adapter = new CollectionAdapter($user->getLogs());
        $results = new Pagerfanta($adapter);
        $results->setMaxPerPage($filters['perPage'] ?? 10);
        $results->setCurrentPage(min($filters['page'] ?? 1, $results->getNbPages()));

        return $this->render('admin/pages/users/dashboard/event_logs.html.twig', [
            'user' => $user,
            'results' => $results,
        ]);
    }

    #[Route('/mangopay-sca', name: 'admin_user_dashboard_manage_sca', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function scaProxyConsent(
        User $user,
        MangopayScaService $mangopayScaService,
        #[MapQueryParameter]
        ?bool $startSession = false,
    ): Response {
        $this->logger->debug('User dashboard - manage Mangopay proxy consent');

        if (!$user->getMangoPayUserId() || !$user->getMangoPayWalletId()) {
            $this->addFlash(
                'warning',
                'User must have a Mangopay account and wallet to manage SCA',
            );
            return $this->redirectToRoute('admin_user_dashboard_overview', [
                'id' => $user->getId(),
            ]);
        }

        try {
            if ($startSession) {
                if ($user->getScaStatus() != ScaStatus::Active) {
                    $this->addFlash('warning', 'User must have a Mangopay account and wallet,
                and have enrolled with SCA to manage proxy consent');
                    return $this->redirectToRoute('admin_user_dashboard_overview', [
                        'id' => $user->getId(),
                    ]);
                }
                $action = 'start Mangopay proxy consent session';
                $userConsent = $this->walletService->manageUserScaConsent(
                    $user->getMangoPayUserId(),
                );
                $returnUrl = $this->generateUrl(
                    route: 'admin_user_dashboard_manage_sca',
                    parameters: ['id' => $user->getId()],
                    referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
                );
                return $this->redirect($mangopayScaService->getScaSessionUrl(
                    $userConsent,
                    $returnUrl,
                ));
            }
            $action = 'load Mangopay SCA statuses';
            $scaStatus = $this->walletService->retrieveScaStatus(
                $user->getMangoPayUserId(),
            );
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error(
                "Unable to {$action}.",
                [
                    $e->GetCode(),
                    $e->getMessage(),
                    $e->GetErrorDetails(),
                ],
            );
            $this->addFlash(
                'error',
                "Unable to {$action}. " . $e->getMessage() . '. '
                    . $e->GetErrorDetails(),
            );
        } catch (\Exception $e) {
            $this->addFlash('error', "Unable to {$action}. " . $e->getMessage());
            $this->logger->error("Unable to {$action}. ", [$e->getMessage()]);
        }

        return $this->render('admin/pages/users/dashboard/manage_sca.html.twig', [
            'user' => $user,
            'scaStatus' => $scaStatus ?? null,
        ]);
    }
}
