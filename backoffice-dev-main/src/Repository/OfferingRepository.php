<?php

namespace App\Repository;

use App\Entity\Enum\AssetStatus;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\OfferingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Offering>
 *
 * @method Offering|null find($id, $lockMode = null, $lockVersion = null)
 * @method Offering|null findOneBy(array $criteria, array $orderBy = null)
 * @method Offering[]    findAll()
 * @method Offering[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, Offering::class);
    }

    public function save(Offering $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Offering $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return Offering[] Returns an array of Offering objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Offering
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByAssetId(int $assetId): ?array
    {
        return $this
            ->getEntityManager()
            ->getRepository(Offering::class)
            ->findBy(['asset' => $assetId]);
    }

    public function findPublishedByAssetId(int $assetId): ?array
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('o')
            ->from(Offering::class, 'o')
            ->leftJoin('o.offeringStatus', 'offeringsStatus')
            ->where('o.asset = :assetId')
            ->setParameter('assetId', $assetId)
            ->andWhere('offeringsStatus.lifecycleStatus = :status')
            ->setParameter('status', 'published')
            ->getQuery()
            ->getResult();
    }

    public function findAllPagerfanta(
        int $page,
        int $limit,
        array $idArray = [],
        $statusFilter = '',
        bool $isFeaturedFilter = false,
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('o')
            ->from(Offering::class, 'o')
            ->leftJoin('o.offeringStatus', 'offeringStatus');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('o.id', $idArray));
        }

        if (!empty($statusFilter)) {
            $qb->andWhere('offeringStatus.lifecycleStatus = :status')->setParameter(
                'status',
                $statusFilter,
            );
        }

        if (!empty($isFeaturedFilter)) {
            $qb->andWhere('o.isFeatured = :featured')->setParameter(
                'featured',
                $isFeaturedFilter,
            );
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findAllPublished($page, $limit): ?Pagerfanta
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('o')
            ->from(Offering::class, 'o')
            ->leftJoin('o.offeringStatus', 'offeringsStatus')
            ->andWhere('offeringsStatus.lifecycleStatus = :status')
            ->setParameter('status', 'published');

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findFirstPartyByAssetId(int $assetId): ?Offering
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('offering')
            ->from(Offering::class, 'offering')
            ->andWhere('offering.sell_investment IS NULL')
            ->andWhere('offering.asset = :assetId')
            ->setParameter('assetId', $assetId)
            ->setMaxResults(1);
        return $qb->getQuery()->getOneOrNullResult();
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
        array $groupBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(Offering::class)
                ->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(Offering::class)
                ->getAssociationNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(OfferingStatus::class)
                ->getFieldNames(),
            [
                'assetId',
                'assetName',
                'assetCompanyNumber',
                'assetCurrentStatus',
                'assetAssetType',
                'investmentUser',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('offering')
            ->from(Offering::class, 'offering')
            ->leftJoin('offering.offeringStatus', 'status')
            ->leftJoin('offering.asset', 'asset')
            ->leftJoin('offering.sell_investment', 'investment');

        if (!empty(array_intersect(['assetCurrentStatus'], array_keys($filters)))) {
            // Potential querying cost to these, so only join if there are filters that need them
            // https://stackoverflow.com/a/2111420
            if (
                isset($filters['assetCurrentStatus'])
                && !empty($filters['assetCurrentStatus'])
            ) {
                $this->logger->debug('Applying status log filters');
                $qb
                    ->leftJoin(
                        'asset.statusLogs',
                        alias: 'asset_status_log',
                        conditionType: Expr\Join::WITH,
                        condition: $qb->expr()->eq(
                            'asset.id',
                            'asset_status_log.asset',
                        ),
                    )
                    ->leftJoin(
                        'asset.statusLogs',
                        alias: 'asset_status_log_comparison',
                        conditionType: Expr\Join::WITH,
                        condition: $qb->expr()->andX(
                            $qb->expr()->eq(
                                'asset.id',
                                'asset_status_log_comparison.asset',
                            ),
                            $qb->expr()->orX(
                                $qb->expr()->lt(
                                    'asset_status_log.occuredAt',
                                    'asset_status_log_comparison.occuredAt',
                                ),
                                $qb->expr()->andX(
                                    $qb->expr()->eq(
                                        'asset_status_log.occuredAt',
                                        'asset_status_log_comparison.occuredAt',
                                    ),
                                    $qb->expr()->lt(
                                        'asset_status_log.id',
                                        'asset_status_log_comparison.id',
                                    ),
                                ),
                            ),
                        ),
                    )
                    ->andWhere('asset_status_log_comparison.id IS NULL');
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

            if (in_array($key, ['name', 'createdBy'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('offering.' . $key, ':' . $key));
            } elseif (in_array($key, ['lifecycleStatus'])) {
                // status related
                $qb->andWhere($qb->expr()->in('status.' . $key, ':' . $key));
            } elseif (in_array($key, ['investmentUser'])) {
                // asset related equivalences
                $field = lcfirst(substr($key, strlen('investment')));
                $qb->andWhere($qb->expr()->in('investment.' . $field, ':' . $key));
            } elseif (in_array($key, [
                'assetId',
                'assetName',
                'assetCompanyNumber',
                'assetAssetType',
            ])) {
                // asset related equivalences
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['companyNumber', 'name'])) {
                    $qb->andWhere($qb->expr()->like('asset.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['assetCurrentStatus'])) {
                // asset status_log related
                $field = lcfirst(substr($key, strlen('assetCurrent')));
                if (is_array($value) && in_array(AssetStatus::Draft, $value)) {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('asset_status_log.' . $field, ':' . $key),
                        $qb->expr()->isNull('asset_status_log'),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->in(
                        'asset_status_log.' . $field,
                        ':' . $key,
                    ));
                }
            } elseif (in_array($key, ['sell_investment', 'tradeOrder'])) {
                // null checks
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('offering.' . $key));
                } else {
                    $qb->andWhere($qb->expr()->isNull('offering.' . $key));
                }
                continue;
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'offering.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'offering.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('offering.' . $key, ':' . $key));
            }

            if (in_array($key, [
                'name',
                'createdBy',
                'assetCompanyNumber',
                'assetName',
            ])) {
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
                $qb->addOrderBy('offering.' . $key, $direction);
            }
        }
        foreach ($groupBy as $key) {
            if (in_array($key, $filtersAllowed)) {
                if (in_array($key, ['assetId'])) {
                    $field = lcfirst(substr($key, strlen('asset')));
                    $qb->groupBy('asset.' . $field);
                } else {
                    $qb->groupBy('offering.' . $key);
                }
            }
        }
        return $qb->getQuery();
    }

    public function findByWithAssociations(
        array $filters,
        array $orderBy = [],
        int $limit = 10,
        int $page = 1,
        array $groupBy = [],
    ): Pagerfanta {
        $query = $this->buildQueryWithAssociations($filters, $orderBy, $groupBy);
        $adapter = new QueryAdapter($query);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage(min($page, $pagerfanta->getNbPages()));
        return $pagerfanta;
    }

    public function findAggregatedOfferingValues(
        Offering $offering,
        bool $approved = true,
        bool $settled = true,
    ): ?array {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(i) AS totalInvestments')
            ->addSelect('COUNT(distinct i.user) AS totalInvestors')
            ->addSelect('SUM(i.investmentValue) AS raisedAmount')
            ->addSelect('SUM(i.numberOfShares) AS sharesSold')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'investmentStatus');
        if ($approved and $settled) {
            $qb->andWhere(
                "investmentStatus.lifecycleStatus = 'approved' OR investmentStatus.lifecycleStatus = 'settled'",
            );
        } elseif ($approved and !$settled) {
            $qb->andWhere("investmentStatus.lifecycleStatus = 'approved'");
        } elseif ($settled and !$approved) {
            $qb->andWhere("investmentStatus.lifecycleStatus = 'settled'");
        }
        if ('retail' == $offering->getOfferingType()) {
            $qb->andWhere("i.type NOT IN ('prefunding')");
        }
        $qb->andWhere('i.offering = :offering');
        $qb->setParameter('offering', $offering);

        return $qb->getQuery()->getSingleResult();
    }

    public function findAllFirstParty(): array
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('o')
            ->from(Offering::class, 'o')
            ->leftJoin('o.offeringStatus', 'offeringsStatus')
            ->andWhere('o.sell_investment IS NULL')
            ->andWhere('offeringsStatus.lifecycleStatus = :status')
            ->setParameter('status', 'published')
            ->addOrderBy('o.fundingGoal', 'DESC');
        return $qb->getQuery()->getResult();
    }

    public function findFirstPartyTotal(): float
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('SUM(o.fundingGoal)')
            ->from(Offering::class, 'o')
            ->leftJoin('o.offeringStatus', 'offeringsStatus')
            ->andWhere('o.sell_investment IS NULL')
            ->andWhere('offeringsStatus.lifecycleStatus = :status')
            ->setParameter('status', 'published');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findRelistingsByYear(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select('COUNT(ofr.id) AS count', 'YEAR(ofr.createdAt) AS year')
            ->from('offerings', 'ofr')
            ->leftJoin(
                'ofr',
                'offerings_status',
                'offstat',
                'ofr.offeringStatus_id = offstat.id',
            )
            ->where('ofr.inv_id IS NOT NULL')
            ->andWhere('offstat.isPublished = 1')
            ->groupBy('YEAR(ofr.createdAt)')
            ->orderBy('ofr.createdAt', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findRelistingsByMonth(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'COUNT(ofr.id) AS count',
                'YEAR(ofr.createdAt) AS year',
                'MONTH(ofr.createdAt) AS month',
            )
            ->from('offerings', 'ofr')
            ->leftJoin(
                'ofr',
                'offerings_status',
                'offstat',
                'ofr.offeringStatus_id = offstat.id',
            )
            ->where('ofr.inv_id IS NOT NULL')
            ->andWhere('offstat.isPublished = 1')
            ->groupBy('MONTH(ofr.createdAt)')
            ->orderBy('ofr.createdAt', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    /**
     * Count of Open Offerings in Approved and Settled state
     * @return mixed
     */
    public function getOfferingCount()
    {
        $conn = $this->getEntityManager()->getConnection();

        //only return investments in approved or settled state
        $sql =
            'select count(*) as offering_count from offerings off, offerings_status offstatus '
            . ' where off.offeringStatus_id = offstatus.id '
            . ' and offstatus.lifecycleStatus in (\'approved\')';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result[0]['offering_count'];
    }

    /**
     * Calculated field that returns the number of investors against ths offering
     * @return mixed
     *
     * @param int $off_id
     *
     */
    public function getInvestorCount($off_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        //only return investments in approved or settled state
        $sql =
            'SELECT count(distinct user_id) as investment_count FROM investments inv, investments_status inv_st'
            . ' where  inv.investmentStatus_id = inv_st.id and inv_st.lifecycleStatus in (\'approved\', \'settled\')'
            . ' and off_id = ?';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $off_id);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result[0]['investment_count'];
    }

    /**
     * Calculated field that returns the number of investments against ths offering
     * @return mixed
     *
     * @param int $off_id
     *
     */
    public function getInvestmentCount($off_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        //only return investments in approved or settled state
        $sql =
            'SELECT count(inv.id) as investment_count FROM investments inv, investments_status inv_st'
            . ' where  inv.investmentStatus_id = inv_st.id and inv_st.lifecycleStatus in'
            . '(\'approved\', \'settled\')'
            . ' and off_id = ?';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $off_id);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        return $result[0]['investment_count'];
    }

    /**
     * Calculated field that returns the amount raised for the offering
     *
     * Offering Id @param $off_id
     *
     * @return mixed
     */
    public function getRaisedAmount($off_id, $off_ext_commit)
    {
        $conn = $this->getEntityManager()->getConnection();
        //only return investments in approved or settled state
        $sql =
            'select sum(investmentValue) as investment_sum FROM investments inv, investments_status inv_st'
            . ' where  inv.investmentStatus_id = inv_st.id and inv_st.lifecycleStatus in (\'approved\', \'settled\')'
            . ' and off_id = ?';

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, $off_id);
        $result = $stmt->executeQuery()->fetchAllAssociative();
        //need to add the offering external commit
        $raised_amount = $result[0]['investment_sum'];

        return $raised_amount + $off_ext_commit;
    }

    /**
     * All offerings data for export
     */
    public function getOfferingData(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select * from getOfferingData order by id DESC';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    public function getOfferingsWithExternalCommits(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('o')
            ->from(Offering::class, 'o')
            ->andWhere('o.externalCommitments > 0');
        return $qb->getQuery()->getResult();
    }

    public function queryAssetListingSummary(array $assetIds = []): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'ast.id AS assetId',
                'COUNT(ast.id) AS listings',
                'SUM(CASE WHEN ofr.inv_id IS NOT NULL THEN 1 ELSE 0 END) AS relistings',
                'SUM(ofr.noOfShares) AS sharesListed',
                'SUM(ofr.noOfShares * COALESCE(NULLIF(ofr.pricePerShare, 0), ast.pricePerShare)) AS valueListed',
                'SUM(ofr.fundingGoal) AS fundingGoal',
                'ROUND(SUM(ofr.fundingGoal / COALESCE(NULLIF(ofr.pricePerShare, 0), ast.pricePerShare)), 0) AS equivalentSharesListed',
                'offstat.lifecycleStatus AS status',
            )
            ->from('offerings', 'ofr')
            ->leftJoin(
                'ofr',
                'offerings_status',
                'offstat',
                'ofr.offeringStatus_id = offstat.id',
            )
            ->leftJoin('ofr', 'assets ', 'ast', 'ofr.asset_id = ast.id')
            ->where(
                "offstat.lifecycleStatus IN ('draft', 'submitted', 'approved', 'published')",
            )
            ->groupBy('offstat.lifecycleStatus', 'ast.id')
            ->orderBy('ast.id', 'ASC');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function queryRaisedAmount(
        array $filters = [],
        string $orderDirection = 'DESC',
        int $limit = 10,
        int $page = 1,
    ): ?array {
        $conn = $this->getEntityManager()->getConnection();

        $subqb = $conn->createQueryBuilder();
        $subqb
            ->select(
                'ofr.id AS offeringId',
                'ROUND(SUM(inv.investmentValue), 2) AS raisedAmount',
            )
            ->from('investments', 'inv')
            ->leftJoin('inv', 'offerings', 'ofr', 'ofr.id = inv.off_id')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->where("invstatus.lifecycleStatus = 'approved'")
            ->orWhere("invstatus.lifecycleStatus = 'settled'")
            ->groupBy('ofr.id');

        if (!($filters['includePrefunding'] ?? false)) {
            $subqb->andWhere("inv.type != 'prefunding'");
        }

        if (!in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }

        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'ofr.id AS offeringId',
                'ofr.name AS offeringName',
                'ast.id AS assetId',
                'ast.name AS assetName',
                'ast.companyNumber AS assetSpv',
                'ofr.fundingGoal AS fundingGoal',
                'raised.raisedAmount AS raisedAmount',
                'offstat.lifecycleStatus AS offeringStatus',
                'ofr.createdAt AS createdAt',
                'offstat.publishedOn AS publishedAt',
                'ofr.inv_id AS relistingInvestmentId',
            )
            ->from('offerings', 'ofr')
            ->leftJoin(
                'ofr',
                'offerings_status',
                'offstat',
                'ofr.offeringStatus_id = offstat.id',
            )
            ->leftJoin('ofr', 'assets ', 'ast', 'ofr.asset_id = ast.id')
            ->leftJoin('ofr', 'investments', 'inv', 'ofr.id = inv.off_id')
            ->leftJoin(
                'ofr',
                sprintf('(%s)', $subqb->getSQL()),
                'raised',
                'ofr.id = raised.offeringId',
            )
            ->groupBy('ofr.id')
            ->orderBy('ofr.id', $orderDirection);

        if (!empty($filters['lifecycleStatus'])) {
            $qb->andWhere('offstat.lifecycleStatus IN (:statuses)')->setParameter(
                'statuses',
                $filters['lifecycleStatus'],
                \Doctrine\DBAL\ArrayParameterType::STRING,
            );
        }

        if (!is_null($filters['fundingProgress'] ?? null)) {
            $condition = match ($filters['fundingProgress']) {
                0 => 'raisedAmount IS NULL',
                1 => 'raisedAmount IS NOT NULL AND raisedAmount < ofr.fundingGoal',
                99 => 'raisedAmount IS NULL OR (raisedAmount < ofr.fundingGoal)',
                100 => 'raisedAmount >= ofr.fundingGoal',
                101 => 'raisedAmount IS NOT NULL',
                default => null,
            };
            if ($condition) {
                $qb->andWhere($condition);
            }
        }

        if (!is_null($filters['firstPartyOnly'] ?? null)) {
            if ($filters['firstPartyOnly']) {
                $qb->andWhere('ofr.inv_id IS NULL');
            } else {
                $qb->andWhere('ofr.inv_id IS NOT NULL');
            }
        }

        // $this->logger->debug($qb->getSQL());
        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getAsIdAndIdentifier(): \Traversable
    {
        $dbalConnection = $this->getEntityManager()->getConnection();
        $qb = $dbalConnection->createQueryBuilder();
        $qb
            ->select('id', 'name AS identifier')
            ->from('offerings', 'ofr')
            ->orderBy('id', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->iterateAssociative();
    }
}
