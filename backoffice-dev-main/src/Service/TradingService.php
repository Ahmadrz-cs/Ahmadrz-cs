<?php

namespace App\Service;

use App\Dto\Payment\LinkedPaymentRequestDto;
use App\Dto\Sca\ScaActionResponseDto;
use App\Dto\Sca\ScaOutcomeResponseDto;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Entity\Transaction;
use BcMath\Number;
use MangoPay\TransactionStatus;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TradingService
{
    public function __construct(
        private LoggerInterface $logger,
        private MangoPay $mangopayService,
    ) {}

    /**
     * Attempt to create a share trade based on a buy and sell order
     *
     * @throws BadRequestException
     */
    public function reserveShares(
        TradeDirection $initiator,
        TradeOrder $buyOrder,
        TradeOrder $sellOrder,
    ): ShareTrade {
        $initiatingOrder = match ($initiator) {
            TradeDirection::Sell => $sellOrder,
            TradeDirection::Buy => $buyOrder,
        };
        $counterpartyOrder = match ($initiator) {
            TradeDirection::Sell => $buyOrder,
            TradeDirection::Buy => $sellOrder,
        };

        // Must be in specific state to reserve shares (draft, submitted, or active)
        if (
            !in_array(
                $initiatingOrder->getStatus(),
                TradeOrderStatus::reservingStates(),
            )
            || !in_array(
                $counterpartyOrder->getStatus(),
                TradeOrderStatus::reservingStates(),
            )
        ) {
            throw new BadRequestException(
                'Buy or sell order not in suitable state to reserve shares',
            );
        }

        $shares = min(
            $initiatingOrder->getNumberOfShares(),
            $counterpartyOrder->getSharesAvailable(),
        );
        $this->logger->debug('Trading shares involved', [
            'initiator' => $initiatingOrder->getNumberOfShares(),
            'counterparty' => $counterpartyOrder->getSharesAvailable(),
        ]);
        // If reserving shares, you must (for now) complete in a single order (no incremental)
        // Reserving is only for buying in practice, and only for legacy style
        if ($initiatingOrder->getNumberOfShares() > $shares) {
            throw new BadRequestException(
                'Not enough shares available in counterparty to fulfill order',
            );
        }

        $shareTrade = new ShareTrade(
            buyOrder: $buyOrder,
            sellOrder: $sellOrder,
            numberOfShares: $shares,
            pricePerShare: $initiatingOrder->getPricePerShare(),
        );
        $this->validateShareTrade($shareTrade);

        $shareTrade->setStatus(TradeStatus::Reserved);
        $buyOrder->addShareTrade($shareTrade);
        $sellOrder->addShareTrade($shareTrade);

        return $shareTrade;
    }

    /**
     * First line checks - mainly whether the asset is accepting new TradeOrders
     * @throws BadRequestException
     */
    public function validateTradeOrder(TradeOrder $tradeOrder): bool
    {
        // Is the asset in a trading permitted state
        if (!in_array(
            $tradeOrder->getAsset()->getCurrentStatus(),
            AssetStatus::activeCases(),
        )) {
            throw new BadRequestException(
                'Asset must be in a tradeable status to invest',
            );
        }

        // Allowing empty share quantity and price may be a staff only feature in future
        if ($tradeOrder->getNumberOfShares() <= 0) {
            throw new BadRequestException('Number of shares must be greater than zero');
        }
        if ($tradeOrder->getPricePerShare() <= 0) {
            throw new BadRequestException('Price per share must be greater than zero');
        }

        // Must be trading type (buys are a superset of the sells as it includes prefunding)
        // Can add a staff bypass in future (bool toogle) that allows any type
        if (!in_array($tradeOrder->getType(), TradeOrderType::tradingBuyTypes())) {
            throw new BadRequestException(
                'Trade order type must be a valid trading type',
            );
        }

        // Is the asset open to the trading action (buy or sell)
        $actionBlocked = match ($tradeOrder->getDirection()) {
            TradeDirection::Sell => $tradeOrder->getAsset()->isSellRestricted(),
            TradeDirection::Buy => $tradeOrder->getAsset()->isBuyRestricted(),
        };
        if ($actionBlocked) {
            throw new BadRequestException(
                "{$tradeOrder->getDirection()->name}ing shares in this asset is currently restricted",
            );
        }
        return true;
    }

    /**
     * Checks relation between the buy and sell side of a ShareTrade
     *
     * @throws BadRequestException
     */
    public function validateShareTrade(ShareTrade $shareTrade): bool
    {
        // Are the pairings valid at all
        if (
            ShareTradeType::fromBuySellTypes(
                $shareTrade->getBuyOrder()->getType(),
                $shareTrade->getSellOrder()->getType(),
            ) === null
        ) {
            throw new BadRequestException('Unsupported buy-sell order pairing types');
        }

        // Are they for the same asset
        if (
            $shareTrade->getBuyOrder()->getAsset() != $shareTrade->getSellOrder()->getAsset()
        ) {
            throw new BadRequestException(
                'Buy-sell order pair cannot be for different assets',
            );
        }

        // Prefunding must be with fundraising-acquiring asset status
        if (
            $shareTrade->getBuyOrder()->getType() === TradeOrderType::Prefunding
            && $shareTrade->getBuyOrder()->getAsset()->getCurrentStatus()
                !== AssetStatus::Acquiring
        ) {
            throw new BadRequestException(
                'Asset must be in fundraising/acquiring status to prefund',
            );
        }

        // If specifically checking trading (not internal), then can only allow subset of types
        // if (
        //     $tradingOnly
        //     && !in_array(
        //         $shareTrade->getBuyOrder()->getType(),
        //         $shareTrade->getSellOrder()->getDirection()->counterpartyTradingTypes(),
        //     )
        //     || !in_array(
        //         $shareTrade->getSellOrder()->getType(),
        //         $shareTrade->getBuyOrder()->getDirection()->counterpartyTradingTypes(),
        //     )
        // ) {
        //     throw new BadRequestException('Unsupporting buy or sell types for trading');
        // }
        return true;
    }

    /**
     * @throws BadRequestException
     */
    public function validateComplementaryOrder(
        TradeOrder $tradeOrder,
        TradeOrder $complementaryOrder,
    ): bool {
        // Must be the same prefunding type
        if (
            $tradeOrder->getType() !== TradeOrderType::Prefunding
            || $complementaryOrder->getType() !== TradeOrderType::Prefunding
        ) {
            throw new BadRequestException(
                'Complementary orders must both prefunding type',
            );
        }

        // Must be different directions
        if ($tradeOrder->getDirection() == $complementaryOrder->getDirection()) {
            throw new BadRequestException(
                'Complementary orders must be opposite directions',
            );
        }

        // Sell side must have less than or equal to buy side shares
        $sellSide = match ($tradeOrder->getDirection()) {
            TradeDirection::Sell => $tradeOrder,
            TradeDirection::Buy => $complementaryOrder,
        };
        $buySide = match ($tradeOrder->getDirection()) {
            TradeDirection::Sell => $complementaryOrder,
            TradeDirection::Buy => $tradeOrder,
        };
        if ($sellSide->getNumberOfShares() > $buySide->getNumberOfShares()) {
            throw new BadRequestException(
                'Prefunding sell shares cannot be greater than the buy shares',
            );
        }

        // Are they for the same user
        if (
            $tradeOrder->getUser()?->getId() != $complementaryOrder->getUser()?->getId()
        ) {
            throw new BadRequestException(
                'Complementary orders cannot be for different users',
            );
        }

        // Are they for the same asset
        if (
            $tradeOrder->getAsset()?->getId() != $complementaryOrder->getAsset()?->getId()
        ) {
            throw new BadRequestException(
                'Complementary orders cannot be for different assets',
            );
        }

        // Asset must be in acquiring state to be prefunding
        if ($tradeOrder->getAsset()->getCurrentStatus() !== AssetStatus::Acquiring) {
            throw new BadRequestException(
                'Asset must be in fundraising/acquiring status to prefund',
            );
        }

        return true;
    }

    public function takeTradeOrderPayment(
        TradeOrder $tradeOrder,
        LinkedPaymentRequestDto $dto,
    ): ScaActionResponseDto {
        $mangopayTransfer = $this->mangopayService->createTradeOrderTransfer(
            $tradeOrder,
            $dto->amount,
            $dto->sca,
        );
        $transaction = $this->createTradeOrderTransaction(
            $tradeOrder,
            $mangopayTransfer,
        );
        $tradeOrder->setTransactionReference($mangopayTransfer->Id);
        $tradeOrder->setTransaction($transaction);

        if ($mangopayTransfer->PendingUserAction?->RedirectUrl) {
            $pendingUserAction = [
                'redirectUrl' => $mangopayTransfer->PendingUserAction->RedirectUrl,
            ];
        }

        return new ScaActionResponseDto(
            id: $tradeOrder->getId(),
            object: 'tradeOrder',
            status: $tradeOrder->getStatus()->value,
            providerId: $mangopayTransfer->Id,
            providerStatus: $mangopayTransfer->Status,
            pendingUserAction: $pendingUserAction ?? [],
        );
    }

    public function processPaymentOutcome(
        TradeOrder $tradeOrder,
        bool $success,
    ): ScaOutcomeResponseDto {
        $tradeOrder->deriveSharesTraded();
        $tradeOrderStatus = $success
            ? match (true) {
                $tradeOrder->getDirection() === TradeDirection::Sell
                    => TradeOrderStatus::Submitted,
                $tradeOrder->getSharesAvailable() > 0 => TradeOrderStatus::Active,
                default => TradeOrderStatus::Completed,
            }
            : TradeOrderStatus::Cancelled;
        $complementStatus = $success
            ? TradeOrderStatus::Active
            : TradeOrderStatus::Cancelled;
        $tradeStatus = $success ? TradeStatus::Unsettled : TradeStatus::Cancelled;
        $transactionStatus = $success
            ? TransactionStatus::Succeeded
            : TransactionStatus::Failed;

        $transaction = $tradeOrder->getTransaction();
        // Only update if necessary
        if ($tradeOrder->getStatus() !== $tradeOrderStatus) {
            $tradeOrder->setStatus($tradeOrderStatus);
        }
        // Check if there's a complementaryOrder to update - exclusively a prefunding thing for now
        if (
            $tradeOrder->getComplementaryOrder()
            && $tradeOrder->getComplementaryOrder()->getType()
                === TradeOrderType::Prefunding
            && $tradeOrder->getComplementaryOrder()->getStatus() !== $complementStatus
        ) {
            $tradeOrder->getComplementaryOrder()->setStatus($complementStatus);
        }
        if ($transaction && $transaction?->getPaymentStatus() != $transactionStatus) {
            $transaction->setPaymentStatus($transactionStatus);
        }
        foreach ($tradeOrder->getShareTrades() as $shareTrade) {
            if ($shareTrade->getStatus() == TradeStatus::Reserved) {
                $shareTrade->setStatus($tradeStatus);
            }
        }
        return new ScaOutcomeResponseDto(
            id: (string) $tradeOrder->getId(),
            object: 'tradeOrder',
            status: $tradeOrder->getStatus()->value,
            providerId: $tradeOrder->getTransactionReference(),
            success: $success,
        );
    }

    public function createTradeOrderTransaction(
        TradeOrder $tradeOrder,
        \Mangopay\Transfer $mangopayTransfer,
    ): Transaction {
        $this->logger->debug(
            "Creating transaction record for trade order {$tradeOrder->getId()}",
        );
        $transaction = new Transaction();
        $transaction->setTradeOrder($tradeOrder);
        $transaction->setDebitorId($tradeOrder->getUser()->getId());

        $transaction->setExternalId($mangopayTransfer->Id);
        $transaction->setValueAmount($mangopayTransfer->DebitedFunds->Amount);
        $transaction->setDebitedWalletId($mangopayTransfer->DebitedWalletId);
        $transaction->setCreditedWalletId($mangopayTransfer->CreditedWalletId);
        $transaction->setCurrency($mangopayTransfer->DebitedFunds->Currency);
        $transaction->setFeeAmount($mangopayTransfer->Fees->Amount);
        $transaction->setPaymentStatus($mangopayTransfer->Status);
        $transaction->setTransType(TRANS_TYPE_CONSTANT::TRANS_NP);

        return $transaction;
    }

    public function prepareSellOrder(TradeOrder $tradeOrder): TradeOrder
    {
        // Specifically ignore non sell orders
        if ($tradeOrder->getDirection() !== TradeDirection::Sell) {
            return $tradeOrder;
        }

        if (
            $tradeOrder->getAsset() !== null
            && $tradeOrder->getMinimumShares() === null
            && $tradeOrder->getPricePerShare() > 0
        ) {
            // Derive the minimum shares based on the asset's minimum investment (monetary)
            // Use £100 as the default if not set
            $minInvest = $tradeOrder->getAsset()->getMinimumInvestment() ?? new Number(
                '100',
            );
            $tradeOrder->setMinimumShares(
                (int) (string) $minInvest->div($tradeOrder->getPricePerShare())->ceil(),
            );
        }
        $tradeOrder->setMinimumShares(min(
            $tradeOrder->getNumberOfShares(),
            $tradeOrder->getMinimumShares(),
        ));

        return $tradeOrder;
    }
}
