<?php

namespace App\Repository;

use App\Entity\Asset;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\TradeOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<TradeOrder>
 */
class TradeOrderRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, TradeOrder::class);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge($this->getClassMetadata()->getFieldNames(), [
            'assetId',
            'assetName',
            'userId',
            'userUsername',
            'excludeUserId',
            'createdAt_gte',
            'createdAt_lt',
            'status',
        ]);

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('tradeOrder', 'user', 'asset')
            ->from(TradeOrder::class, 'tradeOrder')
            ->leftJoin('tradeOrder.asset', 'asset')
            ->leftJoin('tradeOrder.user', 'user');

        if (!empty(array_intersect(['status'], array_keys($filters)))) {
            // Potential querying cost to these, so only join if there are filters that need them
            // https://stackoverflow.com/a/2111420
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->logger->debug('Applying status log filters');
                $qb
                    ->distinct()
                    ->leftJoin('tradeOrder.statusLogs', alias: 'status_log')
                    ->leftJoin(
                        'tradeOrder.statusLogs',
                        alias: 'status_log_comparison',
                        conditionType: Expr\Join::WITH,
                        condition: $qb->expr()->andX(
                            $qb->expr()->eq(
                                'tradeOrder.id',
                                'status_log_comparison.tradeOrder',
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

            if (in_array($key, ['assetId', 'assetName'])) {
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['name'])) {
                    $qb->andWhere($qb->expr()->like('asset.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['userId', 'userUsername'])) {
                $field = lcfirst(substr($key, strlen('user')));
                if (in_array($field, ['name', 'username'])) {
                    $qb->andWhere($qb->expr()->like('user.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['excludeUserId'])) {
                $qb->andWhere($qb->expr()->notIn('user.id', ':' . $key));
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'tradeOrder.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'tradeOrder.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } elseif (in_array($key, ['status'])) {
                // status_log related
                if (
                    is_array($value)
                    && (
                        in_array(TradeOrderStatus::Draft, $value)
                        || in_array(TradeOrderStatus::Draft->value, $value)
                    )
                ) {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('status_log.' . $key, ':' . $key),
                        $qb->expr()->isNull('status_log'),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->in('status_log.' . $key, ':' . $key));
                }
            } else {
                $qb->andWhere($qb->expr()->in('tradeOrder.' . $key, ':' . $key));
            }

            if (in_array($key, ['assetName', 'userUsername'])) {
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
                $qb->addOrderBy('tradeOrder.' . $key, $direction);
            }
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
     * @return TradeOrder[]
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

    public function findInitialSellOrders(Asset $asset): array
    {
        return $this->buildQueryWithAssociations([
            'assetId' => $asset->getId(),
            'status' => [
                TradeOrderStatus::Active,
                TradeOrderStatus::Completed,
                TradeOrderStatus::Suspended,
            ],
            'type' => TradeOrderType::Initial,
            'direction' => TradeDirection::Sell,
        ])->getResult();
    }
}
