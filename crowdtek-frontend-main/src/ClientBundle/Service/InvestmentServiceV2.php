<?php

namespace ClientBundle\Service;

use AppBundle\Entity\AssetProduct;
use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\ScaStatus;
use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\Enum\TradeStatus;
use AppBundle\Entity\ScaAction;
use AppBundle\Entity\ScaOutcome;
use AppBundle\Entity\ShareTrade;
use AppBundle\Entity\TradeOrder;
use ClientBundle\Exception\InvestmentNotAllowedException;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class InvestmentServiceV2
{
    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private OnboardingService $onboardingService,
        private VerificationService $verificationService,
        private PortfolioService $portfolioService,
        private AssetProductService $assetProductService,
        private DenormalizerInterface $denormalizer,
    ) {}

    /**
     * - No stamp duty is taken
     * - Prefunding sell order is created (representation the prefunder liquidation)
     * @throws InvestmentNotAllowedException
     */
    public function prefundInvestAsset(
        AssetProduct $asset,
        int $numberOfShares,
        int $sharesToKeep,
        ?TradeOrder $tradeOrder = null,
        bool $sca = true,
    ): ScaAction|ScaOutcome {
        $this->logger->info('IN prefundInvestAsset');

        if ($asset->status != AssetStatus::Acquiring) {
            throw new InvestmentNotAllowedException('Asset not currently open for prefunding.');
        }

        if ($tradeOrder && $tradeOrder->type != TradeOrderType::Initial) {
            throw new InvestmentNotAllowedException('The chosen asset listing does not support prefunding.');
        }

        if ($tradeOrder) {
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            $userId = (\is_array($userInfo) && \array_key_exists('id', $userInfo))
                ? $userInfo['id']
                : 0;
            if ($userId == $tradeOrder->userId) {
                $this->logger->debug("Cannot invest in own sell order");
                throw new InvestmentNotAllowedException('Cannot invest through your own listing');
            }
        }


        // Pre-check for investment
        $this->validateInvestmentAmount($asset, $numberOfShares, $tradeOrder);

        // Retention amount check
        // $retentionLimit = $asset->maximumRetention ?: '0.25';
        $retentionLimit = '0.25';
        if (($sharesToKeep / $numberOfShares) > $retentionLimit) {
            $this->logger->debug(
                "Cannot invest in own sell order",
                ['totalShares' => $numberOfShares, 'retentionShares' => $sharesToKeep],
            );
            $retentionAsPercent = round($retentionLimit * 100, 2);
            throw new InvestmentNotAllowedException("Cannot keep/retain more than {$retentionAsPercent}% of total prefunding amount");
        }

        // Calculate amount required to take (i.e. stamp duty)
        $sharePrice = $tradeOrder ? $tradeOrder->pricePerShare : $asset->pricePerShare;
        $totalToPay = $investmentValue = $numberOfShares * $sharePrice;
        $liquidationShares = $numberOfShares - $sharesToKeep;
        $liquidationValue = $liquidationShares * $sharePrice;
        $retentionValue = $sharesToKeep * $sharePrice;
        $this->logger->debug(
            "Prefunding investment amounts",
            ['total' => $investmentValue, 'retention' => $retentionValue, 'liquidation' => $liquidationValue],
        );
        if (!$this->checkBalanceAvailable($totalToPay)) {
            $this->logger->debug("Wallet balance pre-check - low balance");
            throw new InvestmentNotAllowedException('Insufficient wallet balance to make payment');
        }

        // Create the buy order (and reserved share trade)
        $buyOrder = $this->createBuyOrder(
            asset: $asset,
            numberOfShares: $numberOfShares,
            tradeOrder: $tradeOrder,
            prefunding: true,
        );

        // Create the sell order for the liquidation portion
        $sellOrder = $this->createSellOrder(
            asset: $asset,
            numberOfShares: $liquidationShares,
            tradeOrder: $buyOrder,
            prefunding: true,
        );
        $this->logger->debug(
            "Prefunding orders",
            ['buyId' => $buyOrder->id, 'sellId' => $sellOrder->id],
        );

        // Clear single asset cache so others can see share reservation occupying the available
        // Doesn't affect the general listings which remain on a 60 second TTL
        $this->assetProductService->clearSingleAssetProductCache($asset->id);

        // $this->logger->debug("BuyOrder", json_decode(json_encode($buyOrder), true));

        // Create SCA transfer and return the response if successful
        $scaAction = $this->takeOrderPayment($buyOrder->id, $totalToPay, $sca);

        // $this->logger->debug("Sca payment", json_decode(json_encode($scaAction), true));

        if ($scaAction->providerStatus == 'SUCCEEDED' && empty($scaAction->pendingUserAction)) {
            // If the transfer for succeeded without SCA (e.g. below threshold), mark buy order as payment completed
            $scaOutcome = $this->processOrderPaymentOutcome($buyOrder->id, true);
            // $this->logger->debug("Sca outcome", json_decode(json_encode($scaOutcome), true));
            return $scaOutcome;
        }
        return $scaAction;
    }

    /**
     * - Stamp duty is taken
     * - Stamp duty is calculated based of amount invested in current calendar month
     * @throws InvestmentNotAllowedException
     */
    public function retailInvestAsset(
        AssetProduct $asset,
        int $numberOfShares,
        ?TradeOrder $tradeOrder = null,
        bool $sca = true,
    ): ScaAction|ScaOutcome {
        $this->logger->info('IN retailInvestAsset');

        if ($tradeOrder) {
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            $userId = (\is_array($userInfo) && \array_key_exists('id', $userInfo))
                ? $userInfo['id']
                : 0;
            if ($userId == $tradeOrder->userId) {
                $this->logger->debug("Cannot invest in own sell order");
                throw new InvestmentNotAllowedException('Cannot invest through your own listing');
            }
        }

        // Pre-check for investment
        $this->validateInvestmentAmount($asset, $numberOfShares, $tradeOrder);

        // Calculate amount required to take (i.e. stamp duty)
        $sharePrice = $tradeOrder ? $tradeOrder->pricePerShare : $asset->pricePerShare;
        $totalInvestedThisMonth = $this->sumUnsettledMonthlyShareTrades($asset->id);
        $investmentValue = $numberOfShares * $sharePrice;
        $stampDuty = $this->calculateStampDutyDue($totalInvestedThisMonth, $investmentValue);
        $totalToPay = round($investmentValue + $stampDuty, 2);
        $this->logger->debug(
            "Retail investment amounts",
            ['mtd' => $totalInvestedThisMonth, 'new' => $investmentValue, 'duty' => $stampDuty, 'total' => $totalToPay],
        );
        if (!$this->checkBalanceAvailable($totalToPay)) {
            $this->logger->debug("Wallet balance pre-check - low balance");
            throw new InvestmentNotAllowedException('Insufficient wallet balance to make payment');
        }

        // Create the buy order (and reserved share trade)
        $buyOrder = $this->createBuyOrder(
            $asset,
            $numberOfShares,
            $stampDuty,
            $tradeOrder,
        );

        // Clear single asset cache so others can see share reservation occupying the available
        // Doesn't affect the general listings which remain on a 60 second TTL
        $this->assetProductService->clearSingleAssetProductCache($asset->id);

        // $this->logger->debug("BuyOrder", json_decode(json_encode($buyOrder), true));

        // Create SCA transfer and return the response if successful
        $scaAction = $this->takeOrderPayment($buyOrder->id, $totalToPay, $sca);

        // $this->logger->debug("Sca payment", json_decode(json_encode($scaAction), true));

        if ($scaAction->providerStatus == 'SUCCEEDED' && empty($scaAction->pendingUserAction)) {
            // If the transfer for succeeded without SCA (e.g. below threshold), mark buy order as payment completed
            $scaOutcome = $this->processOrderPaymentOutcome($buyOrder->id, true);
            // $this->logger->debug("Sca outcome", json_decode(json_encode($scaOutcome), true));
            return $scaOutcome;
        }
        return $scaAction;
    }

    /**
     * @throws InvestmentNotAllowedException
     */
    public function createBuyOrder(
        AssetProduct $asset,
        int $numberOfShares,
        float|int|string $stampDuty = 0,
        ?TradeOrder $tradeOrder = null,
        bool $prefunding = false,
    ): TradeOrder {
        $this->logger->info('IN createBuyOrder');

        $sharePrice = $tradeOrder ? $tradeOrder->pricePerShare : $asset->pricePerShare;
        $requestBody = [
            "assetId" => (string)$asset->id,
            "direction" => TradeDirection::Buy,
            "numberOfShares" => $numberOfShares,
            "pricePerShare" => (string)$sharePrice,
            "type" => $prefunding ? TradeOrderType::Prefunding : TradeOrderType::Market,
            // "notes" => "From APIv1",
            "status" => TradeOrderStatus::Submitted,
            "counterpartyOrderId" => $tradeOrder?->id,
            "reserveShares" => true,
            "fees" => "0",
            "taxes" => (string)$stampDuty,
        ];
        $response = $this->client->tradeOrder()->create([
            'json' => $requestBody,
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Unable to create buy order. Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response),
            );
            throw new InvestmentNotAllowedException(
                'Investment could not be made. Please try again or contact support if issue persists.',
            );
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            TradeOrder::class,
        );
    }

    /**
     * @throws InvestmentNotAllowedException
     */
    public function createSellOrder(
        AssetProduct $asset,
        int $numberOfShares,
        float|int|string $fees = 0,
        ?TradeOrder $tradeOrder = null,
        bool $prefunding = false,
    ): TradeOrder {
        $this->logger->info('IN createSellOrder');

        $sharePrice = $tradeOrder ? $tradeOrder->pricePerShare : $asset->pricePerShare;
        $requestBody = [
            "assetId" => (string)$asset->id,
            "direction" => TradeDirection::Sell,
            "numberOfShares" => $numberOfShares,
            "pricePerShare" => (string)$sharePrice,
            "type" => $prefunding ? TradeOrderType::Prefunding : TradeOrderType::Market,
            // "notes" => "From APIv1",
            "status" => TradeOrderStatus::Submitted,
            "complementaryOrderId" => $tradeOrder?->id,
            "fees" => (string)$fees,
            "taxes" => "0",
        ];
        $response = $this->client->tradeOrder()->create([
            'json' => $requestBody,
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Unable to create sell order. Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response),
            );
            throw new InvestmentNotAllowedException(
                'Sell order could not be made. Please try again or contact support if issue persists.',
            );
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            TradeOrder::class,
        );
    }

    /**
     * @throws \RuntimeException
     */
    public function processOrderPaymentOutcome(
        string $orderId,
        bool $success,
        bool $verify = true,
    ): ScaOutcome {
        $requestBody = [
            'success' => $success,
            'verify' => $verify,
        ];
        $response = $this->client->tradeOrder()->createPaymentOutcome(
            $orderId,
            ['json' => $requestBody],
        );
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Failed to update trade order #{$orderId} status. Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response),
            );
            throw new \RuntimeException("Unable to submit payment outcome for trade order #{$orderId}");
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            ScaOutcome::class,
        );
    }

    /**
     * Do a soft pre-check for whether there is enough money in the wallet for the payment.
     *
     * If wallet balance is not in session (usually due to SCA verification being required).
     *
     * Then return true and delegate wallet balance checking to Mangopay when making the
     * transaction.
     */
    public function checkBalanceAvailable(float $amount): bool
    {
        $sessionBalance = $this->requestStack->getSession()->get('balance', null);
        $walletScaRequired = $this->requestStack->getSession()->get('walletScaRequired', true);
        if ($sessionBalance === null || $walletScaRequired) {
            // Delegate balance checking to backoffice/Mangopay
            $this->logger->debug("Wallet balance pre-check skipped - not in session");
            return true;
        }
        $walletBalance = (float)str_replace(' ', '', $sessionBalance);
        return $walletBalance >= $amount;
    }

    public function checkRetentionAllowed(int $numberOfShares, int $sharesToKeep, int $min, int $max): bool
    {
        $minShareRetention = ($numberOfShares * $min) / 100;
        $maxShareRetention = ($numberOfShares * $max) / 100;
        return ($sharesToKeep == 0 || $minShareRetention <= $sharesToKeep) && ($sharesToKeep <= $maxShareRetention);
    }

    public function sumUnsettledMonthlyShareTrades(int $assetId): int|float
    {
        $unsettledThisMonth = [];
        $monthlyInvestmentsInAsset = 0;
        try {
            $unsettledThisMonth = $this->portfolioService->retrievePortfolioUnsettled(true);
            $this->logger->debug("unsettled ths month", $unsettledThisMonth);
        } catch (\Throwable $th) {
            $this->logger->error('Unable to retrieve portfolio unsettled share trades this month', [$th->getMessage()]);
        }
        $monthlyTrades = $this->unsettledTradesThisMonthPerAsset($unsettledThisMonth);
        if (array_key_exists($assetId, $monthlyTrades)) {
            $monthlyInvestmentsInAsset = $monthlyTrades[$assetId];
        }
        return $monthlyInvestmentsInAsset;
    }

    /**
     * @param ShareTrade[] $shareTrades
     * @return array
     */
    public function unsettledTradesThisMonthPerAsset(array $shareTrades): array
    {
        $currentDate = new \DateTime();
        $rangeStartDate = (new \DateTimeImmutable())
            ->setDate($currentDate->format('Y'), $currentDate->format('m'), 1)
            ->setTime(0, 0);
        $rangeEndDate = $rangeStartDate->modify('+1 month');
        $totalInvestedPerAsset = [];
        foreach ($shareTrades as $inv) {
            if (
                $inv->createdAt >= $rangeStartDate
                && $inv->createdAt < $rangeEndDate
                && $inv->status == TradeStatus::Unsettled
            ) {
                if (array_key_exists($inv->assetId, $totalInvestedPerAsset)) {
                    $totalInvestedPerAsset[$inv->assetId] += $inv->tradeValue;
                    $totalInvestedPerAsset[$inv->assetId] = (string)round($totalInvestedPerAsset[$inv->assetId], 2);
                } else {
                    $totalInvestedPerAsset[$inv->assetId] = $inv->tradeValue;
                }
            }
        }
        return $totalInvestedPerAsset;
    }

    public function calculateStampDuty(string|float $amount): int
    {
        if ($amount < 1000) {
            return 0;
        }
        return (int)ceil($amount / 1000) * 5;
    }

    public function calculateStampDutyDue(
        string|float $existingAmount,
        string|float $newAmount,
    ): int {
        $existingStampDuty = $this->calculateStampDuty($existingAmount);
        $newTotalStampDuty = $this->calculateStampDuty($existingAmount + $newAmount);
        return (int)round($newTotalStampDuty - $existingStampDuty);
    }

    public function checkUserCanInvest(): bool
    {
        if (!$this->requestStack->getSession()->get('authenticated')) {
            throw new \Exception("Not logged in.");
        }
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        $cooloffEndTimeStamp = $obp->cooloffEnd?->getTimestamp();
        if (empty($userInfo)) {
            throw new \Exception("Not fully authenticated. Trying logging out and back in again.");
        }
        if (!$userInfo['registration_complete'] && $userInfo['ob_step'] < 5) {
            throw new InvestmentNotAllowedException("You cannot invest until you have completed onboarding.");
        }
        if (!$userInfo['has_been_approved']) {
            throw new InvestmentNotAllowedException("You cannot invest until your account has been approved.");
        }
        if (!($userInfo['sca_status'] == ScaStatus::Active->value)) {
            throw new InvestmentNotAllowedException("Your cannot invest until you have setup Strong Customer Authentication (SCA).");
        }
        if (
            $this->onboardingService->needsCheckup($userInfo)
            || !$this->onboardingService->isAllowedToInvest($obp)
        ) {
            throw new InvestmentNotAllowedException("Your cannot invest until have completed your profile's pending actions.");
        }
        if (!(time() > $cooloffEndTimeStamp)) {
            throw new InvestmentNotAllowedException("Your cannot invest until the cooling off period has ended.");
        }
        if ($this->verificationService->needsIdentityVerification()) {
            throw new InvestmentNotAllowedException("Your cannot invest until have completed your profile's pending actions.");
        }
        return true;
    }

    /**
     * @throws InvestmentNotAllowedException
     */
    public function takeOrderPayment(
        string $orderId,
        float|string $totalToPay,
        bool $sca = true,
    ): ScaAction {
        $this->logger->info('IN takeOrderPayment');

        $requestBody = [
            "amount" => (string)$totalToPay,
            "sca" => $sca,
        ];
        $response = $this->client->tradeOrder()->createPayment(
            $orderId,
            ['json' => $requestBody],
        );
        $responseBody = $this->client->getContent($response);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error('Error creating transfer for trade order payment: ', $responseBody);
            // Payment could not be taken, so mark cancel the by order
            $this->processOrderPaymentOutcome($orderId, false, false);

            if (array_key_exists("detail", $responseBody)) {
                if (str_contains($responseBody['detail'], "Insufficient funds")) {
                    throw new InvestmentNotAllowedException('Insufficient wallet balance to make payment');
                }
            }
            throw new InvestmentNotAllowedException(
                'Payment could not be made. Please try again or contact support if issue persists.',
            );
        }
        return $this->denormalizer->denormalize(
            $responseBody,
            ScaAction::class,
        );
    }

    /**
     * @throws InvestmentNotAllowedException
     */
    private function validateInvestmentAmount(AssetProduct $asset, int $numberOfShares, ?TradeOrder $tradeOrder = null): bool
    {
        /**
         * Checks several things
         * - Must be at least 1 share
         * - Must be enough shares available (either across the entire asset or the specific trade order)
         * - Must be within min-max commit
         */
        if ($numberOfShares == 0) {
            $this->logger->warning("Must invest at least 1 share");
            throw new InvestmentNotAllowedException('Investments must be for at least 1 share');
        }
        $sharesAvailable = $tradeOrder ? $tradeOrder->sharesAvailable : $asset->sharesAvailable;
        if ($sharesAvailable < $numberOfShares) {
            $this->logger->warning(
                "Not enough shares available",
                ['wanted' => $numberOfShares, 'available' => $sharesAvailable],
            );
            throw new InvestmentNotAllowedException('Not enough shares available to fulfil your request');
        }

        // $this->logger->debug("data sources", ['asset' => $asset, 'order' => $tradeOrder]);

        $sharePrice = $tradeOrder ? $tradeOrder->pricePerShare : $asset->pricePerShare;
        $minShares = $tradeOrder?->minimumShares ?: 1;
        $maxShares = $tradeOrder ? ($tradeOrder?->maximumShares ?? $tradeOrder->numberOfShares) : $asset->numberOfShares;
        $maxShares = min($sharesAvailable, $maxShares);
        $minValue = number_format($sharePrice * $minShares, 2);
        $maxValue = number_format($sharePrice * $maxShares, 2);
        if ($numberOfShares < $minShares || $maxShares < $numberOfShares) {
            $errorMessage = "The investment value must be between £{$minValue} ({$minShares} shares) and £{$maxValue} ({$maxShares} shares)";
            $this->logger->warning($errorMessage);
            throw new InvestmentNotAllowedException($errorMessage);
        }
        return true;
    }

}
