<?php

namespace App\Controller\ApiV1;

use App\Dto\Payment\LinkedPaymentRequestDto;
use App\Dto\Sca\ScaOutcomeRequestDto;
use App\Dto\TradeOrder\TradeOrderRequestDto;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Repository\ShareTradeRepository;
use App\Repository\TradeOrderRepository;
use App\Service\MangopayScaService;
use App\Service\Mapper\ShareTradeMapper;
use App\Service\Mapper\TradeOrderMapper;
use App\Service\PortfolioService;
use App\Service\TradingService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class TradeOrderController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private ShareTradeRepository $shareTradeRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private PortfolioService $portfolioService,
        private ShareTradeMapper $shareTradeMapper,
        private TradeOrderMapper $tradeOrderMapper,
        private TradingService $tradingService,
        private MangopayScaService $mangopayScaService,
    ) {}

    #[Route(
        path: '/%api_network_path%/trade-orders',
        name: 'api_create_trade_order',
        methods: ['POST'],
    )]
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    public function create(
        #[CurrentUser]
        User $currentUser,
        #[MapRequestPayload(validationGroups: ['create'])]
        TradeOrderRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug('APIv1 create trade order');
        // If userId is left empty, default to current user
        if ($dto->userId === null) {
            $tradeOrder = new TradeOrder(user: $currentUser);
        }
        $tradeOrder = $this->tradeOrderMapper->mapToEntity($dto, $tradeOrder ?? null);
        $this->tradingService->validateTradeOrder($tradeOrder);
        // Only operation+ staff can create trades for other users
        if (
            !$this->isGranted('ROLE_OPERATIONS')
            || $tradeOrder->getUser() != $currentUser
        ) {
            $tradeOrder->setUser($currentUser);
            // Revalidate with different user is has complementary
            if ($tradeOrder->getComplementaryOrder()) {
                $this->tradingService->validateComplementaryOrder(
                    $tradeOrder,
                    $tradeOrder->getComplementaryOrder(),
                );
            }
        }
        // Prefunding does not need to go through shareholding checks
        // It will go through complementary id checks instead
        if (
            $dto->direction === TradeDirection::Sell && (
                $dto->type !== TradeOrderType::Prefunding
                && $dto->complementaryOrderId === null
            )
        ) {
            $sharesAvailable = $this->portfolioService->getSharesAvailableToSell(
                $tradeOrder->getUser(),
                $tradeOrder->getAsset(),
            );
            if ($sharesAvailable < $tradeOrder->getNumberOfShares()) {
                throw new BadRequestException('Not enough shares available to sell');
            }
            $tradeOrder = $this->tradingService->prepareSellOrder($tradeOrder);
        }
        if (
            $dto->complementaryOrderId
            && $tradeOrder->getComplementaryOrder() === null
        ) {
            throw new NotFoundHttpException(
                "Could not find complementaryOrderId: {$dto->complementaryOrderId}",
            );
        }
        $this->em->persist($tradeOrder);
        if ($dto->counterpartyOrderId) {
            // Does the counterpartyOrder exist?
            $counterpartyOrder = $this->tradeOrderRepository->find($dto->counterpartyOrderId);
            // Are we reserving shares?
            if ($counterpartyOrder && $dto->reserveShares) {
                $shareTrade = match ($tradeOrder->getDirection()) {
                    TradeDirection::Buy => $this->tradingService->reserveShares(
                        $tradeOrder->getDirection(),
                        $tradeOrder,
                        $counterpartyOrder,
                    ),
                    TradeDirection::Sell => $this->tradingService->reserveShares(
                        $tradeOrder->getDirection(),
                        $counterpartyOrder,
                        $tradeOrder,
                    ),
                };
                $this->em->persist($shareTrade);
            } else {
                throw new NotFoundHttpException(
                    "Could not find counterpartyOrderId: {$dto->counterpartyOrderId}",
                );
            }
        }
        $this->em->flush();
        return $this->json(
            data: $this->tradeOrderMapper->mapToDto($tradeOrder),
            status: Response::HTTP_CREATED,
        );
    }

    #[Route(
        path: '/%api_network_path%/trade-orders/{id}/payments',
        name: 'api_create_trade_order_payment',
        methods: ['POST'],
    )]
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    public function createTradeOrderPayment(
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        TradeOrder $tradeOrder,
        #[MapRequestPayload]
        LinkedPaymentRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug(
            "APIv1 create trade order #{$tradeOrder->getId()} payment",
        );
        // Can only edit own investments
        if (
            !$this->isGranted('ROLE_FINANCIAL_OPS')
            && $currentUser != $tradeOrder->getUser()
        ) {
            $this->logger->debug('Different user', [
                'current' => $currentUser->getId(),
                'orderUser' => $tradeOrder->getUser()->getId(),
                'orderId' => $tradeOrder->getId(),
            ]);
            throw new AccessDeniedHttpException(
                'Cannot create payment for different users trade',
            );
        }
        // Trade order must be in draft or submitted state to make payments
        if (!in_array($tradeOrder->getStatus(), [
            TradeOrderStatus::Draft,
            TradeOrderStatus::Submitted,
        ])) {
            throw new BadRequestHttpException(
                'Trade order must be in draft or submitted state to create a payment',
            );
        }
        try {
            $scaActionDto = $this->tradingService->takeTradeOrderPayment(
                $tradeOrder,
                $dto,
            );
            $this->em->flush();
        } catch (\Throwable $e) {
            if ($e->getCode() == 916) {
                $this->logger->error('Error occured in Mangopay createTradeOrderPayment', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                throw new BadRequestHttpException(
                    'Insufficient funds in wallet to cover the payment.',
                );
            } else {
                $this->logger->error('Error occured in Mangopay createTradeOrderPayment', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                throw new BadRequestHttpException(
                    'The payment provider was unable to process the payment.',
                );
            }
        }
        return $this->json(data: $scaActionDto, status: Response::HTTP_CREATED);
    }

    #[Route(
        path: '/%api_network_path%/trade-orders/{id}/payment-outcome',
        name: 'api_create_trade_order_payment_outcome',
        methods: ['POST'],
    )]
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_INVESTMENT:WRITE")',
        ),
    )]
    public function createTradeOrderPaymentOutcome(
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        TradeOrder $tradeOrder,
        #[MapRequestPayload]
        ScaOutcomeRequestDto $dto,
    ): JsonResponse {
        $this->logger->debug(
            "APIv1 submit trade order #{$tradeOrder->getId()} payment outcome",
            [
                $dto->success,
                $dto->type,
            ],
        );
        // Can only edit own investments
        if (
            !$this->isGranted('ROLE_FINANCIAL_OPS')
            && $currentUser != $tradeOrder->getUser()
        ) {
            $this->logger->debug('Different user', [
                'current' => $currentUser->getId(),
                'orderUser' => $tradeOrder->getUser()->getId(),
                'orderId' => $tradeOrder->getId(),
            ]);
            throw new AccessDeniedHttpException(
                'Cannot create submit payment outcome for different users trade',
            );
        }

        $success = $dto->success;
        if ($dto->verify && $tradeOrder->getTransactionReference()) {
            $success = $this->mangopayScaService->isTransferSucceeded(
                $tradeOrder->getTransactionReference(),
            );
        }

        $scaOutcomeDto = $this->tradingService->processPaymentOutcome(
            $tradeOrder,
            $success,
        );
        $this->em->flush();

        // if ($success && $dto->type != 'prefunding') {
        //     $investmentManagerV2->sendInvestmentCreatedMail($investment);
        // }
        return $this->json(data: $scaOutcomeDto, status: Response::HTTP_OK);
    }
}
