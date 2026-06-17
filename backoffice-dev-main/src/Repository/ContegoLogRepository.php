<?php

namespace App\Repository;

use App\Entity\ContegoLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContegoLog>
 *
 * @method ContegoLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContegoLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContegoLog[]    findAll()
 * @method ContegoLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContegoLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContegoLog::class);
    }

    public function save(ContegoLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ContegoLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return ContegoLog[] Returns an array of ContegoLog objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ContegoLog
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Contego Log Data
     */
    public function getContegoLogData(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'select * from getContegoLog order by id DESC';
        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }
}
