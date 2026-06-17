<?php

namespace App\Repository;

use App\Entity\UserCategorisation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<UserCategorisation>
 */
class UserCategorisationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCategorisation::class);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge($this->getClassMetadata()->getFieldNames(), [
            'createdAt_gte',
            'createdAt_lt',
        ]);

        $qb = $this->createQueryBuilder('userCategorisation');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'userCategorisation.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'userCategorisation.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in(
                    'userCategorisation.' . $key,
                    ':' . $key,
                ));
            }

            if ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                $qb->setParameter($key, $value->format('Y-m-d'));
            } else {
                $qb->setParameter($key, $value);
            }
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('userCategorisation.' . $key, $direction);
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

    public function getCountByCategory(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $conn->createQueryBuilder();
        $tableName = $this
            ->getEntityManager()
            ->getClassMetadata(UserCategorisation::class)
            ->getTableName();

        $qb
            ->select('category', 'COUNT(uc.id) AS count')
            ->from($tableName, 'uc')
            ->groupBy('category');

        $stmt = $qb->executeQuery();
        return $stmt->fetchAllAssociative();
    }

    /**
     * Dedicated query to avoid N+1 query problem if accessing profile and user
     */
    public function findAllWithJoins(): Query
    {
        $qb = $this
            ->createQueryBuilder('uc')
            ->leftJoin('uc.profile', 'profile')
            ->addSelect('profile')
            ->leftJoin('profile.user', 'user')
            ->addSelect('user')
            ->orderBy('uc.id', 'ASC');
        return $qb->getQuery();
    }
}
