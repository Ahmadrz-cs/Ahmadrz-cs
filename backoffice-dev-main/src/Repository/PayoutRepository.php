<?php

namespace App\Repository;

use App\Entity\Payout;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<Payout>
 *
 * @method Payout|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payout|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payout[]    findAll()
 * @method Payout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PayoutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payout::class);
    }

    public function save(Payout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Payout $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return Payout[] Returns an array of Payout objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Payout
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findPayoutByAssetAndDate(
        int $assetId,
        \DateTime $payoutDate,
        int $type,
    ) {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Payout::class, 'p')
            ->leftJoin('p.investment', 'investment')
            ->leftJoin('investment.offering', 'offering')
            ->andWhere('p.asset = :assetId OR offering.asset = :assetId')
            ->andWhere('DAY(p.dueDate) = :day')
            ->andWhere('MONTH(p.dueDate) = :month')
            ->andWhere('YEAR(p.dueDate) = :year')
            ->andWhere('p.payoutType = :type');

        $qb
            ->setParameter('day', $payoutDate->format('d'))
            ->setParameter('month', $payoutDate->format('m'))
            ->setParameter('year', $payoutDate->format('Y'))
            ->setParameter('assetId', $assetId)
            ->setParameter('type', $type);

        return $qb->getQuery()->getResult();
    }

    public function findByAssetIdAndCreditedUser(
        int $assetId,
        User $creditedUser,
        ?int $type = null,
        ?\DateTime $dueDate = null,
        float $amount = 0,
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('p')
            ->from(Payout::class, 'p')
            ->leftJoin('p.asset', 'a')
            ->where('a.id = :assetId')
            ->andWhere('p.creditedUser = :user')
            ->setParameter('assetId', $assetId)
            ->setParameter('user', $creditedUser);

        if ($dueDate) {
            $qb
                ->andWhere('DAY(p.dueDate) = :day')
                ->andWhere('MONTH(p.dueDate) = :month')
                ->andWhere('YEAR(p.dueDate) = :year')
                ->setParameter('day', $dueDate->format('d'))
                ->setParameter('month', $dueDate->format('m'))
                ->setParameter('year', $dueDate->format('Y'));
        }

        if ($type) {
            $qb->andWhere('p.payoutType = :type')->setParameter('type', $type);
        }

        if ($amount > 0) {
            $qb->andWhere('p.payoutAmount = :amount')->setParameter('amount', $amount);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByInvestmentIdOrCreditedUser(
        $page,
        $limit,
        array $investmentIdArray,
        User $creditedUser,
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Payout::class, 'p')
            ->leftJoin('p.investment', 'inv');
        if (!empty($investmentIdArray)) {
            $qb->where('inv.id IN (:investmentIdArray) OR p.creditedUser = :user');
            $qb->setParameter('investmentIdArray', $investmentIdArray);
        } else {
            $qb->where('p.creditedUser = :user');
        }
        $qb->setParameter('user', $creditedUser);

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findAllPagerfanta($page, $limit, array $idArray = []): ?Pagerfanta
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Payout::class, 'p');

        if (!empty($idArray)) {
            $qb->andWhere($qb->expr()->in('p.id', $idArray));
        }

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findByUser(User $user): ?array
    {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('p')
            ->from(Payout::class, 'p')
            ->leftJoin('p.investment', 'i')
            ->andWhere('p.creditedUser = :user OR i.user = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }

    public function findAggregatedPayoutAmounts(
        ?User $user = null,
        array $typeFilter = [],
    ): ?string {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->from(Payout::class, 'p')
            ->select('SUM(p.payoutAmount) AS totalAmount');

        if (!empty($typeFilter)) {
            $qb->andWhere($qb->expr()->in('p.payoutType', $typeFilter));
        }

        if (!empty($user)) {
            $qb->leftJoin('p.investment', 'i');
            $qb->andWhere('p.creditedUser = :user OR i.user = :user');
            $qb->setParameter('user', $user);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findUserPayoutsInDateRange(
        int $userId,
        int $payoutType,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('p')
            ->from(Payout::class, 'p')
            ->andWhere('p.payoutType = :payoutType')
            ->andWhere('p.creditedUser = :user')
            ->andWhere($qb->expr()->gte('p.dueDate', ':fromDate'))
            ->andWhere($qb->expr()->lt('p.dueDate', ':toDate'))
            ->setParameter('payoutType', $payoutType)
            ->setParameter('user', $userId)
            ->setParameter('fromDate', $startDate)
            ->setParameter('toDate', $endDate);

        return $qb->getQuery()->getResult();
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this->getEntityManager()->getClassMetadata(Payout::class)->getFieldNames(),
            [
                'assetId',
                'assetName',
                'userId',
                'investmentId',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('payout', 'asset', 'user')
            ->from(Payout::class, 'payout')
            ->leftJoin('payout.asset', 'asset')
            ->leftJoin('payout.creditedUser', 'user')
            ->leftJoin('payout.investment', 'investment')
            ->leftJoin('investment.user', 'investmentUser')
            ->leftJoin('investment.offering', 'offering')
            ->leftJoin('offering.asset', 'offeringAsset');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['assetId', 'assetName'])) {
                // asset related
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['name'])) {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->like('asset.' . $field, ':' . $key),
                        $qb->expr()->like('offeringAsset.' . $field, ':' . $key),
                    ));
                } else {
                    $qb->andWhere($qb->expr()->orX(
                        $qb->expr()->in('asset.' . $field, ':' . $key),
                        $qb->expr()->in('offeringAsset.' . $field, ':' . $key),
                    ));
                }
            } elseif (in_array($key, ['userId'])) {
                // user related
                $field = lcfirst(substr($key, strlen('user')));
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->in('user.' . $field, ':' . $key),
                    $qb->expr()->in('investmentUser.' . $field, ':' . $key),
                ));
            } elseif (in_array($key, ['investmentId'])) {
                // investment related
                $field = lcfirst(substr($key, strlen('investment')));
                $qb->andWhere($qb->expr()->in('investment.' . $field, ':' . $key));
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'payout.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'payout.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('payout.' . $key, ':' . $key));
            }

            if (in_array($key, ['name', 'assetName'])) {
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
                $qb->addOrderBy('payout.' . $key, $direction);
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
     * @return Payout[]
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

    public function getDividendSummaryByAsset(
        ?int $userId = null,
        bool $includeTemporalAggregates = true,
    ): array {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();

        $qb
            ->select(
                'ast.id AS assetId',
                'ast.name AS assetName',
                'ast.companyNumber AS assetSpvId',
                'ast.fundingGoal AS assetValue',
                'SUM(pyt.payoutAmount) AS dividendsTotal',
                'MAX(pyt.createdAt) AS lastDividendPaidAt',
                'MIN(pyt.createdAt) AS firstDividendPaidAt',
            )
            ->from('payouts', 'pyt')
            ->leftJoin('pyt', 'assets', 'ast', 'pyt.asset_id = ast.id')
            ->where('pyt.asset_id IS NOT NULL')
            ->andWhere('pyt.credited_user_id IS NOT NULL')
            ->andWhere('pyt.payoutType = 0')
            ->groupBy('ast.id');
        if ($includeTemporalAggregates) {
            // Note that sqlite used in tests does not support YEAR, MONTH, NOW, IF
            // Sqlite has CASE-WHEN to replace IF and CURRENT_TIMESTAMP to replace NOW() that also works with MySQL
            // But there's no YEAR or MONTH equivalent compatible with both
            $qb->addSelect(
                'SUM(IF(YEAR(pyt.createdAt) = YEAR(NOW())AND MONTH(pyt.createdAt) = MONTH(NOW()),pyt.payoutAmount,0)) AS dividendsThisMonth',
                'COUNT(DISTINCT(CONCAT(YEAR(pyt.createdAt), MONTH(pyt.createdAt)))) AS paymentPeriods',
            );
        }
        if (!is_null($userId)) {
            $qb->andWhere('pyt.credited_user_id = :user')->setParameter(
                'user',
                $userId,
            );
        }

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function findUserPayoutsCountInDateRange(
        int $payoutType,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate,
    ): array {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select(
                'pyt.credited_user_id AS user',
                'COUNT(pyt.id) AS count',
                'ROUND(SUM(pyt.payoutAmount), 2) AS value',
            )
            ->from('payouts', 'pyt')
            ->andWhere('pyt.payoutType = :payoutType')
            ->andWhere($qb->expr()->gte('pyt.dueDate', ':fromDate'))
            ->andWhere($qb->expr()->lt('pyt.dueDate', ':toDate'))
            ->setParameter('payoutType', $payoutType)
            ->setParameter('fromDate', $startDate->format('Y-m-d'))
            ->setParameter('toDate', $endDate->format('Y-m-d'))
            ->addgroupBy('pyt.credited_user_id')
            ->addOrderBy('count', 'DESC');
        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    public function getInvestmentPayoutsData(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'select * from getInvestmentPayouts order by investment_id DESC';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
