<?php

namespace App\Controller\ApiV1;

use App\Dto\Payout\PayoutQueryDto;
use App\Dto\ShareTrade\ShareTradeQueryDto;
use App\Dto\TradeOrder\TradeOrderQueryDto;
use App\Entity\Enum\TradeStatus;
use App\Entity\User;
use App\Repository\PayoutRepository;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\Mapper\PayoutMapper;
use App\Service\Mapper\PortfolioMapper;
use App\Service\Mapper\ShareTradeMapper;
use App\Service\Mapper\TradeOrderMapper;
use App\Service\PortfolioService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[IsGranted(
    new Expression('is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_USER:READ")'),
)]
class SelfPortfolioController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private PayoutRepository $payoutRepository,
        private PortfolioService $portfolioService,
        private PayoutMapper $payoutMapper,
        private PortfolioMapper $portfolioMapper,
        private ShareTradeMapper $shareTradeMapper,
        private TradeOrderMapper $tradeOrderMapper,
        private NormalizerInterface $normalizer,
    ) {}

    #[Route(
        path: '/%api_network_path%/self/portfolio',
        name: 'api_get_self_portfolio',
        methods: ['GET'],
    )]
    public function retrievePortfolio(#[CurrentUser] User $currentUser): Response
    {
        $portfolio = $this->portfolioService->compilePortfolio($currentUser);
        $dto = $this->portfolioMapper->mapToDto($portfolio);
        return $this->json($dto);
    }

    #[Route(
        path: '/%api_network_path%/self/portfolio/unsettled',
        name: 'api_get_self_portfolio_unsettled',
        methods: ['GET'],
    )]
    public function retrieveUnsettled(
        #[CurrentUser]
        User $currentUser,
        #[MapQueryParameter]
        ?bool $currentMonthOnly = null,
    ): Response {
        // This endpoint does not paginate
        $filters = [
            'buyerId' => $currentUser->getId(),
            'status' => [TradeStatus::Unsettled],
        ];
        if ($currentMonthOnly) {
            $filters['createdAt_gte'] = new \DateTime(
                'midnight first day of this month',
            );
            $filters['createdAt_lt'] = new \DateTime(
                'midnight first day of next month',
            );
        }
        $unsettledTrades = $this->shareTradeRepository->findWithAssociations($filters);
        $dto = $this->shareTradeMapper->mapMultipleToDto($unsettledTrades);
        return $this->json(['data' => $dto]);
    }

    #[Route(
        path: '/%api_network_path%/self/portfolio/prefunding',
        name: 'api_get_self_portfolio_prefunding',
        methods: ['GET'],
    )]
    public function retrievePrefunding(#[CurrentUser] User $currentUser): Response
    {
        $portfolio = $this->portfolioService->compilePrefundingPortfolio($currentUser);
        $dto = $this->portfolioMapper->mapToDto($portfolio);
        return $this->json($dto);
    }

    #[Route(
        path: '/%api_network_path%/self/portfolio/share-trades',
        name: 'api_get_self_portfolio_share_trades',
        methods: ['GET'],
    )]
    public function retrieveShareTrades(
        #[CurrentUser]
        User $currentUser,
        #[MapQueryString]
        ShareTradeQueryDto $dto,
    ): Response {
        $filters = $this->normalizer->normalize($dto);
        // overwrite any userId filters with the current user as this is a self endpoint
        $filters['buyerId'] = $filters['buyerId'] !== null
            ? $currentUser->getId()
            : null;
        $filters['sellerId'] = $filters['sellerId'] !== null
            ? $currentUser->getId()
            : null;
        // Note that userId has lower precedence than buyerId and sellerId
        $filters['userId'] = $filters['userId'] !== null ? $currentUser->getId() : null;
        // If neither the buyer or sell id is set, set the user id (the "buyer OR seller" filter)
        // This ensures you don't accidentally get share trades you're not a participant of
        if (empty($filters['buyerId']) && empty($filters['sellerId'])) {
            $filters['userId'] = $currentUser->getId();
        }
        // Convert the shareTradeType (which is derived not stored) into the equivalent buy/sell order types
        if ($dto->shareTradeType) {
            $filters['buyOrderType'] = $dto->shareTradeType->validBuyTypes();
            $filters['sellOrderType'] = $dto->shareTradeType->validSellTypes();
        }
        // $this->logger->debug('List share trades', [
        //     'dto' => $dto,
        //     'filters' => $filters,
        // ]);
        // $shareTrades = $this->shareTradeRepository->findByWithAssociations(
        //     $filters,
        //     ['createdAt' => 'DESC'],
        //     $dto->perPage,
        //     $dto->page,
        // );
        // Don't support pagination for now as we'd need a special dto wrapper
        // to cover pagination info like page, pageSize, record count
        // Limit records with createdAt search range
        $shareTrades = $this->shareTradeRepository->findWithAssociations($filters, [
            'createdAt' => 'DESC',
        ]);
        $dto = $this->shareTradeMapper->mapMultipleToDto($shareTrades);
        return $this->json(['data' => $dto]);
    }

    #[Route(
        path: '/%api_network_path%/self/portfolio/trade-orders',
        name: 'api_get_self_portfolio_trade_orders',
        methods: ['GET'],
    )]
    public function retrieveTradeOrders(
        #[CurrentUser]
        User $currentUser,
        #[MapQueryString]
        TradeOrderQueryDto $dto,
    ): Response {
        $filters = $this->normalizer->normalize($dto);
        $filters['userId'] = $currentUser->getId();
        // $this->logger->debug('List trade orders', [
        //     'dto' => $dto,
        //     'filters' => $filters,
        // ]);
        // Don't support pagination for now as we'd need a special dto wrapper
        // to cover pagination info like page, pageSize, record count
        // Limit records with createdAt search range
        $tradeOrders = $this->tradeOrderRepository->findWithAssociations($filters, [
            'createdAt' => 'DESC',
        ]);
        $dto = $this->tradeOrderMapper->mapMultipleToDto($tradeOrders);
        return $this->json(['data' => $dto]);
    }

    #[Route(
        path: '/%api_network_path%/self/portfolio/payouts',
        name: 'api_get_self_portfolio_payouts',
        methods: ['GET'],
    )]
    public function retrievePayouts(
        #[CurrentUser]
        User $currentUser,
        #[MapQueryString]
        PayoutQueryDto $dto,
    ): Response {
        $filters = $this->normalizer->normalize($dto);
        $filters['userId'] = $currentUser->getId();
        // if (empty($filters['assetId'])) {
        //     // Limit to 12 months if no asset set to avoid returning too many results
        //     // As this root has no pagination
        //     $filters['createdAt_gte'] = max(
        //         // Note that the normalizer will have converted the datetime to a string
        //         // So can't be used for comparison with max()
        //         new \DateTime($filters['createdAt_gte']),
        //         new \DateTime('first day of -12 months'),
        //     );
        // }

        // $this->logger->debug('List payouts', [
        //     'dto' => $dto,
        //     'filters' => $filters,
        // ]);

        $payouts = $this->payoutRepository->findWithAssociations(
            $filters,
            ['createdAt' => 'DESC'],
            min($dto->perPage, 100),
        );
        $dto = $this->payoutMapper->mapMultipleToDto($payouts);
        return $this->json(['data' => $dto]);
    }
}
