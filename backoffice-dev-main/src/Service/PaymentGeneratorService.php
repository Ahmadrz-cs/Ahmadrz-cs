<?php

namespace App\Service;

use App\Entity\Enum\AllocationMethod;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\TradeOrder;
use App\Repository\UserRepository;
use BcMath\Number;
use Psr\Log\LoggerInterface;
use RoundingMode;

/**
 * Supporting service for generating Payment Requests
 */
class PaymentGeneratorService
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
    ) {}

    public function generateDividends(
        PaymentOrder $paymentOrder,
        array $shareholdings,
        Number|string $payoutPot,
        AllocationMethod $method = AllocationMethod::Accrue,
    ): PaymentOrder {
        if (is_null($paymentOrder->getAsset())) {
            throw new \Exception(
                'Payment order #'
                . $paymentOrder->getId()
                . ' is not linked to an asset',
            );
        }
        $paymentType = ucfirst($paymentOrder->getPaymentType());
        if (PaymentService::TYPE_DIVIDEND != $paymentType) {
            throw new \RuntimeException('Cannot generate dividends for payment type: '
            . $paymentType);
        }
        $userPayments = $this->allocateDividends($shareholdings, $payoutPot, $method);
        // $this->logger->debug('user payments', $userPayments);
        $mappedPayments = [];
        foreach ($userPayments['payoutInfo'] as $payment) {
            $mappedPayments[$payment['userid']] = $payment;
        }
        $usersWithPayments = array_keys($mappedPayments);

        // Overwrite existing payment requests
        foreach ($paymentOrder->getPayments() as $existingRequest) {
            $payeeUserId = $existingRequest->getPayee()->getId();
            if (in_array($payeeUserId, $usersWithPayments)) {
                $existingRequest->setAmount($mappedPayments[$payeeUserId]['payout']);
                $existingRequest->setShareholding(
                    $mappedPayments[$payeeUserId]['shares'],
                );
                unset($mappedPayments[$payeeUserId]);
            } else {
                // Remove existing payment requests if no match from generator
                $paymentOrder->removePayment($existingRequest);
            }
        }
        // Create new payment requests
        foreach ($mappedPayments as $payment) {
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setAmount($payment['payout']);
            $paymentRequest->setShareholding($payment['shares']);
            $user = $this->userRepository->find($payment['userid']);
            if (is_null($user)) {
                throw new \Exception(
                    'User could not be found with id ' . $payment['userid'],
                );
            }
            $paymentRequest->setPayee($user);
            $paymentOrder->addPayment($paymentRequest);
        }
        return $paymentOrder;
    }

    public function generateDivestments(
        PaymentOrder $paymentOrder,
        array $shareholdings,
        Number|string $payoutPot,
        int $sharesToLiquidate,
    ): PaymentOrder {
        if (is_null($paymentOrder->getAsset())) {
            throw new \Exception(
                'Payment order #'
                . $paymentOrder->getId()
                . ' is not linked to an asset',
            );
        }
        $paymentType = ucfirst($paymentOrder->getPaymentType());
        if (!in_array($paymentType, [
            PaymentService::TYPE_LIQUIDATION,
            PaymentService::TYPE_DIVESTMENT,
            PaymentService::TYPE_INVESTMENT_EXIT,
        ])) {
            throw new \RuntimeException('Cannot generate divestments for payment type: '
            . $paymentType);
        }
        $userPayments = $this->allocateDivestments(
            $shareholdings,
            $payoutPot,
            $sharesToLiquidate,
        );
        // $this->logger->debug('user payments', $userPayments);
        $mappedPayments = [];
        foreach ($userPayments['payoutInfo'] as $payment) {
            $mappedPayments[$payment['userid']] = $payment;
        }
        $usersWithPayments = array_keys($mappedPayments);

        // Overwrite existing payment requests
        foreach ($paymentOrder->getPayments() as $existingRequest) {
            $payeeUserId = $existingRequest->getPayee()->getId();
            if (in_array($payeeUserId, $usersWithPayments)) {
                $existingRequest->setAmount($mappedPayments[$payeeUserId]['payout']);
                $existingRequest->setShareholding(
                    $mappedPayments[$payeeUserId]['sharesDivesting'],
                );
                unset($mappedPayments[$payeeUserId]);
            } else {
                // Remove existing payment requests if no match from generator
                $paymentOrder->removePayment($existingRequest);
            }
        }
        // Create new payment requests
        foreach ($mappedPayments as $payment) {
            $paymentRequest = new PaymentRequest();
            $paymentRequest->setAmount($payment['payout']);
            $paymentRequest->setShareholding($payment['sharesDivesting']);
            $user = $this->userRepository->find($payment['userid']);
            if (is_null($user)) {
                throw new \Exception(
                    'User could not be found with id ' . $payment['userid'],
                );
            }
            $paymentRequest->setPayee($user);
            $paymentOrder->addPayment($paymentRequest);
        }
        return $paymentOrder;
    }

    public function generateRepayments(
        PaymentOrder $paymentOrder,
        array $shareholdings,
        int $sharesToLiquidate,
    ): PaymentOrder {
        if (is_null($paymentOrder->getAsset())) {
            throw new \Exception(
                'Payment order #'
                . $paymentOrder->getId()
                . ' is not linked to an asset',
            );
        }
        $paymentType = ucfirst($paymentOrder->getPaymentType());
        if (!in_array($paymentType, [PaymentService::TYPE_REPAYMENT])) {
            throw new \RuntimeException('Cannot generate repayments for payment type: '
            . $paymentType);
        }
        $sharePrice = $paymentOrder->getAsset()->getPricePerShare();
        $payoutPot = new Number($sharePrice)->mul($sharesToLiquidate)->round(2);
        $userPayments = $this->allocateDivestments(
            $shareholdings,
            $payoutPot,
            $sharesToLiquidate,
        );
        // $this->logger->debug('user payments', $userPayments);
        $mappedPayments = [];
        foreach ($userPayments['payoutInfo'] as $payment) {
            $mappedPayments[$payment['userid']] = $payment;
        }

        // Clearing existing payments as mapping to existing is awkward with nested loop
        $paymentOrder->getPayments()->clear();

        // Create new payment requests
        foreach ($mappedPayments as $payment) {
            /**
             * @var TradeOrder[] $sellOrders
             */
            $sellOrders = $shareholdings[$payment['userid']]['openSellOrders'];
            $sharesRemaining = $payment['sharesDivesting'];
            foreach ($sellOrders as $sellOrder) {
                if (!$sharesRemaining) {
                    // All done for this prefunder
                    break;
                }
                $availableInOrder =
                    $sellOrder->getNumberOfShares() - $sellOrder->getSharesTraded();
                if ($availableInOrder <= 0) {
                    // All done for this sellOrder, move onto next one available
                    continue;
                }
                // Determine maximum amount of shares that can be bought for this sell order
                // Either the entire amount, or whatever is left in the sell order
                $maxSingleRepayment = min($sharesRemaining, $availableInOrder);
                $amountToPay = new Number($sharePrice)->mul($maxSingleRepayment)->round(
                    2,
                );
                $paymentRequest = new PaymentRequest();
                $paymentRequest->setAmount((string) $amountToPay);
                $paymentRequest->setShareholding($maxSingleRepayment);
                $user = $this->userRepository->find($payment['userid']);
                if (is_null($user)) {
                    throw new \Exception(
                        'User could not be found with id ' . $payment['userid'],
                    );
                }
                $paymentRequest->setPayee($user);
                $paymentRequest->setTradeOrder($sellOrder);
                $paymentOrder->addPayment($paymentRequest);
                $sharesRemaining -= $maxSingleRepayment;
            }
        }
        return $paymentOrder;
    }

    public function allocateDividends(
        array $shareholdings,
        Number|string $payoutPot,
        AllocationMethod $method = AllocationMethod::Accrue,
    ): array {
        if (!$payoutPot instanceof Number) {
            $payoutPot = new Number($payoutPot);
        }
        $sharesCirculating = array_reduce($shareholdings, function ($carry, $item) {
            return $carry += $item['shares'];
        });
        $assetPayouts = [];
        $totalPayout = new Number(0);
        foreach ($shareholdings as $shareholding) {
            $key = $shareholding['userid'];
            $assetPayouts[$key] = $shareholding;

            $proportion = new Number($shareholding['shares'])->div($sharesCirculating);
            $rawPayout = $payoutPot->mul($proportion);
            $payout = $rawPayout->round(2, RoundingMode::TowardsZero);

            if (AllocationMethod::Distribute == $method) {
                $assetPayouts[$key]['rawPayout'] = (string) $rawPayout;
            }
            $assetPayouts[$key]['payout'] = (string) $payout;
            $totalPayout = $totalPayout->add($payout);
        }
        if (AllocationMethod::Distribute == $method) {
            $remaining = $payoutPot->sub($totalPayout);
            $assetPayouts = $this->distributeLeftovers(
                (string) $remaining,
                $assetPayouts,
            );
            $totalPayout = $totalPayout->add($remaining);
        }
        return [
            'payoutInfo' => $assetPayouts,
            'totalShares' => (string) $sharesCirculating,
            'totalPayout' => (string) $totalPayout,
        ];
    }

    public function allocateDivestments(
        array $shareholdings,
        Number|string $payoutPot,
        int $sharesToLiquidate,
    ): array {
        if (!$payoutPot instanceof Number) {
            $payoutPot = new Number($payoutPot);
        }
        $sharesCirculating = array_reduce($shareholdings, function ($carry, $item) {
            return $carry += $item['shares'];
        });
        if ($sharesToLiquidate > $sharesCirculating) {
            throw new \Exception(
                'Trying to liquidate more shares than still circulating',
            );
        }
        $shareSplits = array_combine(
            array_column($shareholdings, 'userid'),
            array_column($shareholdings, 'shares'),
        );
        // $this->logger->debug('Initial splits', $shareSplits);
        if ($sharesToLiquidate < $sharesCirculating) {
            $shareSplits = $this->splitPartialDivestments(
                $shareSplits,
                $sharesToLiquidate,
            );
        }
        // $this->logger->debug('Final splits', $shareSplits);
        $assetPayouts = [];
        $totalPayout = new Number(0);
        $divisor = $sharesToLiquidate == 0 ? 1 : $sharesToLiquidate;
        $equivalentSharePrice = $payoutPot->div($divisor);
        foreach ($shareholdings as $shareholding) {
            $key = $shareholding['userid'];
            $assetPayouts[$key] = $shareholding;

            $rawPayout = $equivalentSharePrice->mul($shareSplits[$key]);
            $payout = $rawPayout->round(2, RoundingMode::TowardsZero);

            $assetPayouts[$key]['sharesDivesting'] = $shareSplits[$key];
            $assetPayouts[$key]['rawPayout'] = (string) $rawPayout;
            $assetPayouts[$key]['payout'] = (string) $payout;
            $totalPayout = $totalPayout->add($payout);
        }
        $remaining = $payoutPot->sub($totalPayout);
        $assetPayouts = $this->distributeLeftovers((string) $remaining, $assetPayouts);
        $totalPayout = $totalPayout->add($remaining);
        return [
            'payoutInfo' => $assetPayouts,
            'totalShares' => (string) $sharesToLiquidate,
            'totalPayout' => (string) $totalPayout,
        ];
    }

    public function splitPartialDivestments(
        array $fullShareSplits,
        int $sharesToLiquidate,
    ): array {
        // Prevent shares liquidating being greater than shares circulating
        $sharesCirculating = array_sum($fullShareSplits);
        $sharesToLiquidate = min($sharesToLiquidate, $sharesCirculating);

        arsort($fullShareSplits);
        $partialShareSplits = [];
        $allocated = 0;
        foreach ($fullShareSplits as $userId => $shares) {
            $initial = new Number($shares)
                ->mul($sharesToLiquidate)
                ->div($sharesCirculating)
                ->floor();
            $partialShareSplits[$userId] = (string) $initial;
            $allocated += (int) (string) $initial;
        }
        // $this->logger->debug('midpoint', $partialShareSplits);

        // Allocate leftover shares
        $remainder = (int) ($sharesToLiquidate - $allocated);

        // There will never be more than leftover shares than there are elements in $fullShareSplits
        // As the most floor() removes is 0.999....
        // Note the pass by reference for &$sharesplit, as we're directly modifying array value
        foreach ($partialShareSplits as $userId => &$shareSplit) {
            if ($remainder == 0) {
                break;
            }
            $sharesAvailable = $fullShareSplits[$userId] - $shareSplit;
            // Allocate at most 1 share per payee
            $sharesAllocatable = min(1, $sharesAvailable);
            $shareSplit += $sharesAllocatable;
            $remainder -= $sharesAllocatable;
        }
        // $this->logger->debug('final', $partialShareSplits);
        return $partialShareSplits;
    }

    public function distributeLeftovers(
        float|string $leftoverPot,
        array $assetPayouts,
    ): array {
        // $this->logger->debug('Leftover: ' . $leftoverPot);
        // convert the pot into pence os it acts like an integer countdown later
        $leftoverPot = (int) round($leftoverPot * 100);
        if ($leftoverPot > count($assetPayouts)) {
            throw new \Exception(
                'Not enough payments to distribute ' . $leftoverPot . 'p',
            );
        }
        $roundingLeaderboard = [];
        $longestLength = 0;
        foreach ($assetPayouts as $index => $payout) {
            // Removes the first 2 characters after the decimal point
            // substr(strrchr("7251.738215", "."), 3) will return 8215
            // This is equivalent to the 1000th pound (£) or the 10th of a penny and smaller
            // i.e. what is used if we were to do rounding
            $roundingLeaderboard[$index] = substr(
                strrchr($payout['rawPayout'], '.'),
                3,
            );
            $longestLength = max($longestLength, strlen($roundingLeaderboard[$index]));
        }
        // pad strings so they're the same length so can be sorted properly
        foreach ($roundingLeaderboard as $k => $v) {
            $roundingLeaderboard[$k] = str_pad($v, $longestLength, '0');
        }
        // Rank by highest to lowest
        // Could alternatively use sort (with asort) and use array_pop, you get the same effect
        arsort($roundingLeaderboard);
        // Only need the array indexes which should align the assetPayouts array
        $roundingLeaderboard = array_keys($roundingLeaderboard);
        while ($leftoverPot > 0) {
            // Use the first element which is the highest rank on a leaderboard
            $current = array_shift($roundingLeaderboard);
            $assetPayouts[$current]['payout'] = (string) round(
                $assetPayouts[$current]['payout'] + 0.01,
                2,
            );
            $leftoverPot--;
        }
        return $assetPayouts;
    }
}
