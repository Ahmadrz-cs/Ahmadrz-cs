<?php

namespace App\Repository;

use App\Entity\KycReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<KycReport>
 *
 * @method KycReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method KycReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method KycReport[]    findAll()
 * @method KycReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KycReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KycReport::class);
    }

    public function save(KycReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(KycReport $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return KycReport[] Returns an array of KycReport objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('k.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?KycReport
    //    {
    //        return $this->createQueryBuilder('k')
    //            ->andWhere('k.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge($this->getClassMetadata()->getFieldNames(), [
            'subjectId',
            'subjectUsername',
            'subjectOb_step',
            'createdAt_gte',
            'createdAt_lt',
            'checkedAt_gte',
            'checkedAt_lt',
        ]);

        $qb = $this->createQueryBuilder('kycReport')->leftJoin(
            'kycReport.subject',
            'user',
        );

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['subjectId', 'subjectUsername', 'subjectOb_step'])) {
                $field = lcfirst(substr($key, strlen('subject')));
                if (in_array($field, ['username'])) {
                    $qb->andWhere($qb->expr()->like('user.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
                }
            } elseif (in_array($key, [
                'createdAt_gte',
                'createdAt_lt',
                'checkedAt_gte',
                'checkedAt_lt',
            ])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'kycReport.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'kycReport.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('kycReport.' . $key, ':' . $key));
            }

            if (in_array($key, ['subjectUsername'])) {
                // note that the preceding % wildcard has a high performance cost
                $qb->setParameter($key, '%' . addcslashes($value, '%_') . '%');
            } elseif ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                // Time is also needed for Mangopay webhook filtering
                $qb->setParameter($key, $value->format('Y-m-d H:i:s'));
            } else {
                $qb->setParameter($key, $value);
            }
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('kycReport.' . $key, $direction);
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
}
