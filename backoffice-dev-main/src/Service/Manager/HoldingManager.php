<?php

namespace App\Service\Manager;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Repository\HoldingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class HoldingManager
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private HoldingRepository $holdingRepository,
    ) {}

    #[\Deprecated(
        'Switch to share trade system. Use shareTradeRepository::aggregateSharesInCirculation',
    )]
    public function getAssetShareholdings(?int $assetId = null): array
    {
        $assetShareHoldings =
            $this->holdingRepository->getShareHoldingsAggregate($assetId);
        return $assetShareHoldings;
    }

    #[\Deprecated(
        'Switch to share trade system. Use shareTradeRepository::aggregateAssetShareholdingsByUser',
    )]
    public function getShareholders(int $assetId, bool $active = true): array
    {
        $assetShareholders = [];
        $assetShareHoldings = $this->holdingRepository->getShareHoldings([
            'assetId' => $assetId,
            'currentHolding' => (int) $active,
        ]);
        foreach ($assetShareHoldings as $shareholder) {
            $assetShareholders[$shareholder['userId']] = $shareholder['currentHolding'];
        }
        return $assetShareholders;
    }

    #[\Deprecated(
        'Switch to share trade system. Use tradeOrderRepository to query for prefunding sell orders and then aggregate with DivestmentService::compileRepaymentProgress',
    )]
    public function getPrefundingShareholders(Asset $asset): array
    {
        /** @var \App\Repository\InvestmentRepository */
        $investmentRepository = $this->em->getRepository(\App\Entity\Investment::class);

        $offeringIds = array_map(function ($x) {
            return $x->getId();
        }, $asset->getOfferings()->toArray());

        $investments = $investmentRepository->findBy([
            'offering' => $offeringIds,
            'type' => 'prefunding',
        ]);
        return $this->aggregateSettledInvestmentsByUser($investments);
    }

    /**
     * @param Investment[] $investments
     */
    #[\Deprecated(
        'Switch to share trade system. Use shareTradeRepository::aggregateUserShareholdingsByAsset',
    )]
    public function aggregateSettledInvestmentsByUser(array $investments): array
    {
        $shareHoldings = [];
        foreach ($investments as $investment) {
            if (
                InvestmentLifecycle::STATE_SETTLED != $investment->getLifecycleStatus()
            ) {
                continue;
            }
            $shareAmount =
                $investment->getNumberOfShares() ?? $investment->getShareAmount();
            if ($shareAmount == 0 || $investment->getInvestmentValue() <= 0) {
                continue;
            }
            $repayments = 0;
            foreach ($investment->getAddFields() ?? [] as $af) {
                if ('capitalRepaid' == $af->getFieldKey()) {
                    $repayments = (int) $af->getFieldValue();
                    break;
                }
            }
            $shareAmount -= $repayments + $investment->getExtraSharesDivested();
            if (array_key_exists($investment->getUser()->getId(), $shareHoldings)) {
                $shareHoldings[$investment->getUser()->getId()] += $shareAmount;
            } else {
                $shareHoldings[$investment->getUser()->getId()] = $shareAmount;
            }
        }
        return $shareHoldings;
    }

    #[\Deprecated(
        'Switch to share trade system. Use tradeOrderRepository to query for prefunding sell orders and then aggregate with DivestmentService::compileRepaymentProgress using QueryGrouping::Asset',
    )]
    public function getAssetPrefundingShareholdings(?int $assetId = null): array
    {
        /** @var \App\Repository\InvestmentRepository */
        $investmentRepository = $this->em->getRepository(\App\Entity\Investment::class);

        $investments = $investmentRepository->findBy([
            'type' => 'prefunding',
        ]);
        return $this->aggregateSettledInvestmentsByAsset($investments);
    }

    /**
     * @param Investment[] $investments
     */
    #[\Deprecated(
        'Switch to share trade system. Use shareTradeRepository::aggregateSharesInCirculation',
    )]
    public function aggregateSettledInvestmentsByAsset(array $investments): array
    {
        $shareHoldings = [];
        foreach ($investments as $investment) {
            if (
                InvestmentLifecycle::STATE_SETTLED != $investment->getLifecycleStatus()
            ) {
                continue;
            }
            $shareAmount =
                $investment->getNumberOfShares() ?? $investment->getShareAmount();
            if ($shareAmount == 0 || $investment->getInvestmentValue() <= 0) {
                continue;
            }
            $repayments = 0;
            foreach ($investment->getAddFields() ?? [] as $af) {
                if ('capitalRepaid' == $af->getFieldKey()) {
                    $repayments = (int) $af->getFieldValue();
                    break;
                }
            }
            $shareAmount -= $repayments + $investment->getExtraSharesDivested();
            $assetId = $investment->getOffering()->getAsset()->getId();
            if (array_key_exists($assetId, $shareHoldings)) {
                $shareHoldings[$assetId] += $shareAmount;
            } else {
                $shareHoldings[$assetId] = $shareAmount;
            }
        }
        return $shareHoldings;
    }

    #[\Deprecated(
        'Switch to share trade system. Use shareTradeRepository::aggregateSharesInCirculation with QueryGrouping::AssetUser',
    )]
    public function getUserAssetShareHoldings(int $assetId, int $userId)
    {
        $assetShareHoldings = $this->holdingRepository->getShareHoldings([
            'assetId' => $assetId,
            'userId' => $userId,
            'currentHolding' => 1,
        ]);

        return $assetShareHoldings;
    }
}
