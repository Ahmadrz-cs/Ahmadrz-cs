<?php

namespace App\Repository;

use App\Entity\DirectDebit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DirectDebit>
 *
 * @method DirectDebit|null find($id, $lockMode = null, $lockVersion = null)
 * @method DirectDebit|null findOneBy(array $criteria, array $orderBy = null)
 * @method DirectDebit[]    findAll()
 * @method DirectDebit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DirectDebitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DirectDebit::class);
    }

    public function save(DirectDebit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DirectDebit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return DirectDebit[] Returns an array of DirectDebit objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DirectDebit
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getDueDirectDebits()
    {
        $cutOffDate = \DateTime::createFromFormat('d', '15');
        $lastMonthDate = new \Datetime('last month');

        $qb = $this
            ->createQueryBuilder('p')
            ->where('p.createDate <= :cutOffDate')
            ->andWhere(
                'MONTH(p.lastSettlementDate) = :month OR p.lastSettlementDate IS NULL',
            )
            ->andWhere('p.directDebitActive = true')
            ->setParameter('cutOffDate', $cutOffDate)
            ->setParameter('month', $lastMonthDate->format('m'));

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $results;
    }

    public function getSettledDirectDebits()
    {
        $time = new \DateTime();
        $qb = $this
            ->createQueryBuilder('p')
            ->where('MONTH(p.lastSettlementDate) = :month')
            ->setParameter('month', $time->format('m'));

        $query = $qb->getQuery();
        $results = $query->getResult();

        return $results;
    }
}
