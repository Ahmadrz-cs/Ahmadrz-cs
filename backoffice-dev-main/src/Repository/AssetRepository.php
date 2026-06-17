<?php

namespace App\Repository;

use App\Entity\Asset;
use App\Entity\AssetStatus;
use App\Entity\Enum\AssetStatus as EnumAssetStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Asset>
 *
 * @method Asset|null find($id, $lockMode = null, $lockVersion = null)
 * @method Asset|null findOneBy(array $criteria, array $orderBy = null)
 * @method Asset[]    findAll()
 * @method Asset[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, Asset::class);
    }

    public function save(Asset $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Asset $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return Asset[] Returns an array of Asset objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Asset
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this->getEntityManager()->getClassMetadata(Asset::class)->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(AssetStatus::class)
                ->getFieldNames(),
            ['createdAt_gte', 'createdAt_lt', 'status'],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('asset')
            ->from(Asset::class, 'asset')
            ->leftJoin('asset.assetStatus', 'status');

        if (!empty(array_intersect(['status'], array_keys($filters)))) {
            // Potential querying cost to these, so only join if there are filters that need them
            // https://stackoverflow.com/a/2111420
            if (isset($filters['status']) && !empty($filters['status'])) {
                $this->logger->debug('Applying status log filters');
                $qb
                    ->leftJoin('asset.statusLogs', alias: 'status_log')
                    ->leftJoin(
                        'asset.statusLogs',
                        alias: 'status_log_comparison',
                        conditionType: Expr\Join::WITH,
                        condition: $qb->expr()->andX(
                            $qb->expr()->eq('asset.id', 'status_log_comparison.asset'),
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

            if (in_array($key, ['name', 'companyNumber'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('asset.' . $key, ':' . $key));
            } elseif (in_array($key, ['lifecycleStatus'])) {
                // status related
                $qb->andWhere($qb->expr()->in('status.' . $key, ':' . $key));
            } elseif (in_array($key, ['featured'])) {
                if ($value) {
                    $qb->andWhere($qb->expr()->gte('asset.' . $key, 1));
                } else {
                    $qb->andWhere($qb->expr()->eq('asset.' . $key, 0));
                }
                // No value to set, so continue to next filter
                continue;
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                // $key = str_replace('-', '', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'asset.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'asset.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } elseif (in_array($key, ['status'])) {
                // status_log related
                if (
                    is_array($value)
                    && (
                        in_array(EnumAssetStatus::Draft, $value)
                        || in_array(EnumAssetStatus::Draft->value, $value)
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
                $qb->andWhere($qb->expr()->in('asset.' . $key, ':' . $key));
            }

            if (in_array($key, ['name', 'companyNumber'])) {
                // note that the preceding % wildcard has a high performance cost
                $qb->setParameter($key, '%' . addcslashes($value, '%_') . '%');
            } elseif ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                $qb->setParameter($key, $value->format('Y-m-d'));
            } else {
                $qb->setParameter($key, $value);
            }
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('asset.' . $key, $direction);
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
     * @return Asset[]
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

    public function getAssetData(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select * from getAssetData order by id DESC';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function findAllPagerfanta(
        $page,
        $limit,
        array $idArray = [],
        $assetTypeFilter = '',
        $statusFilter = '',
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('a')
            ->from(Asset::class, 'a')
            ->leftJoin('a.assetStatus', 'assetStatus');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('a.id', $idArray));
        }

        if (!empty($assetTypeFilter)) {
            $qb->andWhere('a.assetType = :type')->setParameter(
                'type',
                $assetTypeFilter,
            );
        }

        if (!empty($statusFilter)) {
            $qb->andWhere('assetStatus.lifecycleStatus = :status')->setParameter(
                'status',
                $statusFilter,
            );
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findAllPublished(
        $page,
        $limit,
        array $idArray = [],
        $assetTypeFilter = '',
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('a')
            ->from(Asset::class, 'a')
            ->leftJoin('a.assetStatus', 'assetStatus');
        $qb->andWhere('assetStatus.lifecycleStatus = :status')->setParameter(
            'status',
            'published',
        );

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('a.id', $idArray));
        }

        if (!empty($assetTypeFilter)) {
            $qb->andWhere('a.assetType = :type')->setParameter(
                'type',
                $assetTypeFilter,
            );
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function getAsIdAndIdentifier(bool $hideCancelled = true): \Traversable
    {
        $dbalConnection = $this->getEntityManager()->getConnection();
        $qb = $dbalConnection->createQueryBuilder();
        $qb
            ->select(
                'ast.id AS id',
                'ast.companyNumber AS companyNumber',
                'ast.name AS identifier',
            )
            ->from('assets', 'ast')
            ->leftJoin(
                'ast',
                'asset_status_log',
                'ast_status',
                condition: 'ast.id = ast_status.asset_id',
            )
            ->leftJoin(
                'ast',
                'asset_status_log',
                'ast_status_comp',
                'ast.id = ast_status_comp.asset_id AND (ast_status.occuredAt < ast_status_comp.occuredAt OR (ast_status.occuredAt = ast_status_comp.occuredAt AND ast_status.id < ast_status_comp.id))',
            )
            ->andWhere('ast_status_comp.id IS NULL')
            ->orderBy('id', 'DESC');
        if ($hideCancelled) {
            $qb->andWhere(
                'ast_status.status != :statusName OR ast_status.status IS NULL',
            )->setParameter('statusName', EnumAssetStatus::Cancelled->value);
        }
        $stmt = $qb->executeQuery();
        return $stmt->iterateAssociative();
    }
}
