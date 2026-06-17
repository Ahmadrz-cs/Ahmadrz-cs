<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Payout;
use App\Entity\User;
use App\Repository\InvestmentRepository;
use App\Repository\UserRepository;
use App\Service\Manager\PayoutManagerV2;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * High level abstraction for making types of payments
 * - Dividends
 * - Divestments (aka profit-share)
 * - Refunds in future?
 * Use PayoutManager for interacting with actual Payout entities
 */
class PaymentService
{
    /**
     * For term definitions and use:
     * https://gitlab.com/yielders2/business/-/issues/953
     */
    public const TYPE_DIVIDEND = 'Dividend';
    public const TYPE_LIQUIDATION = 'Liquidation';
    public const TYPE_REPAYMENT = 'Repayment';
    public const TYPE_DIVESTMENT = 'Divestment';
    public const TYPE_INVESTMENT_EXIT = 'Investment Exit';

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private PayoutManagerV2 $payoutManager,
        private UserRepository $userRepository,
        private InvestmentRepository $investmentRepository,
        private MangoPay $mangopayService,
    ) {}

    public function payDividend(
        Asset $asset,
        User $user,
        string $assetWalletUserId,
        array $payoutRequest,
        \DateTime $dueDate,
        ?string $debitWalletId = null,
    ): Payout {
        $transfer = $this->mangopayService->createTransferPayment(
            $asset,
            $user,
            $assetWalletUserId,
            $payoutRequest['cashValue'],
            self::TYPE_DIVIDEND,
            $debitWalletId,
        );
        $this->logger->debug('Mangopay service createTransferPayment: ', [$transfer]);
        if ('SUCCEEDED' == $transfer->Status) {
            $payout = $this->buildPayout(
                $asset,
                $user,
                $payoutRequest['cashValue'],
                $payoutRequest['currentHolding'],
                $transfer->Id,
                $dueDate,
                self::TYPE_DIVIDEND,
            );
            $this->entityManager->persist($payout);
            return $payout;
        } else {
            throw new \Exception(
                'Transfer could not be made: ' . $transfer->ResultMessage,
                $transfer->ResultCode,
            );
        }
    }

    public function payDivestment(
        Asset $asset,
        User $user,
        string $assetWalletUserId,
        array $payoutRequest,
        \DateTime $dueDate,
        string $transferType = self::TYPE_LIQUIDATION,
        ?string $debitWalletId = null,
    ): Payout {
        $transfer = $this->mangopayService->createTransferPayment(
            $asset,
            $user,
            $assetWalletUserId,
            $payoutRequest['cashValue'],
            $transferType,
            $debitWalletId,
        );
        $this->logger->debug('Mangopay service createTransferPayment: ', [$transfer]);
        if ('SUCCEEDED' == $transfer->Status) {
            $payout = $this->buildPayout(
                $asset,
                $user,
                $payoutRequest['cashValue'],
                $payoutRequest['sharesToLiquidate'],
                $transfer->Id,
                $dueDate,
                $transferType,
            );
            $this->entityManager->persist($payout);
            return $payout;
        } else {
            throw new \Exception(
                'Transfer could not be made: ' . $transfer->ResultMessage,
                $transfer->ResultCode,
            );
        }
    }

    public function buildPayout(
        Asset $asset,
        User $user,
        float $amout,
        int $shareholding,
        string $transactionId,
        \DateTime $dueDate,
        string $transferType,
    ): Payout {
        $payoutType = match ($transferType) {
            self::TYPE_DIVIDEND => 0,
            default => 1,
        };
        $payout = new Payout();
        $payout->setCurrency('GBP');
        $payout->setPayoutType($payoutType);

        $payout->setAsset($asset);
        $payout->setCreditedUser($user);
        $payout->setPayoutAmount($amout);
        $payout->setShareholding($shareholding);
        $payout->setTransactionId($transactionId);
        $payout->setDueDate($dueDate);

        return $payout;
    }

    public function onlyUpdatePrefundingInvestmentsForType(string $transferType): bool
    {
        $prefundingRelatedPayments = [
            self::TYPE_REPAYMENT,
        ];
        return in_array($transferType, $prefundingRelatedPayments);
    }

    public function getDefaultAssetWalletUserId(): ?string
    {
        return $this->payoutManager->getSuperAdminAuthId();
    }
}
