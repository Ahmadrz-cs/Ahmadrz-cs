<?php

namespace App\Service\Porting;

use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Repository\ShareTradeRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DivestmentScanner
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private ShareTradeRepository $shareTradeRepository,
    ) {}

    public function getCteAssetsWithStatus(bool $filterStatuses = true): QueryBuilder
    {
        // This is a greatest-n-per-group query problem
        // https://stackoverflow.com/a/2111420
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb
            ->select('ast.*', 'COALESCE(ast_status.status, "draft") AS status')
            ->from('assets', 'ast')
            ->leftjoin(
                'ast',
                'asset_status_log',
                'ast_status',
                'ast.id = ast_status.asset_id',
            )
            ->leftJoin(
                'ast',
                'asset_status_log',
                'ast_status_compare',
                'ast.id = ast_status_compare.asset_id AND (ast_status.occuredAt < ast_status_compare.occuredAt OR (ast_status.occuredAt = ast_status_compare.occuredAt AND ast_status.id < ast_status_compare.id))',
            )
            ->andWhere('ast_status_compare.id IS NULL');

        // If filtering is done differently for subqueries
        // Can disable filtering here and filter in the outer queries
        if ($filterStatuses) {
            // The assetStatuses parameter is set in the final query
            // Note the round brackets to indicate an SQL array expected
            // Parameters are set in the main query, not the CTE
            $qb->andWhere('ast_status.status IN (:assetStatuses)');
        }
        return $qb;
    }

    public function getCteDivestmentOrderAggregate(?int $assetId = null): QueryBuilder
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb
            ->select(
                'COUNT(pr.id) AS count',
                'SUM(pr.amount) AS value',
                'SUM(pr.shareholding) AS shares',
            )
            ->from('payment_order', 'po')
            ->leftJoin('po', 'payment_request', 'pr', 'pr.paymentOrder_id = po.id')
            ->andWhere("po.paymentType = 'Investment Exit'")
            ->andWhere("po.status = 'completed'");
        if ($assetId) {
            $qb
                ->addSelect('pr.payee_id AS userid')
                ->andWhere('po.asset_id = :assetId')
                ->setParameter('assetId', $assetId)
                ->groupBy('pr.payee_id');
        } else {
            $qb->addSelect('po.asset_id AS assetid')->groupBy('po.asset_id');
        }
        return $qb;
    }

    public function scanAssetsDivested(?int $assetId = null): array
    {
        $connection = $this->entityManager->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('asset_w_status', $this->getCteAssetsWithStatus())
            ->with(
                'divestment_order_totals',
                $this->getCteDivestmentOrderAggregate($assetId),
            )
            ->select(
                'COUNT(p.id) AS payouts',
                'SUM(p.payoutAmount) AS value',
                'SUM(p.shareholding) AS shares',
                'dot.count AS orderCount',
                'dot.value AS orderValue',
                'dot.shares AS orderShares',
                'COUNT(inv.id) AS investmentCount',
                'SUM(inv.investmentValue) AS investmentValue',
                'SUM(inv.share_amount) AS investmentShares',
            )
            ->from('payouts', 'p')
            ->innerJoin('p', 'asset_w_status', 'astws', 'astws.id = p.asset_id')
            ->leftJoin('p', 'investments', 'inv', 'inv.id = p.investment_id')
            ->where('p.payoutType = 1');
        if ($assetId) {
            // If in single-asset mode, group by user
            // If you need aggregates for that user, just use php array methods array_sum(array_column())
            $qb
                ->addSelect('p.credited_user_id AS userid')
                ->leftJoin(
                    'p',
                    'divestment_order_totals',
                    'dot',
                    'p.credited_user_id = dot.userid',
                )
                ->andWhere('p.asset_id = :assetId')
                ->groupBy('p.credited_user_id')
                ->setParameter('assetId', $assetId);
        } else {
            $qb
                ->addSelect(
                    'p.asset_id AS assetid',
                    'astws.name AS assetName',
                    'astws.companyNumber AS assetSpv',
                    'astws.pricePerShare AS assetSharePrice',
                    'astws.amountOfShares AS assetShareQuantity',
                    'astws.status AS assetStatus',
                )
                ->leftJoin(
                    'astws',
                    'divestment_order_totals',
                    'dot',
                    'astws.id = dot.assetid',
                )
                ->groupBy('p.asset_id');
        }
        $qb->setParameter(
            'assetStatuses',
            [AssetStatus::Closing->value, AssetStatus::Archived->value],
            ArrayParameterType::STRING,
        );
        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function scanTradeBuyBacks(?int $assetId = null): array
    {
        $connection = $this->entityManager->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with(
                'cte_share_trade_w_status',
                $this->shareTradeRepository->getCteShareTradesWithStatus(),
            )
            ->select(
                "COUNT(share_trade_w_status.id) AS 'count'",
                "SUM(share_trade_w_status.numberOfShares)AS 'shares'",
                "SUM(share_trade_w_status.tradeValue) AS 'value'",
            )
            ->from('cte_share_trade_w_status', 'share_trade_w_status')
            ->leftJoin(
                'share_trade_w_status',
                'trade_order',
                'tro',
                'share_trade_w_status.sellOrder_id = tro.id',
            )
            ->andWhere('tro.type = :orderType');
        if ($assetId) {
            $qb
                ->addSelect('tro.user_id AS userid')
                ->andWhere('tro.asset_id = :assetId')
                ->setParameter('assetId', $assetId)
                ->groupBy('tro.user_id');
        } else {
            $qb->addSelect("tro.asset_id AS 'assetid'")->groupBy('tro.asset_id');
        }
        $qb->setParameter(
            'tradeStatuses',
            [TradeStatus::Settled->value],
            ArrayParameterType::STRING,
        )->setParameter('orderType', TradeOrderType::BuyBack->value);
        return $qb->executeQuery()->fetchAllAssociative();
    }
}
