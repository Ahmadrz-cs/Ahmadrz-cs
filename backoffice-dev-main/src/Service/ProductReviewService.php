<?php

namespace App\Service;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use Psr\Log\LoggerInterface;

/**
 * Querying support service for products
 */
class ProductReviewService
{
    public function __construct(
        private LoggerInterface $logger,
        private InvestmentRepository $investmentRepository,
        private OfferingRepository $offeringRepository,
        private HoldingRepository $holdingRepository,
    ) {}

    public function getAssetListingSummary(): array
    {
        $listingsSummary = $this->offeringRepository->queryAssetListingSummary();
        $investmentsSummary = $this->investmentRepository->queryInvestmentSummary();
        $pendingInvestmentsSummary =
            $this->investmentRepository->queryInvestmentSummary(settledOnly: false);
        return $this->aggregateAssetListingSummary(
            $listingsSummary,
            $investmentsSummary,
            $pendingInvestmentsSummary,
        );
    }

    public function aggregateAssetListingSummary(
        array $listingsSummary,
        array $investmentsSummary,
        array $pendingInvestmentsSummary,
    ): array {
        $assetIds = array_column($listingsSummary, 'assetId');
        if (empty($assetIds)) {
            return [];
        }

        // The "alt" prefixed values are derived from the fundingGoal (divided by pricePerShare)
        // The non-alt values are derived from the numberOfShares (times the pricePerShare)
        // Needed because the numberOfShares in many relistings are incorrect and just show the primary listing
        // Only the funding goal is correct on relistings
        // Should ideally fix in a db normalisation exercise in future
        $template = [
            'listings' => 0,
            'relistings' => 0,
            'shares' => [
                'listed' => 0,
                'altListed' => 0,
                'traded' => 0,
                'prefunded' => 0,
                'pendingListed' => 0,
                'altPendingListed' => 0,
                'pendingTraded' => 0,
            ],
            'value' => [
                'listed' => 0,
                'altListed' => 0,
                'traded' => 0,
                'prefunded' => 0,
                'pendingListed' => 0,
                'altPendingListed' => 0,
                'pendingTraded' => 0,
            ],
        ];
        $summary = array_fill_keys($assetIds, $template);
        // Populate the offering/listings data first
        foreach ($listingsSummary as $summaryRow) {
            $assetId = $summaryRow['assetId'];
            $summary[$assetId]['listings'] += $summaryRow['listings'];
            $summary[$assetId]['relistings'] += $summaryRow['relistings'];
            if (OfferingLifecycle::STATE_PUBLISHED == $summaryRow['status']) {
                // Published listings only
                $summary[$assetId]['shares']['listed'] += $summaryRow['sharesListed'];
                $summary[$assetId]['shares']['altListed'] +=
                    $summaryRow['equivalentSharesListed'];
                $summary[$assetId]['value']['listed'] += $summaryRow['valueListed'];
                $summary[$assetId]['value']['altListed'] += $summaryRow['fundingGoal'];
            } else {
                // Listings pending publication, e.g. draft, submitted, approved
                $summary[$assetId]['shares']['pendingListed'] +=
                    $summaryRow['sharesListed'];
                $summary[$assetId]['shares']['altPendingListed'] +=
                    $summaryRow['equivalentSharesListed'];
                $summary[$assetId]['value']['pendingListed'] +=
                    $summaryRow['valueListed'];
                $summary[$assetId]['value']['altPendingListed'] +=
                    $summaryRow['fundingGoal'];
            }
        }

        // Not distinguishing between investments in primary and relisted
        // Not relevant for the listings info, only useful for shareholder composition
        foreach ($investmentsSummary as $summaryRow) {
            $assetId = $summaryRow['assetId'];
            if ('prefunding' == $summaryRow['investmentType']) {
                $summary[$assetId]['shares']['prefunded'] += $summaryRow['shares'];
                $summary[$assetId]['value']['prefunded'] += $summaryRow['value'];
            } else {
                $summary[$assetId]['shares']['traded'] += $summaryRow['shares'];
                $summary[$assetId]['value']['traded'] += $summaryRow['value'];
            }
        }
        foreach ($pendingInvestmentsSummary as $summaryRow) {
            $assetId = $summaryRow['assetId'];
            $summary[$assetId]['shares']['pendingTraded'] += $summaryRow['shares'];
            $summary[$assetId]['value']['pendingTraded'] += $summaryRow['value'];
        }
        return $summary;
    }

    public function filterAssetListingSummary(
        array $summary,
        array $filters = [],
    ): array {
        if ($filters['hideNoInvestors'] ?? false) {
            $summary = array_filter(
                $summary,
                fn($s) => 0 < ($s['shares']['traded'] + $s['shares']['pendingTraded']),
            );
        }
        if ($filters['hideNoListings'] ?? false) {
            $summary = array_filter($summary, fn($s) => 0 < $s['shares']['altListed']);
        }
        if ($filters['hideNoAvailable'] ?? false) {
            $summary = array_filter(
                $summary,
                fn($s) => (
                    0
                    < (
                        $s['shares']['altListed'] - $s['shares']['traded']
                        - $s['shares']['pendingTraded']
                    )
                ),
            );
        }
        return $summary;
    }
}
