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

class RepaymentScanner
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

    public function getCteRepaymentOrderAggregate(?int $assetId = null): QueryBuilder
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
            ->andWhere("po.paymentType = 'Repayment'")
            ->andWhere("po.status IN ('in_progress', 'completed')")
            ->andWhere("pr.status = 'paid'");
        if ($assetId) {
            $qb
                ->addSelect('pr.payee_id AS userid')
                ->andWhere('po.asset_id = :assetId')
                // ->setParameter('assetId', $assetId)
                ->groupBy('pr.payee_id');
        } else {
            $qb->addSelect('po.asset_id AS assetid')->groupBy('po.asset_id');
        }
        return $qb;
    }

    public function getCteInvestmentCapitalRepaidAdditionalField(): QueryBuilder
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb
            ->select('iaf.investment_id AS investmentid', "SUM(CASE
                    WHEN fieldKey = 'capitalRepaid'
                    THEN fieldValue
                    ELSE 0
                END) AS 'capitalRepaid'
            ")
            ->from('investment_add_fields', 'iaf')
            ->groupBy('iaf.investment_id');
        return $qb;
    }

    public function getCteInvestmentCapitalRepaid(?int $assetId = null): QueryBuilder
    {
        $qb = $this->entityManager->getConnection()->createQueryBuilder();
        $qb
            ->select(
                'SUM(inv.share_amount) AS prefunded',
                'COUNT(inv.id) AS count',
                'SUM(inv.extraSharesDivested) AS extraSharesDivested',
                'SUM(iaf.capitalRepaid) AS capitalRepaid',
                'MAX(inv.createdAt) AS lastPrefunded',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'ist',
                'ist.id = inv.investmentStatus_id',
            )
            ->leftJoin('inv', 'offerings', 'o', 'o.id = inv.off_id')
            ->leftJoin(
                'inv',
                'investment_af_repaid',
                'iaf',
                'iaf.investmentid = inv.id',
            )
            ->andWhere("ist.lifecycleStatus = 'settled'")
            ->andWhere("inv.type = 'prefunding'");
        if ($assetId) {
            $qb
                ->addSelect('inv.user_id AS userid')
                ->andWhere('o.asset_id = :assetId')
                // ->setParameter('assetId', $assetId)
                ->groupBy('inv.user_id');
        } else {
            $qb->addSelect('o.asset_id AS assetid')->groupBy('o.asset_id');
        }
        return $qb;
    }

    public function scanAssetRepayments(?int $assetId = null): array
    {
        $connection = $this->entityManager->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('asset_w_status', $this->getCteAssetsWithStatus())
            ->with(
                'investment_af_repaid',
                $this->getCteInvestmentCapitalRepaidAdditionalField(),
            )
            ->with('asset_repayments', $this->getCteInvestmentCapitalRepaid($assetId))
            ->with(
                'repayment_order_totals',
                $this->getCteRepaymentOrderAggregate($assetId),
            )
            ->select(
                'ar.count AS count',
                'ar.prefunded AS prefunded',
                'ar.extraSharesDivested AS sharesNative',
                'COALESCE(ar.capitalRepaid, 0) AS sharesAdditionalField',
                'ar.extraSharesDivested + COALESCE(ar.capitalRepaid, 0) AS sharesCombined',
                'MAX(ar.lastPrefunded) AS lastPrefunded',
                'dot.count AS orderCount',
                'dot.value AS orderValue',
                'dot.shares AS orderShares',
            )
            ->from('asset_repayments', 'ar');
        if ($assetId) {
            // If in single-asset mode, group by user
            // If you need aggregates for that user, just use php array methods array_sum(array_column())
            $qb
                ->addSelect('ar.userid AS userid')
                ->leftJoin(
                    'ar',
                    'repayment_order_totals',
                    'dot',
                    'ar.userid = dot.userid',
                )
                // ->andWhere('p.asset_id = :assetId')
                ->groupBy('ar.userid')
                ->setParameter('assetId', $assetId);
        } else {
            $qb
                ->addSelect(
                    'ar.assetid AS assetid',
                    'astws.name AS assetName',
                    'astws.companyNumber AS assetSpv',
                    'astws.pricePerShare AS assetSharePrice',
                    'astws.amountOfShares AS assetShareQuantity',
                    'astws.status AS assetStatus',
                )
                ->innerJoin('ar', 'asset_w_status', 'astws', 'astws.id = ar.assetid')
                ->leftJoin(
                    'astws',
                    'repayment_order_totals',
                    'dot',
                    'astws.id = dot.assetid',
                )
                ->groupBy('ar.assetid');
        }
        $qb->setParameter(
            'assetStatuses',
            [AssetStatus::Active->value, AssetStatus::Closing->value],
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
        )->setParameter('orderType', TradeOrderType::Prefunding->value);
        return $qb->executeQuery()->fetchAllAssociative();
    }
}
