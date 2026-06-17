<?php

namespace App\Repository;

use App\Entity\Communication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<Communication>
 *
 * @method Communication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Communication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Communication[]    findAll()
 * @method Communication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Communication::class);
    }

    public function save(Communication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Communication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return Communication[] Returns an array of Communication objects
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

    //    public function findOneBySomeField($value): ?Communication
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByUserAndContent(
        User $user,
        string $subject,
        int $status,
        string $content,
    ): ?array {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('c')
            ->from(Communication::class, 'c')
            ->where('c.user = :user')
            ->andWhere('c.subject = :subject')
            ->andWhere('c.status = :status')
            ->andWhere('REGEXP(c.content, :content) = true');

        $qb
            ->setParameter('user', $user)
            ->setParameter('subject', $subject)
            ->setParameter('status', $status)
            ->setParameter('content', '(^|[[:space:]])' . $content . '([[:space:]]|$)');

        return $qb->getQuery()->getResult();
    }

    public function findInvestmentSettledEmails(int $status = 1): ?array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('c')
            ->from(Communication::class, 'c')
            ->where("c.subject = 'Investment Settled'")
            ->andWhere('c.status = :status')
            ->setParameter('status', $status);

        return $qb->getQuery()->getResult();
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(Communication::class)
                ->getFieldNames(),
            [
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('communication')
            ->from(Communication::class, 'communication');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['subject'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('communication.' . $key, ':' . $key));
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'communication.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'communication.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('communication.' . $key, ':' . $key));
            }

            if (in_array($key, ['subject'])) {
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
                $qb->addOrderBy('communication.' . $key, $direction);
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

    public function sizeBySubject(
        ?\DateTimeInterface $endDate = null,
        bool $groupByYear = false,
    ): array {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->select('c.subject')
            ->addSelect('count(c.id) AS count')
            ->from(Communication::class, 'c')
            ->addGroupBy('c.subject')
            ->addOrderBy('count', 'DESC');

        if (!is_null($endDate)) {
            $qb->andWhere($qb->expr()->lt('c.createdAt', ':toDate'))->setParameter(
                'toDate',
                $endDate->format('Y-m-d'),
            );
        }
        if ($groupByYear) {
            $qb->addSelect('YEAR(c.createdAt) AS year')->addGroupBy('year');
        }
        return $qb->getQuery()->getResult();
    }

    public function deleteBySubject(
        string $subject,
        ?\DateTimeInterface $endDate = null,
    ): int {
        $endDate = $this->getSafeEndDate($endDate);
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb
            ->delete(Communication::class, 'communication')
            ->andWhere($qb->expr()->eq('communication.subject', ':subject'))
            ->andWhere($qb->expr()->lt('communication.createdAt', ':toDate'))
            ->setParameter('subject', $subject)
            ->setParameter('toDate', $endDate->format('Y-m-d'));
        return $qb->getQuery()->execute();
    }

    public function getSafeEndDate(?\DateTimeInterface $endDate): \DateTimeInterface
    {
        $safeDate = new \DateTime('-1 month');
        if (is_null($endDate)) {
            $endDate = $safeDate;
        }
        return min($safeDate, $endDate);
    }
}
