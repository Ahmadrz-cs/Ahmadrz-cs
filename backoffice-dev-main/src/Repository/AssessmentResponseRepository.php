<?php

namespace App\Repository;

use App\Entity\AssessmentResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssessmentResponse>
 */
class AssessmentResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssessmentResponse::class);
    }

    //    /**
    //     * @return AssessmentResponse[] Returns an array of AssessmentResponse objects
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

    //    public function findOneBySomeField($value): ?AssessmentResponse
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Dedicated query to avoid N+1 query problem if accessing profile and user
     */
    public function findAllWithJoins(): Query
    {
        $qb = $this
            ->createQueryBuilder('ar')
            ->leftJoin('ar.assessment', 'assessment')
            ->addSelect('assessment')
            ->leftJoin('ar.question', 'question')
            ->addSelect('question')
            ->leftJoin('ar.choice', 'choice')
            ->addSelect('choice')
            ->orderBy('ar.id', 'ASC');
        return $qb->getQuery();
    }
}
