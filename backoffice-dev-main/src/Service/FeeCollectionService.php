<?php

namespace App\Service;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Repository\OfferingRepository;
use App\Service\AppSettingService;
use Psr\Log\LoggerInterface;

class FeeCollectionService
{
    public const PREFERRED_FEE_WALLET = 'yieldersFeeWallet';

    public function __construct(
        private LoggerInterface $logger,
        private MonthEndService $monthEndService,
        private AppSettingService $appSettingService,
        private OfferingRepository $offeringRepository,
    ) {}

    /**
     * @param array<string, string> $additionalWallets
     * @return string[]
     */
    public function getFeeWallets(array $additionalWallets = []): array
    {
        $settings = $this->appSettingService->getMultiple([
            'yieldersFeeWallet',
            'ypmlFeeWallet',
        ]);
        // Don't remove duplicates as it can hide potential issues
        $settings = array_merge($settings, $additionalWallets);
        return array_filter($settings, fn($s) => !empty($s));
    }

    public function findMonthlyRelistings(
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        ?int $assetId = null,
    ): array {
        $filters = [
            'sell_investment' => 1,
            'lifecycleStatus' => [
                OfferingLifecycle::STATE_DRAFT,
                OfferingLifecycle::STATE_SUBMITTED,
                OfferingLifecycle::STATE_APPROVED,
                OfferingLifecycle::STATE_PUBLISHED,
            ],
            'createdAt_gte' => $start,
            'createdAt_lt' => $end,
        ];
        if ($assetId) {
            $filters['assetId'] = $assetId;
        }
        $monthlyRelistings = $this->offeringRepository
            ->buildQueryWithAssociations($filters, ['id' => 'DESC'])
            ->getResult();
        return $monthlyRelistings;
    }

    public function generateRelistingFeeTransfers(
        TransferOrder $transferOrder,
        array $feeSummary,
        string $creditWalletId,
    ): TransferOrder {
        foreach ($feeSummary as $assetFeeSummary) {
            /** @var Offering $relisting */
            $relisting = reset($assetFeeSummary['relistings']);
            $asset = $relisting[0]->getAsset();
            $transfer = new TransferRequest();
            $transfer->setAsset($asset);
            $transfer->setAmount($assetFeeSummary['totalRelistingFees']);
            $transfer->setDebitWalletId($asset->getHoldWalletId());
            $transfer->setCreditWalletId($creditWalletId);
            $transfer->setDescription($this->monthEndService->createStructuredDescription(
                'Relisting fees',
                $asset,
                $transferOrder->getScheduledFor(),
            ));
            $transferOrder->addTransfer($transfer);
        }
        return $transferOrder;
    }

    /**
     * @param Offering[] $relistings
     */
    public function groupMonthlyRelistings(array $relistings): array
    {
        $feeSummary = [];
        foreach ($relistings as $relisting) {
            $assetId = $relisting->getAsset()->getId();
            $sellerId = $relisting->getSellInvestment()->getUser()->getId();
            $feeSummary[$assetId]['relistings'][$sellerId][] = $relisting;
        }
        return $feeSummary;
    }

    /**
     * @param Offering[] $relistings
     */
    public function estimateRelistingFees(array $relistings): array
    {
        $feeSummary = $this->groupMonthlyRelistings($relistings);
        foreach ($feeSummary as $assetId => $relistingInfo) {
            $feeSummary[$assetId]['totalRelistingFees'] = 0;
            foreach ($relistingInfo['relistings'] as $userId => $userRelistings) {
                /** @var Offering[] $userRelistings */
                $amount = array_reduce(
                    $userRelistings,
                    fn($carry, Offering $item) => $carry += $item->getFundingGoal(),
                    0,
                );
                $fee = $this->calculateRelistingFee(
                    $amount,
                    $userRelistings[0]->getAsset()->getFeesGrouped()['relisting'] ?? [],
                    $userRelistings[0]->getSellInvestment()->getUser(),
                );
                $feeSummary[$assetId]['totalRelistingFees'] += $fee;

                // Extras per-user breakdown
                if (!isset($feeSummary[$assetId]['userSummary'][$userId])) {
                    $feeSummary[$assetId]['userSummary'][$userId]['amount'] = $amount;
                    $feeSummary[$assetId]['userSummary'][$userId]['fee'] = $fee;
                } else {
                    $feeSummary[$assetId]['userSummary'][$userId]['amount'] += $amount;
                    $feeSummary[$assetId]['userSummary'][$userId]['fee'] += $fee;
                }

                // Convert summaries back to string
                $feeSummary[$assetId]['totalRelistingFees'] =
                    (string) $feeSummary[$assetId]['totalRelistingFees'];
                $feeSummary[$assetId]['userSummary'][$userId]['amount'] =
                    (string) $feeSummary[$assetId]['userSummary'][$userId]['amount'];
                $feeSummary[$assetId]['userSummary'][$userId]['fee'] =
                    (string) $feeSummary[$assetId]['userSummary'][$userId]['fee'];
            }
        }
        return $feeSummary;
    }

    /**
     * @param array<int, int> $feeBands
     */
    public function calculateRelistingFee(
        string $totalRelisted,
        array $feeBands,
        User $user,
    ): string {
        // Return immediately if any exemptions apply
        // Only Top Yielder exemption exists at the moment
        if ($user->getisVIP()) {
            return '0';
        }

        ksort($feeBands);
        $feeDue = 0;
        foreach ($feeBands as $bandStart => $bandFee) {
            if ($totalRelisted <= $bandStart) {
                break;
            }
            $feeDue = $bandFee;
        }
        return (string) $feeDue;
    }

    /**
     * @throws \InvalidArugmentException if there are no asset relations set on the order or any of its requests
     */
    public function regenerateDescriptions(TransferOrder $transferOrder): TransferOrder
    {
        // Primarily for updating the dates on descriptions if copying from another order
        foreach ($transferOrder->getTransfers() as $transfer) {
            // If the descriptions were generated the descriptions with createStructuredDescription before
            // They should follow a similar format
            // If not, this regenerate will make a bit of a mess that will need manual fixing
            $asset = $transfer->getAsset() ?? $transfer->getTransferOrder()->getAsset();
            if (is_null($asset)) {
                throw new \InvalidArgumentException(
                    'Transfer order or one or more requests missing an asset relation.',
                );
            }
            $coreDescription = explode(';', $transfer->getDescription())[0];
            $this->logger->warning($coreDescription);
            $transfer->setDescription($this->monthEndService->createStructuredDescription(
                $coreDescription,
                $asset,
                $transferOrder->getScheduledFor(),
            ));
        }
        return $transferOrder;
    }

    /**
     * @throws \InvalidArgumentException if income disaggregation order not given
     * Takes an income disaggregation order and foreach transfer, take a percent from that transfer as a relay
     */
    public function collectFeesFromTransferOrder(
        TransferOrder $feeCollection,
        TransferOrder $collectFromOrder,
        string $creditWalletId,
        float|int $feePercent,
        string $feeName,
    ): TransferOrder {
        // populate fee collection with relayed transfers
        $this->monthEndService->generateRelayTransfers(
            $feeCollection,
            $collectFromOrder->getTransfers(),
            $creditWalletId,
        );
        // Update the amount and description relevant for the fee collection
        foreach ($feeCollection->getTransfers() as $fee) {
            $asset = $fee->getAsset() ?? $fee->getTransferOrder()->getAsset();
            if (is_null($asset)) {
                throw new \InvalidArgumentException(
                    'Existing transfers missing asset relation',
                );
            }
            $amount = (string) round($feePercent * $fee->getAmount(), 2);
            $fee->setDescription($this->monthEndService->createStructuredDescription(
                $feeName,
                $asset,
                $feeCollection->getScheduledFor(),
            ));
            $fee->setAmount($amount);
        }
        return $feeCollection;
    }

    public function guessFeeBeingCollected(TransferOrder $transferOrder): ?string
    {
        $descriptions = [];
        foreach ($transferOrder->getTransfers() as $transfer) {
            $snippet = explode(';', $transfer->getDescription());
            if (!empty($snippet)) {
                $descriptions[] = $snippet[0];
            }
        }
        // Guess whether we are collecting
        // - Exclusively management fees
        // - Exclusively relisting fees
        // - Mixed of management and relisting fees
        // - Mix of custom fees - in which case, cannot trivially guess what fee the order is collecting
        return match (array_values(array_unique($descriptions))) {
            ['Yielders management fees', 'Relisting fees'] => 'Collect Yielders fees',
            ['Yielders management fees'] => 'Collect Yielders management fees',
            ['Relisting fees'] => 'Collect relisting fees',
            ['Yielders Property Management Ltd fees'] => 'Collect YPML fees',
            default => null,
        };
    }
}
