<?php

namespace App\Service;

use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\PayoutRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class AnalyticsService
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
        private OfferingRepository $offeringRepository,
        private InvestmentRepository $investmentRepository,
        private PayoutRepository $payoutRepository,
        private UserRepository $userRepository,
    ) {}

    public function getResourceCounts(): array
    {
        return [
            'asset' => $this->assetRepository->count([]),
            'offering' => $this->offeringRepository->count([]),
            'investment' => $this->investmentRepository->count([]),
            // 'payout' => $this->payoutRepository->count([]),
            // 'user' => $this->userRepository->count([]),
        ];
    }

    public function getAumByYear(): array
    {
        $aumByYear = $this->investmentRepository->getAumByYear();
        array_reverse($aumByYear);
        $previousYear = null;
        foreach ($aumByYear as $index => $yearlySummary) {
            if (is_null($previousYear)) {
                $aumByYear[$index]['yoy'] = 0;
                $previousYear = $yearlySummary['total'];
            } else {
                $aumByYear[$index]['yoy'] =
                    (($yearlySummary['total'] / $previousYear) - 1) * 100;
                $previousYear = $yearlySummary['total'];
            }
        }
        return array_reverse($aumByYear);
    }

    public function getUserRegisrationsByYear(): array
    {
        $userRegistrations = $this->userRepository->getUserRegistrationsYear();
        return array_reverse($userRegistrations);
    }

    public function getUserRegisrationsByMonth(): array
    {
        $userRegistrations = $this->userRepository->getUserRegistrationsMonth();
        return $userRegistrations;
    }

    public function getFirstPartyOfferings(): array
    {
        // currently won't handle sold-off assets
        $offerings = $this->offeringRepository->findAllFirstParty();
        return $offerings;
    }

    public function getNormalisedYields(): array
    {
        // currently won't handle sold-off assets
        $offerings = $this->offeringRepository->findAllFirstParty();
        $totalFundingGoal = $this->offeringRepository->findFirstPartyTotal();
        $normalisedYields = [];
        $overallYield = 0;
        foreach ($offerings as $offering) {
            $proportion = $offering->getFundingGoal() / $totalFundingGoal;
            $normalisedOfferingYield = $offering->getNetRentProjected() * $proportion;
            $overallYield += $normalisedOfferingYield;
            $normalisedYields[$offering->getId()] = $normalisedOfferingYield;
        }
        return [
            'totalOffered' => $totalFundingGoal,
            'overallYield' => $overallYield,
            'normalisedYields' => $normalisedYields,
        ];
    }

    public function getInvestmentsOverTime(array $filterDates = []): array
    {
        $invOverTimeYear =
            $this->investmentRepository->findInvestmentsOverTime($filterDates);
        return $invOverTimeYear;
    }

    public function getRelistingsOverTimeMonth(): array
    {
        $relistingsOverTimeMonth = $this->offeringRepository->findRelistingsByMonth();
        return $relistingsOverTimeMonth;
    }

    public function getRelistingsOverTimeYear(): array
    {
        $relistingsOverTimeYear = $this->offeringRepository->findRelistingsByYear();
        return $relistingsOverTimeYear;
    }

    public function getUniqueInvestors(): array
    {
        $uniqueInvestorsPerMonth = $this->investmentRepository->findUniqueInvestors();
        return $uniqueInvestorsPerMonth;
    }

    public function getInvestorInvestmentCounts(?int $limit = null): array
    {
        $investorInvestmentCounts =
            $this->investmentRepository->findInvestorInvestmentCounts($limit);
        return $investorInvestmentCounts;
    }

    public function getUserReferrals(array $filterDates = []): array
    {
        $referrals = $this->investmentRepository->findUserReferrals($filterDates);
        return $referrals;
    }

    public function getUsersOnboardedInvested(): array
    {
        $usersOnboardedInvested = $this->userRepository->getUsersOnboardedInvested();
        return $usersOnboardedInvested;
    }

    public function getLoginActivityLastYear(string $grouping = 'date'): array
    {
        // Search range is from the 1st day of the month a year ago until time of query
        $dateStart = new \DateTime('first day of this month');
        $dateStart->sub(new \DateInterval('P1Y'))->setTime(0, 0);
        return $this->userRepository->getLoginActivity(
            $dateStart,
            new \DateTime('first day of next month')->setTime(0, 0),
            $grouping,
        );
    }

    public function getAuthAccessTokenActivity(string $grouping = 'date'): array
    {
        return $this->userRepository->getAuthAccessTokenActivity($grouping);
    }

    public function getDividendSummaryByAsset(): array
    {
        return $this->payoutRepository->getDividendSummaryByAsset();
    }

    public function getRetailInvestmentsSummary(string $groupBy): array
    {
        return $this->investmentRepository->getInvestmentsSummary($groupBy);
    }

    public function getAssetOfferingMap(): array
    {
        /** @var \App\Entity\Offering[] */
        $offerings = $this->offeringRepository->findAllFirstParty();
        $assetOfferingMap = [];
        foreach ($offerings as $offering) {
            $assetOfferingMap[$offering->getAsset()->getId()] = $offering;
        }
        return $assetOfferingMap;
    }

    public function getMetricLeaderboard(
        string $metric,
        \DateTimeInterface $dateStart,
        \DateTimeInterface $dateEnd,
    ): array {
        return match ($metric) {
            'buys' => $this->investmentRepository->findUserSettlementsCountInDateRange(
                $dateStart,
                $dateEnd,
            ),
            'sells' => $this->investmentRepository->findUserSalesCountInDateRange(
                $dateStart,
                $dateEnd,
            ),
            'positions' => $this->investmentRepository->findUserPositionsCount(),
            'exits' => $this->payoutRepository->findUserPayoutsCountInDateRange(
                1,
                $dateStart,
                $dateEnd,
            ),
            default => [],
        };
    }
}
