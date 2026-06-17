<?php

namespace App\Repository;

use App\Entity\Investment;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<Investment>
 *
 * @method Investment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Investment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Investment[]    findAll()
 * @method Investment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvestmentRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private LoggerInterface $logger,
    ) {
        parent::__construct($registry, Investment::class);
    }

    public function save(Investment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Investment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return Investment[] Returns an array of Investment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Investment
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
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
            $this
                ->getEntityManager()
                ->getClassMetadata(Investment::class)
                ->getFieldNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(Investment::class)
                ->getAssociationNames(),
            $this
                ->getEntityManager()
                ->getClassMetadata(InvestmentStatus::class)
                ->getFieldNames(),
            [
                'userId',
                'assetId',
                'assetName',
                'offeringId',
                'userIsVIP',
                'corporateInvestor',
                'hasDocuments',
                'username',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('investment')
            ->from(Investment::class, 'investment')
            ->leftJoin('investment.investmentStatus', 'status')
            ->leftJoin('investment.user', 'user')
            ->leftJoin('user.investor', 'investor')
            ->leftJoin('investment.offering', 'offering')
            ->leftJoin('offering.asset', 'asset');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['userId', 'userIsVIP'])) {
                // using strlen instead of plain integer for human readability
                $field = lcfirst(substr($key, strlen('user')));
                $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
            } elseif (in_array($key, ['username'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('user.' . $key, ':' . $key));
            } elseif (in_array($key, ['assetId', 'assetName'])) {
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['name'])) {
                    $qb->andWhere($qb->expr()->like('asset.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
                }
            } elseif ('offeringId' === $key) {
                $qb->andWhere($qb->expr()->in('offering.id', ':' . $key));
            } elseif (in_array($key, ['hasDocuments'])) {
                // collections existence
                $field = lcfirst(substr($key, strlen('has')));
                if ($value) {
                    $qb->andWhere('investment.' . $field . ' IS NOT EMPTY');
                } else {
                    $qb->andWhere('investment.' . $field . ' IS EMPTY');
                }
                continue;
            } elseif ('corporateInvestor' === $key) {
                // investor user relation
                if ($value) {
                    $qb->andWhere($qb->expr()->in('investor.' . $key, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('investor.' . $key, ':' . $key),
                        $qb->expr()->isNull('investor.' . $key),
                    ));
                }
            } elseif (in_array($key, ['lifecycleStatus'])) {
                // status related
                $qb->andWhere($qb->expr()->in('status.' . $key, ':' . $key));
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'investment.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'investment.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } elseif (in_array($key, ['shareTrade', 'tradeOrder'])) {
                // null checks
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('investment.' . $key));
                } else {
                    $qb->andWhere($qb->expr()->isNull('investment.' . $key));
                }
                continue;
            } else {
                $qb->andWhere($qb->expr()->in('investment.' . $key, ':' . $key));
            }

            if (in_array($key, ['username', 'assetName'])) {
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
                $qb->addOrderBy('investment.' . $key, $direction);
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
     * @return Investment[]
     */
    public function findPrefundingInvestments(?int $offeringId = null): array
    {
        $filters = [
            'lifecycleStatus' => InvestmentLifecycle::STATE_SETTLED,
            'type' => 'prefunding',
        ];
        if (!is_null($offeringId)) {
            $filters['offeringId'] = $offeringId;
        }
        $prefundingInvestments = $this->buildQueryWithAssociations($filters, [
            'id' => 'DESC',
        ])->getResult();
        return $prefundingInvestments;
    }

    public function countInvestmentsInDateRangeByStatus(
        string $status,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): int {
        return $this->findByWithAssociations([
            'lifecycleStatus' => $status,
            'createdAt_gte' => $start,
            'createdAt_lt' => $end,
        ])->count();
    }

    public function findAllPagerfanta(
        int $page,
        int $limit,
        array $idArray = [],
        $statusFilter = '',
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'investmentStatus');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('i.id', $idArray));
        }

        if (!empty($statusFilter)) {
            $qb->andWhere('investmentStatus.lifecycleStatus = :status')->setParameter(
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

    public function findAllQuery(
        ?\App\Entity\User $investmentUser = null,
        array $idArray = [],
        array $statusFilterArray = [],
    ): QueryBuilder {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'investmentStatus');

        if (!empty($investmentUser)) {
            $qb->andWhere('i.user = :invUser')->setParameter(
                'invUser',
                $investmentUser,
            );
        }

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('i.id', $idArray));
        }

        if (!empty($statusFilterArray)) {
            $qb->andWhere($qb->expr()->in(
                'investmentStatus.lifecycleStatus',
                $statusFilterArray,
            ));
        }

        return $qb;
    }

    public function findAllByAssetsQuery(
        array $idArray = [],
        string $statusFilter = '',
        array $assetIds = [],
        bool $pendingSettlement = false,
        ?\DateTime $beforeDate = null,
    ): QueryBuilder {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'investmentStatus')
            ->leftJoin('i.offering', 'offering')
            ->leftJoin('offering.asset', 'asset');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('i.id', $idArray));
        }

        if (!empty($assetIds)) {
            $qb->andWhere($qb->expr()->in('asset.id', $assetIds));
        }

        if (!empty($statusFilter) and !$pendingSettlement) {
            $qb->andWhere('investmentStatus.lifecycleStatus = :status')->setParameter(
                'status',
                $statusFilter,
            );
        }

        if ($pendingSettlement) {
            $qb->andWhere("investmentStatus.lifecycleStatus = 'approved' ");
        }

        if ($beforeDate) {
            $beforeDate->modify('today midnight');
            $qb->andWhere('i.createdAt < :beforeDate');
            $qb->setParameter('beforeDate', $beforeDate);
        }

        return $qb;
    }

    public function findAllStampDutyDueQuery(
        array $idArray = [],
        string $statusFilter = '',
        array $assetIds = [],
        ?\DateTime $beforeDate = null,
    ): QueryBuilder {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'investmentStatus')
            ->leftJoin('i.offering', 'offering')
            ->leftJoin('i.addFields', 'addField')
            ->leftJoin('offering.asset', 'asset');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('i.id', $idArray));
        }

        if (!empty($assetIds)) {
            $qb->andWhere($qb->expr()->in('asset.id', $assetIds));
        }

        $qb->andWhere("investmentStatus.lifecycleStatus = 'approved'");
        $qb->andWhere('i.investmentValue >= 1000');
        $qb->andWhere("i.type = 'normal' OR i.type = 'off-market' ");
        $qb->andWhere("addField.fieldValue = 'stamp duty transfer pending'");

        if ($beforeDate) {
            $qb->andWhere('i.createdAt < :beforeDate');
            $qb->setParameter('beforeDate', $beforeDate);
        }

        return $qb;
    }

    public function findAggregatedInvestmentTotal(
        ?\App\Entity\User $user = null,
        array $idArray = [],
        array $statusFilterArray = [],
    ) {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->from(Investment::class, 'i')
            ->select('SUM(i.investmentValue) AS totalInvested');

        if (!empty($user)) {
            $qb->andWhere('i.user = :user');
            $qb->setParameter('user', $user);
        }

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('i.id', $idArray));
        }

        if (!empty($statusFilterArray)) {
            $qb->leftJoin('i.investmentStatus', 'investmentStatus');
            $qb->andWhere($qb->expr()->in(
                'investmentStatus.lifecycleStatus',
                $statusFilterArray,
            ));
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findUserSettlementsInDateRange(
        int $userId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'status')
            ->andWhere('i.user = :user')
            ->andWhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->andWhere($qb->expr()->gte('status.settledOn', ':fromDate'))
            ->andWhere($qb->expr()->lt('status.settledOn', ':toDate'))
            ->setParameter('user', $userId)
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('fromDate', $startDate)
            ->setParameter('toDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    public function findSettlementsInDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
        ?int $assetId = null,
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'status')
            ->andWhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->andWhere($qb->expr()->gte('status.settledOn', ':fromDate'))
            ->andWhere($qb->expr()->lt('status.settledOn', ':toDate'))
            ->addOrderBy('i.id', 'DESC')
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('fromDate', $startDate)
            ->setParameter('toDate', $endDate);
        if (!is_null($assetId)) {
            $qb
                ->leftJoin('i.offering', 'offering')
                ->andWhere('offering.asset = :asset')
                ->setParameter('asset', $assetId);
        }
        return $qb->getQuery()->getResult();
    }

    public function findUserSalesInDateRange(
        int $userId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'status')
            ->leftJoin('i.offering', 'offering')
            ->leftJoin('offering.sell_investment', 'si')
            ->andWhere('si.user = :user')
            ->andWhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->andWhere($qb->expr()->gte('status.settledOn', ':fromDate'))
            ->andWhere($qb->expr()->lt('status.settledOn', ':toDate'))
            ->setParameter('user', $userId)
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('fromDate', $startDate)
            ->setParameter('toDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    public function findUserInvestmentsInAsset(
        int $assetId,
        int $userId,
        bool $isPrefunding = false,
    ) {
        // need workaround for share_amount being a varchar (string) instead of int
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('investment')
            ->addSelect('investment.share_amount + 0 AS HIDDEN sharesAsInt')
            ->from(Investment::class, 'investment')
            ->leftJoin('investment.investmentStatus', 'status')
            ->leftJoin('investment.user', 'user')
            ->leftJoin('investment.offering', 'offering')
            ->leftJoin('offering.asset', 'asset')
            ->andWhere('status.lifecycleStatus = :status')
            ->andWhere('asset.id = :assetId')
            ->andWhere('user.id = :userId');
        if ($isPrefunding) {
            $qb->andWhere('investment.type = :investmentType')->setParameter(
                'investmentType',
                'prefunding',
            );
        }

        $qb
            ->addOrderBy('sharesAsInt', 'ASC')
            ->setParameter('status', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('assetId', $assetId)
            ->setParameter('userId', $userId);
        return $qb->getQuery()->getResult();
    }

    public function getAumByYear(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'YEAR(invstatus.settledOn) AS year',
                'ROUND(SUM(inv.investmentValue), 2) AS total',
                'COUNT(inv.id) AS count',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->leftJoin('inv', 'offerings', 'ofr', 'inv.off_id = ofr.id')
            ->where('invstatus.lifecycleStatus IN ("settled", "approved")')
            ->andWhere('ofr.inv_id IS NULL')
            ->andWhere('invstatus.settledOn IS NOT NULL')
            ->andWhere('inv.type = "normal"')
            ->groupBy('YEAR(inv.createdAt)');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findInvestmentsOverTime(array $filterDates = []): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'COUNT(inv.createdAt) AS count',
                'SUM(inv.investmentValue) AS total',
                'CONCAT_WS("-", YEAR(inv.createdAt), MONTH(inv.createdAt)) AS date',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->where('invstatus.isSettled = 1')
            ->groupBy('YEAR(inv.createdAt)', 'MONTH(inv.createdAt)')
            ->orderBy('inv.createdAt', 'DESC');
        if (count($filterDates) == 2) {
            $qb
                ->andWhere('inv.createdAt BETWEEN :startDate AND :filterdate')
                ->setParameter('startDate', $filterDates[0])
                ->setParameter('filterdate', $filterDates[1]);
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUniqueInvestors(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'COUNT(DISTINCT(usr.id)) AS count',
                'YEAR(invstatus.settledOn) AS year',
                'MONTHNAME(invstatus.settledOn) AS month',
            )
            ->from('investments', 'inv')
            ->leftJoin('inv', 'users', 'usr', 'inv.user_id = usr.id')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->where('invstatus.isSettled = 1')
            ->groupBy('YEAR(invstatus.settledOn)', 'MONTHNAME(invstatus.settledOn)');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findInvestorInvestmentCounts(?int $limit = null): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'inv.user_id AS userId',
                // 'usr.username AS username',
                'COUNT(inv.user_id) AS count',
                // 'SUM(inv.investmentValue) AS totalInvested'
            )
            ->from('investments', 'inv')
            ->leftJoin('inv', 'users', 'usr', 'inv.user_id = usr.id')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->where('invstatus.isSettled = 1')
            ->groupBy('inv.user_id')
            ->orderBy('count', 'DESC');
        if (!is_null($limit)) {
            $qb->setMaxResults($limit);
        }
        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUserReferrals(array $filterDates = []): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'DISTINCT(usrs.referralCode) as referralCode',
                'COUNT(usrs.referralCode) as referralCodeCount',
                'COUNT(DISTINCT(usrs.id)) as usersCount',
                'SUM(inv.investmentValue) as investmentsValue',
            )
            ->from('investments', 'inv')
            ->leftJoin('inv', 'users', 'usrs', 'inv.user_id = usrs.id')
            ->where('usrs.referralCode IS NOT NULL')
            ->groupBy('usrs.referralCode')
            ->orderBy('COUNT(usrs.referralCode)', 'DESC')
            ->setMaxResults(10);

        if ($filterDates) {
            $qb
                ->andWhere('inv.createdAt BETWEEN :startDate AND :filterdate')
                ->setParameter('startDate', $filterDates[0])
                ->setParameter('filterdate', $filterDates[1]);
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getInvestmentsSummary(string $groupBy = 'year'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'ROUND(SUM(inv.investmentValue), 2) AS platformTotal',
                'ROUND(SUM(IF(ofr.inv_id IS NULL, inv.investmentValue, 0)), 2) AS firstPartyTotal',
                'ROUND(SUM(IF(ofr.inv_id IS NOT NULL, inv.investmentValue, 0)), 2) AS secondaryMarketTotal',
                'MIN(inv.createdAt) AS firstInvested',
                'MAX(IF(ofr.inv_id IS NULL, inv.createdAt, NULL)) AS lastInvestedOriginal',
                'MAX(IF(ofr.inv_id IS NOT NULL, inv.createdAt, NULL)) AS lastInvestedSecondary',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstatus',
                'inv.investmentStatus_id = invstatus.id',
            )
            ->leftJoin('inv', 'offerings', 'ofr', 'inv.off_id = ofr.id')
            ->leftJoin('ofr', 'assets', 'ast', 'ofr.asset_id = ast.id')
            ->where('invstatus.isSettled = 1')
            ->andWhere('inv.type = "normal"')
            ->orderBy('inv.createdAt', 'DESC');
        if (!in_array($groupBy, ['year', 'month', 'asset'])) {
            $groupBy = 'year';
        }
        switch ($groupBy) {
            case 'year':
                $qb->addSelect('YEAR(inv.createdAt) AS year')->groupBy(
                    'YEAR(inv.createdAt)',
                );
                break;
            case 'month':
                $qb->addSelect(
                    'YEAR(inv.createdAt) AS year',
                    'MONTH(inv.createdAt) AS month',
                )->groupBy('YEAR(inv.createdAt)', 'MONTH(inv.createdAt)');
                break;
            case 'asset':
                $qb->addSelect(
                    'ast.id AS assetId',
                    'ast.name AS assetName',
                    'ast.companyNumber AS assetSpvId',
                    'ast.fundingGoal AS assetValue',
                )->groupBy('ast.id');
                break;
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUserSettlementsCountInDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ) {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'inv.user_id AS user',
                'COUNT(inv.id) AS count',
                'ROUND(SUM(inv.investmentValue), 2) AS value',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'status',
                'inv.investmentStatus_id = status.id',
            )
            ->andwhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->andWhere($qb->expr()->gte('status.settledOn', ':fromDate'))
            ->andWhere($qb->expr()->lt('status.settledOn', ':toDate'))
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('fromDate', $startDate->format('Y-m-d'))
            ->setParameter('toDate', $endDate->format('Y-m-d'))
            ->addGroupBy('inv.user_id')
            ->addOrderBy('count', 'DESC')
            ->setMaxResults(10);

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUserSalesCountInDateRange(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ) {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'si.user_id AS user',
                'COUNT(inv.id) AS count',
                'ROUND(SUM(inv.investmentValue), 2) AS value',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'status',
                'inv.investmentStatus_id = status.id',
            )
            ->leftJoin('inv', 'offerings', 'offr', 'inv.off_id = offr.id')
            ->leftJoin('offr', 'investments', 'si', 'offr.inv_id = si.id')
            ->andwhere('offr.inv_id IS NOT NULL')
            ->andwhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->andWhere($qb->expr()->gte('status.settledOn', ':fromDate'))
            ->andWhere($qb->expr()->lt('status.settledOn', ':toDate'))
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->setParameter('fromDate', $startDate->format('Y-m-d'))
            ->setParameter('toDate', $endDate->format('Y-m-d'))
            ->addGroupBy('si.user_id')
            ->addOrderBy('count', 'DESC')
            ->setMaxResults(10);

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUserPositionsCount()
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'inv.user_id AS user',
                'COUNT(DISTINCT ast.id) AS count',
                'ROUND(SUM(inv.investmentValue), 2) AS value',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'status',
                'inv.investmentStatus_id = status.id',
            )
            ->leftJoin('inv', 'offerings', 'offr', 'inv.off_id = offr.id')
            ->leftJoin('offr', 'assets', 'ast', 'offr.asset_id = ast.id')
            ->andwhere('status.isSettled = 1')
            ->andWhere('status.lifecycleStatus = :lifecycleStatus')
            ->setParameter('lifecycleStatus', InvestmentLifecycle::STATE_SETTLED)
            ->addGroupBy('inv.user_id')
            ->addOrderBy('count', 'DESC')
            ->setMaxResults(10);

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getInvestmentsByAssetId(int $assetId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.offering', 'o')
            ->leftJoin('o.asset', 'a')
            ->andWhere('a.id = :assetId')
            ->setParameter('assetId', $assetId);
        return $qb->orderBy('i.id', 'DESC')->getQuery()->getResult();
    }

    public function findByRelationCriteria(
        array $criteria = [],
        $limit = null,
        $offset = null,
    ) {
        /**
         * Prototype findBy alternative for dealing with common asset, offering, status relations
         */
        $criteria = $this->prepareRelationCriteria($criteria);
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('i')
            ->from(Investment::class, 'i')
            ->leftJoin('i.investmentStatus', 'istatus')
            ->leftJoin('i.offering', 'o')
            ->leftJoin('o.asset', 'a');

        if (isset($criteria['assetId']) && is_array($criteria['assetId'])) {
            $qb->andWhere('a.id IN (:assetId)')->setParameter(
                'assetId',
                $criteria['assetId'],
            );
        }

        if (
            isset($criteria['investmentStatus'])
            && is_array($criteria['investmentStatus'])
        ) {
            $qb->andWhere(
                'istatus.lifecycleStatus IN (:investmentStatus)',
            )->setParameter('investmentStatus', $criteria['investmentStatus']);
        }
        $qb->orderBy('i.id', 'DESC');
        if (isset($offset)) {
            $qb->setFirstResult($offset);
        }
        if ($limit) {
            $qb->setMaxResults($limit);
        }
        $query = $qb->getQuery();
        return new Paginator($query, true);
    }

    // /**
    //  * Sometimes the respository does update the entity so putting in a method so a object entity can be refresh
    //  */
    // public function doRefresh($object)
    // {
    //     $this->getEntityManager()->refresh($object);
    // }

    /** @deprecated */
    public function getCapitalOutstanding($inv_id)
    {
        // Calculated field that returns the number of investors against ths offering
        // 0 = Pending, 1 = Paid, 2 = Cancelled or 3 = Failed. Defaults to 1 (Paid)
        //
        //        $conn = $this->getEntityManager()->getConnection();
        //
        //        $sql = 'select sum(t.transaction_amount) as sumpaid from  payouts p, transactions t where p.id = t.Payout_id';
        //        $sql = $sql . ' and payment_status =1 and p.investment_id = ?';
        //
        //        $stmt = $conn->prepare($sql);
        //        $stmt->bindValue(1, $inv_id);
        //        $stmt->execute();
        //
        //        /** @var array $result */
        //        $result = $stmt->fetchAll();
        //
        //        $paid_amount = $result[0]["sumpaid"];
        //
        //        $sql = 'select sum(payoutAmount) as totalAmount from  payouts where investment_id = ?';
        //        $stmt = $conn->prepare($sql);
        //        $stmt->bindValue(1, $inv_id);
        //        $stmt->execute();
        //
        //        /** @var array $result */
        //        $result = $stmt->fetchAll();
        //
        //        $totalAmount = $result[0]["totalAmount"];
        //
        //        return ($totalAmount - $paid_amount);
        //
        return 0;
    }

    public function getOfferedValues($inv_id)
    {
        // determine the total amount of shares offering and value for resell
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT SUM(fundingGoal) as totaloffered_amount, sum(equityOffered) as totaloffered_shares, count(off.id) as countofferings'
            . ' FROM offerings off, offerings_status off_st'
            . ' WHERE inv_id = '
            . $inv_id
            . ' AND off.offeringStatus_id = off_st.id '
            . ' AND off_st.lifecycleStatus !=\'draft\''
            . ' AND off_st.lifecycleStatus !=\'cancelled\'';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        $results['totaloffered_amount'] = $result[0]['totaloffered_amount'];
        $results['totaloffered_shares'] = $result[0]['totaloffered_shares'];
        $results['count_offerings'] = $result[0]['countofferings'];

        return $results;
    }

    /** @deprecated */
    public function getSettledAmount()
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'select sum(investmentValue) as investment_sum FROM investments inv, investments_status inv_st'
            . ' where  inv.investmentStatus_id = inv_st.id and inv_st.lifecycleStatus in (\'settled\')';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        $totalAmount = $result[0]['investment_sum'];

        return $totalAmount;
    }

    /***
     * determine that amount that has been divested from the original investment, ie has been offered
     * up to secondary market.  Only settled investments of secondary market should be considered as divested
     *
     * @param $inv_id
     * @param $offering_id
     * @return array
     */
    public function getDivestedValues($inv_id, $offering_id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql =
            'SELECT SUM(investmentValue) AS totaldivested_amount, SUM(share_amount) AS totaldivested_shares'
            . ' FROM investments inv, investments_status inv_st'
            . ' WHERE inv.off_id in (SELECT id FROM offerings WHERE inv_id = '
            . $inv_id
            . ')'
            . ' AND inv.investmentStatus_id = inv_st.id '
            . ' AND inv_st.lifecycleStatus =\'settled\'';

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery()->fetchAllAssociative();

        $results['totaldivested_amount'] = $result[0]['totaldivested_amount'];
        $results['totaldivested_shares'] = $result[0]['totaldivested_shares'];

        return $results;
    }

    public function getAsIdAndIdentifier(): \Traversable
    {
        $dbalConnection = $this->getEntityManager()->getConnection();
        $qb = $dbalConnection->createQueryBuilder();
        $qb
            ->select('id', 'name AS identifier')
            ->from('investments', 'inv')
            ->orderBy('id', 'DESC');

        $stmt = $qb->executeQuery();
        return $stmt->iterateAssociative();
    }

    /**
     * All Investments data for export
     */
    public function getInvestmentData(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select * from getInvestmentData order by id DESC';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Share register data for export
     */
    public function getShareRegister(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select * from getShareRegister order by id DESC';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    private function prepareRelationCriteria(array $criteria): array
    {
        $preparedCriteria = [];
        foreach ($criteria as $key => $value) {
            $preparedCriteria[$key] = [];
            if (is_array($value)) {
                foreach ($value as $element) {
                    if (is_int($element) || ctype_alnum($element)) {
                        $preparedCriteria[$key][] = $element;
                    }
                }
            }
            if (is_int($value) || ctype_alnum($value)) {
                $preparedCriteria[$key] = [$value];
            }
        }
        return $preparedCriteria;
    }

    public function queryInvestmentSummary(bool $settledOnly = true): ?array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'ast.id AS assetId',
                'SUM(inv.share_amount) AS shares',
                'ROUND(SUM(inv.share_amount * inv.orgPricePerShare), 2) AS value',
                'CASE WHEN ofr.inv_id IS NOT NULL THEN 1 ELSE 0 END AS isRelisted',
                'inv.type AS investmentType',
            )
            ->from('investments', 'inv')
            ->leftJoin(
                'inv',
                'investments_status',
                'invstat',
                'inv.investmentStatus_id = invstat.id',
            )
            ->leftJoin('inv', 'offerings ', 'ofr', 'inv.off_id = ofr.id')
            ->leftJoin('ofr', 'assets ', 'ast', 'ofr.asset_id = ast.id');

        if ($settledOnly) {
            $qb->where("invstat.lifecycleStatus IN ('settled')");
        } else {
            $qb->where("invstat.lifecycleStatus IN ('approved')");
        }

        $qb->groupBy('inv.type', 'isRelisted', 'ast.id')->orderBy('ast.id', 'ASC');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }
}
