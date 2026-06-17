<?php

namespace App\Repository;

use App\Entity\Enum\OrderingDirection;
use App\Entity\Enum\QueryGrouping;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\UnionType;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ShareTrade>
 */
class ShareTradeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, ShareTrade::class);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
        bool $asBuilder = false,
    ): \Doctrine\ORM\QueryBuilder|Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(ShareTrade::class)
                ->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(ShareTrade::class)
                ->getAssociationNames(),
            [
                'assetId',
                'assetName',
                'assetCompanyNumber',
                'userId',
                'buyerId',
                'sellerId',
                'buyerUsername',
                'sellerUsername',
                'status',
                'buyOrderType',
                'sellOrderType',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('shareTrade', 'buyOrder', 'sellOrder', 'buyer', 'seller', 'asset')
            ->from(ShareTrade::class, 'shareTrade')
            ->leftJoin('shareTrade.buyOrder', 'buyOrder')
            ->leftJoin('shareTrade.sellOrder', 'sellOrder')
            ->leftJoin('buyOrder.user', alias: 'buyer')
            ->leftJoin('sellOrder.user', alias: 'seller')
            ->leftJoin('buyOrder.asset', alias: 'asset');

        if (!empty(array_intersect(['status'], array_keys($filters)))) {
            // Potential querying cost to these, so only join if there are filters that need them
            // https://stackoverflow.com/a/2111420
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->logger->debug('Applying status log filters');
                $qb
                    ->distinct()
                    ->leftJoin('shareTrade.statusLogs', alias: 'status_log')
                    ->leftJoin(
                        'shareTrade.statusLogs',
                        alias: 'status_log_comparison',
                        conditionType: Expr\Join::WITH,
                        condition: $qb->expr()->andX(
                            $qb->expr()->eq(
                                'shareTrade.id',
                                'status_log_comparison.shareTrade',
                            ),
                            $qb->expr()->orX(
                                $qb->expr()->lt(
                                    'status_log.occuredAt',
                                    'status_log_comparison.occuredAt',
                                ),
                                $qb->expr()->andX(
                                    $qb->expr()->eq(
                                        'status_log.occuredAt',
                                        'status_log_comparison.occuredAt',
                                    ),
                                    $qb->expr()->lt(
                                        'status_log.id',
                                        'status_log_comparison.id',
                                    ),
                                ),
                            ),
                        ),
                    )
                    ->andWhere('status_log_comparison.id IS NULL');
            }
        }

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['assetId', 'assetName', 'assetCompanyNumber'])) {
                // Note that you don't need to check asset on BOTH sides (buy and sell) and
                // they will be the same unless something has gone catastrophically wrong
                // If we are super concerned, we can do an AND to ensure the trade has the same asset on both sides
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['name', 'companyNumber'])) {
                    $qb->andWhere($qb->expr()->like('asset.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['userId'])) {
                $field = lcfirst(substr($key, strlen('user')));
                $conditions = [];
                // Cannot apply buyer.id or seller.id twice so only add to filter conditions if not already defined
                if (!in_array('buyerId', array_keys(array_filter($filters)))) {
                    $this->logger->debug('UserId in buyer');
                    $conditions[] = $qb->expr()->in('buyer.' . $field, ':' . $key);
                }
                if (!in_array('sellerId', array_keys(array_filter($filters)))) {
                    $this->logger->debug('UserId in seller');
                    $conditions[] = $qb->expr()->in('seller.' . $field, ':' . $key);
                }
                if (count($conditions) == 2) {
                    $qb->andWhere($qb->expr()->orX(...$conditions));
                } elseif (count($conditions) == 1) {
                    $qb->andWhere($conditions[0]);
                } else {
                    // If both sellerId and buyerId are set, then userId will not be filtering, so skip to next filter
                    continue;
                }
            } elseif (in_array($key, ['buyerId', 'buyerUsername'])) {
                $field = lcfirst(substr($key, strlen('buyer')));
                // $this->logger->debug('Filter buyer');
                if (in_array($field, ['username'])) {
                    $qb->andWhere($qb->expr()->like('buyer.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('buyer.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['sellerId', 'sellerUsername'])) {
                $field = lcfirst(substr($key, strlen('seller')));
                // $this->logger->debug('Filter seller');
                if (in_array($field, ['username'])) {
                    $qb->andWhere($qb->expr()->like('seller.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('seller.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['buyOrderType'])) {
                $field = lcfirst(substr($key, strlen('buyOrder')));
                $qb->andWhere($qb->expr()->in('buyOrder.' . $field, ':' . $key));
            } elseif (in_array($key, ['sellOrderType'])) {
                $field = lcfirst(substr($key, strlen('sellOrder')));
                $qb->andWhere($qb->expr()->in('sellOrder.' . $field, ':' . $key));
            } elseif (in_array($key, ['status'])) {
                // status_log related
                if (
                    is_array($value)
                    && (
                        in_array(TradeStatus::Draft, $value)
                        || in_array(TradeStatus::Draft->value, $value)
                    )
                ) {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('status_log.' . $key, ':' . $key),
                        $qb->expr()->isNull('status_log'),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->in('status_log.' . $key, ':' . $key));
                }
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'shareTrade.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'shareTrade.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('shareTrade.' . $key, ':' . $key));
            }

            if (in_array($key, [
                'buyerUsername',
                'sellerUsername',
                'assetName',
                'assetCompanyNumber',
            ])) {
                // note that the preceding % wildcard has a high performance cost
                $qb->setParameter($key, '%' . addcslashes($value, '%_') . '%');
            } elseif ($value instanceof Uuid) {
                // Uuids should be formatted by the Symfony bridge for uuid and doctrine
                // See https://symfony.com/doc/current/components/uid.html#storing-uuids-in-databases
                $qb->setParameter($key, $value, UuidType::NAME);
            } elseif ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                $qb->setParameter($key, $value->format('Y-m-d'));
            } else {
                $qb->setParameter($key, $value);
            }
        }
        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('shareTrade.' . $key, $direction);
            }
        }
        if ($asBuilder) {
            return $qb;
        }
        return $qb->getQuery();
    }

    public function findByWithAssociations(
        array $filters,
        array $orderBy = [],
        int $limit = 10,
        int $page = 1,
    ): Pagerfanta {
        $query = $this->buildQueryWithAssociations($filters, $orderBy);
        $adapter = new QueryAdapter($query);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));
        return $pagerfanta;
    }

    /**
     * If pagination is needed, use findByWithAssociations
     * This always returns the list of results, if you need to modify the query
     * use buildQueryWithAssociations
     * @return ShareTrade[]
     */
    public function findWithAssociations(
        array $filters,
        array $orderBy = [],
        ?int $limit = null,
    ): array {
        $query = $this->buildQueryWithAssociations($filters, $orderBy);
        if ($limit) {
            $query->setMaxResults($limit);
        }
        return $query->getResult();
    }

    /**
     * Return a query builder for retrieving share trades with their most recent status log as 'status'
     */
    public function getCteShareTradesWithStatus(bool $filterStatuses = true): QueryBuilder
    {
        // This is a greatest-n-per-group query problem
        // https://stackoverflow.com/a/2111420
        // Also implemented in QDL in buildQueryWithAssociations
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select(
                'str.*',
                'COALESCE(str_status.status, "draft") AS status',
                'COALESCE(str_status.occuredAt, str.createdAt) AS statusOccuredAt',
            )
            ->from('share_trade', 'str')
            ->leftjoin(
                'str',
                'share_trade_status_log',
                'str_status',
                'str.id = str_status.shareTrade_id',
            )
            ->leftJoin(
                'str',
                'share_trade_status_log',
                'str_status_compare',
                'str.id = str_status_compare.shareTrade_id AND (str_status.occuredAt < str_status_compare.occuredAt OR (str_status.occuredAt = str_status_compare.occuredAt AND str_status.id < str_status_compare.id))',
            )
            ->andWhere('str_status_compare.id IS NULL');

        // If filtering is done differently for subqueries
        // Can disable filtering here and filter in the outer queries
        if ($filterStatuses) {
            // The tradeStatuses parameter is set in the final query
            // Note the round brackets to indicate an SQL array expected
            // Parameters are set in the main query, not the CTE
            $qb->andWhere('str_status.status IN (:tradeStatuses)');
        }
        return $qb;
    }

    public function getCteTradeOrdersWithStatus(bool $filterStatuses = true): QueryBuilder
    {
        // This is a greatest-n-per-group query problem
        // https://stackoverflow.com/a/2111420
        // Also implemented in QDL in buildQueryWithAssociations
        $qb = $this->getEntityManager()->getConnection()->createQueryBuilder();
        $qb
            ->select(
                'tro.*',
                'COALESCE(tro_status.status, "draft") AS status',
                'COALESCE(tro_status.occuredAt, tro.createdAt) AS statusOccuredAt',
            )
            ->from('trade_order', 'tro')
            ->leftjoin(
                'tro',
                'trade_order_status_log',
                'tro_status',
                'tro.id = tro_status.tradeOrder_id',
            )
            ->leftJoin(
                'tro',
                'trade_order_status_log',
                'tro_status_compare',
                'tro.id = tro_status_compare.tradeOrder_id AND (tro_status.occuredAt < tro_status_compare.occuredAt OR (tro_status.occuredAt = tro_status_compare.occuredAt AND tro_status.id < tro_status_compare.id))',
            )
            ->andWhere('tro_status_compare.id IS NULL');

        // If filtering is done differently for subqueries
        // Can disable filtering here and filter in the outer queries
        if ($filterStatuses) {
            // The orderStatuses parameter is set in the final query
            // Note the round brackets to indicate an SQL array expected
            // Parameters are set in the main query, not the CTE
            $qb->andWhere('tro_status.status IN (:orderStatuses)');
        }
        return $qb;
    }

    /**
     * Returns a query builder to be used as a CTE containing share trades with
     * - current status
     * - aggregate trade-count, shares, tradeValue by either USER ID or ASSET ID
     *   - OR leave it ungrouped so it can be grouped later in getCteShareholdings
     */
    public function getCteShareTradesWithStatusGrouped(
        QueryGrouping $groupBy,
        bool $filterBuyTypes = true,
        bool $filterSellTypes = true,
    ): QueryBuilder {
        // $cteShareTradesWithStatus = $this->getCteShareTradesWithStatus();
        $connection = $this->getEntityManager()->getConnection();
        $commonFields = ["COUNT(share_trade_w_status.id) AS 'count'"];
        $buyFields = [
            "SUM(share_trade_w_status.numberOfShares) AS 'shares'",
            "SUM(share_trade_w_status.tradeValue) * -1 AS 'value'",
        ];
        $sellFields = [
            "SUM(share_trade_w_status.numberOfShares) * -1 AS 'shares'",
            "SUM(share_trade_w_status.tradeValue) AS 'value'",
        ];
        if ($groupBy === QueryGrouping::AssetUser) {
            $commonFields = [
                "share_trade_w_status.id AS 'tradeid'",
                "tro.id AS 'orderid'",
                "tro.user_id AS 'userid'",
                "tro.asset_id AS 'assetid'",
            ];
            $buyFields = [
                "share_trade_w_status.numberOfShares AS 'shares'",
                "share_trade_w_status.tradeValue * -1 AS 'value'",
            ];
            $sellFields = [
                "share_trade_w_status.numberOfShares * -1 AS 'shares'",
                "share_trade_w_status.tradeValue AS 'value'",
            ];
        }
        $buySubquery = $connection
            ->createQueryBuilder()
            // Note that for a buy, the tradeValue is negated, to represent an outflow of money
            // i.e. you are gaining shares, at the cost of money
            ->select(...$commonFields, ...$buyFields)
            ->from('cte_share_trade_w_status', 'share_trade_w_status')
            ->leftJoin(
                'share_trade_w_status',
                'trade_order',
                'tro',
                'share_trade_w_status.buyOrder_id = tro.id',
            );
        if ($filterBuyTypes) {
            $buySubquery->andWhere('tro.type IN (:buyTypes)');
        }
        $sellSubquery = $connection
            ->createQueryBuilder()
            // Note that for a sell, the shares value is negated, to respresent an outflow of shares
            // i.e. you are gaining money, at the cost of shares
            ->select(...$commonFields, ...$sellFields)
            ->from('cte_share_trade_w_status', 'share_trade_w_status')
            ->leftJoin(
                'share_trade_w_status',
                'trade_order',
                'tro',
                'share_trade_w_status.sellOrder_id = tro.id',
            );
        if ($filterSellTypes) {
            $sellSubquery->andWhere('tro.type IN (:sellTypes)');
        }

        /**
         * Configure groups and filterings
         * Reminder: Parameters are set in the main query, not the CTE
         */
        if ($groupBy == QueryGrouping::User) {
            $buySubquery->addSelect("tro.user_id AS 'userid'");
            $sellSubquery->addSelect("tro.user_id AS 'userid'");

            $buySubquery->andWhere('tro.asset_id = :filterId');
            $sellSubquery->andWhere('tro.asset_id = :filterId');

            $buySubquery->addGroupBy('tro.user_id');
            $sellSubquery->addGroupBy('tro.user_id');
        } elseif ($groupBy == QueryGrouping::Asset) {
            $buySubquery->addSelect("tro.asset_id AS 'assetid'");
            $sellSubquery->addSelect("tro.asset_id AS 'assetid'");

            $buySubquery->andWhere('tro.user_id = :filterId');
            $sellSubquery->andWhere('tro.user_id = :filterId');

            $buySubquery->addGroupBy('tro.asset_id');
            $sellSubquery->addGroupBy('tro.asset_id');
        }
        $unionQuery = $connection->createQueryBuilder();
        $unionQuery->union($buySubquery)->addUnion($sellSubquery, UnionType::ALL);
        return $unionQuery;
    }

    /**
     * - Equivalent to the legacy shareholdings view.
     * - Aggregates by asset-user together,
     *   so you can see all assets and their shareholders in a single view
     * - Can be subsequently aggregated again by either asset or user
     *   - Aggregating by asset shows the current holdings across all users combined for all assets
     *   - Aggregating by user shows the current holdings across all assets combined (portfolio total) for all users (pretty heavy)
     */
    public function getCteShareholdings(): QueryBuilder
    {
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection
            ->createQueryBuilder()
            ->select(
                'assetid',
                'userid',
                "COUNT(DISTINCT tradeid) AS 'trades'",
                "COUNT(DISTINCT orderid) AS 'orders'",
                "COUNT(DISTINCT CASE WHEN shares < 0 THEN orderid END) AS 'sell_orders'",
                "COUNT(DISTINCT CASE WHEN shares > 0 THEN orderid END) AS 'buy_orders'",
                "SUM(CASE WHEN shares < 0 THEN value ELSE 0 END) AS 'sell_value'",
                "SUM(CASE WHEN shares > 0 THEN value ELSE 0 END) AS 'buy_value'",
                "SUM(shares) AS 'shares'",
                "SUM(value) AS 'value'",
            )
            ->from('cte_share_trades_grouped')
            ->addGroupBy('assetid')
            ->addGroupBy('userid');
        return $qb;
    }

    /**
     * @param TradeStatus[] $tradeStatuses
     * @param TradeOrderType[] $buyTypes
     * @param TradeOrderType[] $sellTypes
     */
    public function buildAggregateShareholdingQuery(
        QueryGrouping $groupBy,
        int|string $filterId,
        array $tradeStatuses = [TradeStatus::Settled],
        bool $nonZero = false,
        array $buyTypes = [],
        array $sellTypes = [],
    ): QueryBuilder {
        $tradeStatuses = array_map(
            fn(TradeStatus $ts): string => $ts->value,
            array_filter($tradeStatuses, fn($ts) => $ts instanceof TradeStatus),
        );
        $buyTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            array_filter($buyTypes, fn($ts) => $ts instanceof TradeOrderType),
        );
        $sellTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            array_filter($sellTypes, fn($ts) => $ts instanceof TradeOrderType),
        );

        /**
         * Using common table expressions (CTEs)
         * https://www.doctrine-project.org/projects/doctrine-dbal/en/4.4/reference/query-builder.html#common-table-expressions
         *
         * We have dependent CTEs, where this query builder -> cte_share_trades_grouped -> cte_share_trade_w_status
         */
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_share_trade_w_status', $this->getCteShareTradesWithStatus())
            ->with('cte_share_trades_grouped', $this->getCteShareTradesWithStatusGrouped(
                $groupBy,
                !empty($buyTypes),
                !empty($sellTypes),
            ))
            // Note that we apply ABS (absolute) to the negated values
            // The negation is purely for the summing process to get the net (sells minus buys)
            ->select(
                "SUM(CASE WHEN shares > 0 THEN count ELSE 0 END) AS 'buyTrades'",
                "SUM(CASE WHEN shares > 0 THEN shares ELSE 0 END) AS 'buyShares'",
                "ABS(SUM(CASE WHEN shares > 0 THEN value ELSE 0 END)) AS 'buyValue'",
                "SUM(CASE WHEN shares < 0 THEN count ELSE 0 END) AS 'sellTrades'",
                "ABS(SUM(CASE WHEN shares < 0 THEN shares ELSE 0 END)) AS 'sellShares'",
                "SUM(CASE WHEN shares < 0 THEN value ELSE 0 END) AS 'sellValue'",
                "SUM(count) AS 'trades'",
                "SUM(shares) AS 'shares'",
                "SUM(value) AS 'value'",
            )
            ->from('cte_share_trades_grouped');
        // Default to asset grouping
        if ($groupBy === QueryGrouping::User) {
            $qb->addSelect('userid')->addGroupBy('userid');
        } else {
            $qb->addSelect('assetid')->addGroupBy('assetid');
        }
        if ($nonZero) {
            $qb->andHaving('SUM(shares) > 0');
        }
        $qb->setParameter(
            'tradeStatuses',
            $tradeStatuses,
            ArrayParameterType::STRING,
        )->setParameter('filterId', (string) $filterId, ParameterType::STRING);
        if (!empty($buyTypes)) {
            $qb->setParameter('buyTypes', $buyTypes, ArrayParameterType::STRING);
        }
        if (!empty($sellTypes)) {
            $qb->setParameter('sellTypes', $sellTypes, ArrayParameterType::STRING);
        }
        return $qb;
    }

    /**
     * @param TradeStatus[] $tradeStatuses
     * @param TradeOrderType[] $buyTypes empty is equivalent to all
     * @param TradeOrderType[] $sellTypes empty is equivalent to all
     */
    public function aggregateAssetShareholdingsByUser(
        int|string $assetId,
        array $tradeStatuses = [TradeStatus::Settled],
        bool $nonZero = false,
        ?OrderingDirection $shareholderOrdering = null,
        int|string|null $userId = null,
        array $buyTypes = [],
        array $sellTypes = [],
    ): array {
        $qb = $this->buildAggregateShareholdingQuery(
            groupBy: QueryGrouping::User,
            filterId: $assetId,
            tradeStatuses: $tradeStatuses,
            nonZero: $nonZero,
            buyTypes: $buyTypes,
            sellTypes: $sellTypes,
        );
        if ($shareholderOrdering) {
            $qb->addOrderBy('shares', $shareholderOrdering->value);
        }
        // Optional query-level filter to select a specific user's shareholding in the asset
        // Allows you to skip having to filter in PHP-land
        if ($userId) {
            $qb->andWhere('userid = :userId')->setParameter('userId', $userId);
        }
        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param TradeStatus[] $tradeStatuses
     * @param TradeOrderType[] $buyTypes empty is equivalent to all
     * @param TradeOrderType[] $sellTypes empty is equivalent to all
     */
    public function aggregateUserShareholdingsByAsset(
        int|string $userId,
        array $tradeStatuses = [TradeStatus::Settled],
        bool $nonZero = false,
        ?OrderingDirection $shareholderOrdering = null,
        int|string|null $assetId = null,
        array $buyTypes = [],
        array $sellTypes = [],
    ): array {
        $qb = $this->buildAggregateShareholdingQuery(
            groupBy: QueryGrouping::Asset,
            filterId: $userId,
            tradeStatuses: $tradeStatuses,
            nonZero: $nonZero,
            buyTypes: $buyTypes,
            sellTypes: $sellTypes,
        );
        if ($shareholderOrdering) {
            $qb->addOrderBy('shares', $shareholderOrdering->value);
        }
        // Optional query-level filter to select a specific asset's shareholding for the user
        // Allows you to skip having to filter in PHP-land
        if ($assetId) {
            $qb->andWhere('assetid = :assetId')->setParameter('assetId', $assetId);
        }
        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function aggregateSharesInCirculation(
        QueryGrouping $groupBy = QueryGrouping::Asset,
        bool $nonZero = false,
        bool $orderBySize = false,
    ): array {
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_share_trade_w_status', $this->getCteShareTradesWithStatus())
            ->with('cte_share_trades_grouped', $this->getCteShareTradesWithStatusGrouped(
                QueryGrouping::AssetUser,
                true,
                true,
            ))
            ->with('cte_shareholdings', $this->getCteShareholdings())
            ->select('*')
            ->from('cte_shareholdings');
        ;
        if ($groupBy !== null) {
            $qb->select(
                "SUM(trades) AS 'trades'",
                "SUM(orders) AS 'orders'",
                "SUM(sell_orders) AS 'sell_orders'",
                "SUM(buy_orders) AS 'buy_orders'",
                "SUM(sell_value) AS 'sell_value'",
                "ABS(SUM(buy_value)) AS 'buy_value'",
                "SUM(shares) AS 'shares'",
                "SUM(value) AS 'value'",
            );
        }

        if ($groupBy === QueryGrouping::Asset) {
            $qb
                ->addSelect('assetid')
                ->addGroupBy('assetid')
                ->addOrderBy(
                    $orderBySize ? 'value' : 'assetid',
                    $orderBySize ? 'DESC' : 'ASC',
                );
        } elseif ($groupBy === QueryGrouping::User) {
            $qb
                ->addSelect('userid')
                ->addGroupBy('userid')
                ->addOrderBy(
                    $orderBySize ? 'value' : 'userid',
                    $orderBySize ? 'DESC' : 'ASC',
                );
        }
        if ($nonZero) {
            $qb->andWhere('shares > 0');
        }
        $buyTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            TradeOrderType::circulatingBuyTypes(),
        );
        $sellTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            TradeOrderType::circulatingSellTypes(),
        );
        $qb
            ->setParameter(
                'tradeStatuses',
                [TradeStatus::Settled->value],
                ArrayParameterType::STRING,
            )
            ->setParameter('buyTypes', $buyTypes, ArrayParameterType::STRING)
            ->setParameter('sellTypes', $sellTypes, ArrayParameterType::STRING);
        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function extendAssetTradeShareholdingsQuery(QueryBuilder $qb): QueryBuilder
    {
        $additionalColumns = [
            'usr.username AS username',
            'usr.firstname AS first_name',
            'usr.lastname AS last_name',
            'usr.email AS userContactEmail',
        ];
        $qb->addSelect(...$additionalColumns)->leftJoin(
            'cte_share_trades_grouped',
            'users',
            'usr',
            'cte_share_trades_grouped.userid = usr.id',
        );
        return $qb;
    }

    public function extendShareTradeQuery(\Doctrine\ORM\QueryBuilder $qb): \Doctrine\ORM\QueryBuilder
    {
        return $qb
            ->leftJoin('buyer.investor', alias: 'buyerInvestor')
            ->leftJoin('buyer.onboardingProfile', alias: 'buyerObp')
            ->leftJoin('seller.onboardingProfile', alias: 'sellerObp')
            ->leftJoin('buyer.company', alias: 'buyerCompany')
            ->addSelect('buyerInvestor', 'buyerObp', 'sellerObp', 'buyerCompany');
    }

    public function getTradeOrderAggregates(TradeOrder $tradeOrder): ?array
    {
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_share_trade_w_status', $this->getCteShareTradesWithStatus())
            ->select(
                "tro.numberOfShares AS 'sharesListed'",
                "SUM(share_trade_w_status.numberOfShares) AS 'shares'",
                "tro.id AS 'orderId'",
                "tro.asset_id AS 'assetId'",
            )
            ->from('cte_share_trade_w_status', 'share_trade_w_status');
        if ($tradeOrder->getDirection() === TradeDirection::Buy) {
            $qb->leftJoin(
                'share_trade_w_status',
                'trade_order',
                'tro',
                'share_trade_w_status.buyOrder_id = tro.id',
            );
        } else {
            $qb->leftJoin(
                'share_trade_w_status',
                'trade_order',
                'tro',
                'share_trade_w_status.sellOrder_id = tro.id',
            );
        }
        $qb->andWhere('tro.id = :filterId')->addGroupBy('tro.id');

        $tradeStatuses = array_map(
            fn(TradeStatus $ts): string => $ts->value,
            TradeStatus::countedStatuses(),
        );
        $qb->setParameter(
            'tradeStatuses',
            $tradeStatuses,
            ArrayParameterType::STRING,
        )->setParameter(
            'filterId',
            (string) $tradeOrder->getId(),
            ParameterType::STRING,
        );
        // SQL queries will always return "rows" of results
        // Each result is itself an array
        // We'll just return the first "row" (i.e. array) using array_first() (requires PHP8.5)
        return array_first($qb->executeQuery()->fetchAllAssociative());
    }

    /**
     * Returns query that aggregates all share trades in sell-side trade orders.
     *
     * @return QueryBuilder
     */
    public function getCteTradeOrderProgress(): QueryBuilder
    {
        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_share_trade_w_status', $this->getCteShareTradesWithStatus())
            ->with('cte_trade_orders_w_status', $this->getCteTradeOrdersWithStatus())
            ->select(
                "trade_order_w_status.numberOfShares AS 'sharesListed'",
                "SUM(COALESCE(share_trade_w_status.numberOfShares, 0)) AS 'shares'",
                "trade_order_w_status.id AS 'orderId'",
                "trade_order_w_status.asset_id AS 'assetId'",
                "trade_order_w_status.user_id AS 'userId'",
                "trade_order_w_status.direction AS 'direction'",
            )
            ->from('cte_trade_orders_w_status', 'trade_order_w_status')
            ->leftJoin(
                'trade_order_w_status',
                'cte_share_trade_w_status',
                'share_trade_w_status',
                'share_trade_w_status.sellOrder_id = trade_order_w_status.id',
            )
            ->addGroupBy('trade_order_w_status.id')
            ->andWhere('trade_order_w_status.type IN (:orderTypes)');
        return $qb;
    }

    public function getAssetTradeAggregates(int|string $assetId): ?array
    {
        $sellTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            TradeOrderType::tradingSellTypes(),
        );
        $tradeStatuses = array_map(
            fn(TradeStatus $ts): string => $ts->value,
            TradeStatus::countedStatuses(),
        );

        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_trade_orders_progress', $this->getCteTradeOrderProgress())
            ->select(
                "SUM(trade_order_progress.sharesListed) AS 'sharesListed'",
                "SUM(trade_order_progress.shares) AS 'shares'",
                "SUM(trade_order_progress.sharesListed) - SUM(trade_order_progress.shares) AS 'sharesAvailable'",
                "trade_order_progress.assetId AS 'assetId'",
            )
            ->from('cte_trade_orders_progress', 'trade_order_progress')
            ->andWhere('trade_order_progress.assetId = :filterId');

        $qb->addGroupBy('trade_order_progress.assetId');

        $qb
            ->setParameter('tradeStatuses', $tradeStatuses, ArrayParameterType::STRING)
            ->setParameter(
                'orderStatuses',
                [TradeOrderStatus::Active->value],
                ArrayParameterType::STRING,
            )
            ->setParameter('filterId', (string) $assetId, ParameterType::STRING)
            ->setParameter('orderTypes', $sellTypes, ArrayParameterType::STRING);
        // SQL queries will always return "rows" of results
        // Each result is itself an array
        // There should only be 1 row of results, as we're grouping AND filtering by asset id
        // We'll just return the first "row" (i.e. array) using array_first() (requires PHP8.5)
        return array_first($qb->executeQuery()->fetchAllAssociative());
    }

    /**
     * @param TradeOrderStatus[] $orderStatuses
     * @param TradeOrderType[] $orderTypes
     */
    public function aggregateUserTradeOrdersByAsset(
        int|string $userId,
        TradeDirection $direction = TradeDirection::Sell,
        array $orderStatuses = [TradeOrderStatus::Active],
        array $orderTypes = [TradeOrderType::Market],
        array $tradeStatuses = [TradeStatus::Settled],
    ): ?array {
        $tradeStatuses = array_map(
            fn(TradeStatus $ts): string => $ts->value,
            array_filter($tradeStatuses, fn($ts) => $ts instanceof TradeStatus),
        );
        $orderStatuses = array_map(
            fn(TradeOrderStatus $ts): string => $ts->value,
            array_filter($orderStatuses, fn($ts) => $ts instanceof TradeOrderStatus),
        );
        $orderTypes = array_map(
            fn(TradeOrderType $ts): string => $ts->value,
            array_filter($orderTypes, fn($ts) => $ts instanceof TradeOrderType),
        );

        $connection = $this->getEntityManager()->getConnection();
        $qb = $connection->createQueryBuilder();
        $qb
            ->with('cte_trade_orders_progress', $this->getCteTradeOrderProgress())
            ->select(
                "COUNT(trade_order_progress.orderId) AS 'count'",
                "SUM(trade_order_progress.sharesListed) AS 'sharesListed'",
                "SUM(trade_order_progress.shares) AS 'shares'",
                "SUM(trade_order_progress.sharesListed) - SUM(trade_order_progress.shares) AS 'sharesAvailable'",
                "trade_order_progress.assetId AS 'assetId'",
            )
            ->from('cte_trade_orders_progress', 'trade_order_progress')
            ->addGroupBy('trade_order_progress.assetId')
            ->andWhere('trade_order_progress.direction = :direction')
            ->andWhere('trade_order_progress.userId = :filterId');

        $qb
            ->setParameter('tradeStatuses', $tradeStatuses, ArrayParameterType::STRING)
            ->setParameter('orderStatuses', $orderStatuses, ArrayParameterType::STRING)
            ->setParameter('filterId', (string) $userId, ParameterType::STRING)
            ->setParameter('direction', $direction->value, ParameterType::INTEGER)
            ->setParameter('orderTypes', $orderTypes, ArrayParameterType::STRING);
        return $qb->executeQuery()->fetchAllAssociative();
    }
}
