<?php

namespace App\Service;

use App\Dto\AccountSummary;
use App\Dto\AssetSummary;
use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Payout;
use App\Entity\User;
use App\Repository\InvestmentRepository;
use App\Repository\PayoutRepository;
use App\Service\Util\Helper;
use Psr\Log\LoggerInterface;

class ReportsService
{
    public function __construct(
        private InvestmentRepository $investmentRepository,
        private PayoutRepository $payoutRepository,
        private LoggerInterface $logger,
    ) {}

    /**
     * @deprecated deprecated with the introduction of divestment payouts
     *
     * Regarding deprecation: Can optionally upgrade the SQL statements to accommodate new divestment payouts
     *
     * Returns an AccountSummary object
     *
     * Payout aggregation and investment aggregation preformed via MySQL
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in 'unit' mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getUserAccountSummary(
        User $user,
        ?string $mode = null,
    ): AccountSummary {
        $investments = $this->getUserInvestments($user);
        $totalInvestmentValues = $this->getTotalInvestmentStats($investments);
        $totalInvestmentCount = $totalInvestmentValues['totalInvestmentCount'];
        $totalInvestmentValue = $totalInvestmentValues['totalInvestmentValue'];
        $totalDividend = $this->getTotalDividendAmount($user);
        $totalCapitalAppreciation = $this->getTotalCapitalAppreciationAmount($user);
        $totalReturn = $totalDividend + $totalCapitalAppreciation;
        if ($mode) {
            $montlySummmary = $this->getMonthlyPayoutSummary($user, $mode);
        }

        $accountSummary = new AccountSummary(
            $totalInvestmentCount ?? 0,
            $totalInvestmentValue ?? 0,
            $totalReturn ?? 0,
            $totalDividend ?? 0,
            $totalCapitalAppreciation ?? 0,
            $montlySummmary ?? [],
        );

        return $accountSummary;
    }

    /**
     * Returns an AccountSummary object
     *
     * Payout aggregation and investment aggregation preformed via PHP
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in 'unit' mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getAccountSummary(
        array $investments,
        array $payouts,
        ?string $mode = null,
    ): AccountSummary {
        $totalInvestmentValues = $this->getTotalInvestmentStats($investments);
        $totalInvestmentCount = $totalInvestmentValues['totalInvestmentCount'];
        $totalInvestmentValue = $totalInvestmentValues['totalInvestmentValue'];

        if ($mode) {
            $payoutSummary = $this->getTotalPayoutSummary(
                $payouts,
                $investments,
                Helper::generatePastMonthsStrings(),
                $mode,
            );
        } else {
            $payoutSummary = $this->getTotalPayoutSummary($payouts, $investments);
        }

        $totalReturn = $payoutSummary['totalReturn'];
        $totalDividend = $payoutSummary['totalDividend'];
        $totalCapitalAppreciation = $payoutSummary['totalCapitalAppreciation'];
        $montlySummmary = $payoutSummary['montlySummmary'];

        $accountSummary = new AccountSummary(
            $totalInvestmentCount ?? 0,
            $totalInvestmentValue ?? 0,
            $totalReturn ?? 0,
            $totalDividend ?? 0,
            $totalCapitalAppreciation ?? 0,
            $montlySummmary ?? [],
        );

        return $accountSummary;
    }

    /**
     * Returns an array of AssetSummary objects
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in 'unit' mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getAssetSummaries(
        array $investments,
        array $payouts,
        ?string $mode = null,
    ): array {
        $assetSummaries = [];
        $assetInvMap = $this->mapAssetIdsToInvestments($investments);
        $assetPayoutMap = $this->mapAssetIdsToPayouts($payouts);
        $months = null;
        if ($mode) {
            $months = Helper::generatePastMonthsStrings();
        }

        foreach ($assetInvMap as $assetId => $assetInvestments) {
            $totalInvestmentValues = $this->getTotalInvestmentStats($assetInvestments);
            $investmentCount = $totalInvestmentValues['totalInvestmentCount'];
            $investmentValue = $totalInvestmentValues['totalInvestmentValue'];
            $payoutSummary = null;
            $assetPayouts = [];
            if (isset($assetPayoutMap[$assetId])) {
                $assetPayouts = $assetPayoutMap[$assetId];
            }
            if ($mode and $months) {
                // $this->logger->debug("Month mode");
                $payoutSummary = $this->getTotalPayoutSummary(
                    $assetPayouts,
                    $assetInvestments,
                    $months,
                    $mode,
                );
            } else {
                // $this->logger->debug("Base mode");
                $payoutSummary = $this->getTotalPayoutSummary(
                    $assetPayouts,
                    $assetInvestments,
                );
            }

            $assetSummary = new AssetSummary(
                $investmentCount ?? 0,
                $investmentValue ?? 0,
                $payoutSummary['totalReturn'] ?? 0,
                $payoutSummary['totalDividend'] ?? 0,
                $payoutSummary['totalCapitalAppreciation'] ?? 0,
                $payoutSummary['montlySummmary'] ?? [],
            );
            // $this->logger->debug($assetId, [$payoutSummary['montlySummmary']]);

            $assetSummaries[$assetId] = $assetSummary;
        }

        return $assetSummaries;
    }

    /**
     * Returns an AssetSummary object
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in 'unit' mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getAssetSummary(
        int $assetId,
        array $investments,
        array $payouts,
        ?string $mode = null,
    ): AssetSummary {
        $assetInvMap = $this->mapAssetIdsToInvestments($investments);
        $assetPayoutMap = $this->mapAssetIdsToPayouts($payouts);
        $months = null;
        if ($mode) {
            $months = Helper::generatePastMonthsStrings();
        }

        $investments = $assetInvMap[$assetId];
        if ($investments) {
            $totalInvestmentValues = $this->getTotalInvestmentStats($investments);
            $investmentCount = $totalInvestmentValues['totalInvestmentCount'];
            $investmentValue = $totalInvestmentValues['totalInvestmentValue'];
            $assetPayouts = [];
            if (isset($assetPayoutMap[$assetId])) {
                $assetPayouts = $assetPayoutMap[$assetId];
            }
            if ($mode and $months) {
                // $this->logger->debug("Month mode");
                $payoutSummary = $this->getTotalPayoutSummary(
                    $assetPayouts,
                    $assetInvMap[$assetId],
                    $months,
                    $mode,
                );
            } else {
                // $this->logger->debug("Base mode");
                $payoutSummary = $this->getTotalPayoutSummary(
                    $assetPayouts,
                    $assetInvMap[$assetId],
                );
            }

            $assetSummary = new AssetSummary(
                $investmentCount ?? 0,
                $investmentValue ?? 0,
                $payoutSummary['totalReturn'] ?? 0,
                $payoutSummary['totalDividend'] ?? 0,
                $payoutSummary['totalCapitalAppreciation'] ?? 0,
                $payoutSummary['montlySummmary'] ?? [],
            );
            return $assetSummary;
        }

        return $assetSummary = new AssetSummary(0, 0, 0, 0, 0, []);
    }

    /**
     * Returns an array of investments related to a user
     *
     * Only finds investments which are in state 'approved' or 'settled'
     */
    public function getUserInvestments(User $user): ?array
    {
        $qb = $this->investmentRepository->findAllQuery(
            $user,
            [],
            ['approved', 'settled'],
        );
        return $qb->getQuery()->getResult();
    }

    /**
     * Returns the total capital appreciation amount a user has earned
     */
    public function getTotalCapitalAppreciationAmount(User $user): float
    {
        try {
            $capApp = $this->payoutRepository->findAggregatedPayoutAmounts($user, [1]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error calculating total capital appreciation amount for user id: '
                    . $user->getId()
                    . ' Error Message: '
                    . $e->getMessage(),
            );
            return 0;
        }

        $investments = $this->investmentRepository
            ->findAllQuery($user, [], ['approved', 'settled'])
            ->getQuery()
            ->getResult();

        if ($investments) {
            $capApp = $this->reduceCapitalAppreciation($capApp, $investments);
        }

        return $capApp;
    }

    /**
     * Returns the total dividend amount a user has earned
     */
    public function getTotalDividendAmount(User $user): float
    {
        try {
            $result = $this->payoutRepository->findAggregatedPayoutAmounts($user, [0]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error calculating total dividend amount for user id: '
                    . $user->getId()
                    . ' Error Message: '
                    . $e->getMessage(),
            );
            return 0;
        }

        return (float) $result;
    }

    /**
     * Returns the total amount a user has earned
     * Does not remove remaining investment value from profit share payouts
     */
    public function getUnfilteredTotalReturnAmount(User $user): float
    {
        try {
            $result = $this->payoutRepository->findAggregatedPayoutAmounts($user, [
                0,
                1,
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error calculating total return amount for user id: '
                    . $user->getId()
                    . ' Error Message: '
                    . $e->getMessage(),
            );
            return 0;
        }

        return (float) $result;
    }

    /**
     * Returns the total amount a user has invested
     *
     * Caution: This value is the total a user has invested over the accounts lifetime. It does not take into account investment liquidation.
     */
    public function getTotalInvestedAmount(User $user): float
    {
        try {
            $result = $this->investmentRepository->findAggregatedInvestmentTotal(
                $user,
                [],
                ['approved', 'settled'],
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error calculating total return amount for user id: '
                    . $user->getId()
                    . ' Error Message: '
                    . $e->getMessage(),
            );
            return 0;
        }

        return (float) $result;
    }

    /**
     * Returns all the payouts related to a user
     */
    public function getAllUserPayouts(User $user): ?array
    {
        return $this->payoutRepository->findByUser($user);
    }

    /**
     * Returns an overview of the payouts sent to a user over the previous 12 months
     *
     * Return format: [<yy-mm> => amount]
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in 'unit' mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getMonthlyPayoutSummary(
        User $user,
        string $mode = 'cumulative',
    ): array {
        $months = Helper::generatePastMonthsStrings();
        $payouts = $this->getAllUserPayouts($user);
        $montlySummmary = array_fill_keys($months, 0);

        foreach ($payouts as $payout) {
            $payoutAmount = $payout->getPayoutAmount();
            $payoutDate = date(
                'Y-m',
                strtotime(Helper::formatDate($payout->getDueDate())),
            );
            if (in_array($payoutDate, $months)) {
                $montlySummmary[$payoutDate] += $payoutAmount;
            }
        }

        if ($mode == 'cumulative') {
            $payoutValues = array_values($montlySummmary);
            $cumulativeValues = $this->getRunningTotal($payoutValues);
            $montlySummmary = array_combine(
                array_keys($montlySummmary),
                $cumulativeValues,
            );
        }

        return $montlySummmary;
    }

    /**
     * Converts an array of numbers into a running total
     *
     * Example: [23, 18, 5, 8, 10, 16] => [23, 41, 46, 54, 64, 80]
     */
    public function getRunningTotal(array $array): array
    {
        $generator = function (array $array) {
            $total = 0;
            foreach ($array as $key => $value) {
                $total += $value;
                yield $key => $total;
            }
        };
        return iterator_to_array($generator($array));
    }

    /**
     * Returns the total amount invested from an array of investments
     */
    public function getTotalInvestmentValue(array $investments): float
    {
        $total = 0;
        foreach ($investments as $inv) {
            if ($inv instanceof Investment) {
                $heldInvestment =
                    $inv->getInvestmentValue() - $inv->getDivestedAmount();
                if ($heldInvestment < 0) {
                    $heldInvestment = 0;
                }

                $total += $heldInvestment;
            }
        }
        return $total;
    }

    /**
     * Returns a summary of totals from an array of payouts
     *
     * Optional arg: $months
     *   - An array of months
     *   - Format: [<yy-mm>]
     *   - If left null, a monthly summary will not be returned
     *
     * Optional arg: $mode
     *   - In mode 'cumulative' the monthly summary is a running total (previous payouts included in each month totals)
     *   - If mode is changed to anything else then the monthly summary is in unit mode (each month represents the exact amount sent for the given month)
     *   - Only the previous 12 months are included in both modes
     *   - If not passed as arg monthly summary not included
     */
    public function getTotalPayoutSummary(
        array $payouts,
        array $investments,
        ?array $months = null,
        string $mode = 'cumulative',
    ): array {
        $totalDividend = 0;
        $totalCapitalAppreciation = 0;
        $montlySummmary = [];

        if ($months) {
            $montlySummmary = array_fill_keys($months, 0);
        }

        foreach ($payouts as $payout) {
            if ($payout instanceof Payout) {
                $type = $payout->getPayoutType();
                $amount = $payout->getPayoutAmount();
                $payoutDate = date(
                    'Y-m',
                    strtotime(Helper::formatDate($payout->getDueDate())),
                );
                if ($type == 0) {
                    $totalDividend += $amount;
                }
                if ($type == 1) {
                    if ($payout->getAsset()) {
                        $originalSharePrice = $payout->getAsset()->getPricePerShare()
                        ?? 0;
                        $amount -= $originalSharePrice * $payout->getShareholding();
                    }
                    $totalCapitalAppreciation += $amount;
                }
                if ($months) {
                    if (in_array($payoutDate, $months)) {
                        $montlySummmary[$payoutDate] += $amount;
                    }
                }
            }
        }

        $totalCapitalAppreciation = $this->reduceCapitalAppreciation(
            $totalCapitalAppreciation,
            $investments,
        );
        $totalReturn = $totalDividend + $totalCapitalAppreciation;

        if (!empty($montlySummmary) and $mode == 'cumulative') {
            $payoutValues = array_values($montlySummmary);
            $cumulativeValues = $this->getRunningTotal($payoutValues);
            $montlySummmary = array_combine(
                array_keys($montlySummmary),
                $cumulativeValues,
            );
        }

        return [
            'totalReturn' => $totalReturn,
            'totalDividend' => $totalDividend,
            'totalCapitalAppreciation' => $totalCapitalAppreciation,
            'montlySummmary' => $montlySummmary,
        ];
    }

    /**
     * Returns a map fron asset ids to Investment objects
     *
     * Return format: assetId : $investments[]
     */
    public function mapAssetIdsToInvestments(array $investments): array
    {
        $assetIdsToInvestmentsMap = [];
        foreach ($investments as $inv) {
            if ($inv instanceof Investment) {
                $assetIdsToInvestmentsMap[$inv->getAssetId()][] = $inv;
            }
        }

        return $assetIdsToInvestmentsMap;
    }

    /**
     * Returns a map fron asset ids to Payout objects
     *
     * Return format: assetId : $payouts[]
     */
    public function mapAssetIdsToPayouts(array $payouts): array
    {
        $assetIdsToPayoutsMap = [];
        foreach ($payouts as $payout) {
            if ($payout instanceof Payout) {
                if ($payout->getAsset()) {
                    $assetId = $payout->getAsset()->getId();
                } elseif ($payout->getInvestment()) {
                    $assetId = $payout->getInvestment()->getAssetId();
                }
                if ($assetId) {
                    $assetIdsToPayoutsMap[$assetId][] = $payout;
                }
            }
        }

        return $assetIdsToPayoutsMap;
    }

    /**
     * Returns the total number investments and the total value of the investments
     *   - Investment liquidation and assets sold off taken into account
     */
    public function getTotalInvestmentStats(array $investments): array
    {
        $totalInvestmentCount = 0;
        $totalInvestmentValue = 0;
        foreach ($investments as &$inv) {
            if ($inv instanceof Investment) {
                if ($inv->getDivestedShares() < $inv->getShareAmount()) {
                    $asset = $inv->getOffering()->getAsset();
                    $heldInvestment =
                        $inv->getInvestmentValue() - $inv->getDivestedAmount();
                    $isAssetSold = $this->isAssetSoldOff($asset);
                    if ($isAssetSold) {
                        continue;
                    }

                    if ($heldInvestment < 0) {
                        $heldInvestment = 0;
                    }

                    $totalInvestmentValue += $heldInvestment;
                    $totalInvestmentCount++;
                }
            }
        }

        return [
            'totalInvestmentCount' => $totalInvestmentCount,
            'totalInvestmentValue' => $totalInvestmentValue,
        ];
    }

    /**
     * Helper for determining if an asset has been marked as "sold_off"
     */
    public function isAssetSoldOff(Asset $asset): bool
    {
        $soldField = $asset->getAddedField('sold_off');
        if ($soldField) {
            if (
                strtolower($soldField->getValue()) === 'true'
                or $soldField->getValue() === '1'
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper for reducing the capital appreciation by current holding
     */
    public function reduceCapitalAppreciation(float $capApp, array $investments): float
    {
        foreach ($investments as $inv) {
            if ($inv instanceof Investment) {
                // check asset solf_off status
                $asset = $inv->getOffering()->getAsset();
                if ($this->isAssetSoldOff($asset)) {
                    $remainingValue =
                        $inv->getInvestmentValue() - $inv->getDivestedAmount();
                    $capApp -= $remainingValue;
                }
            }
        }

        return $capApp;
    }
}
