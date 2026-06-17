<?php

namespace App\Repository;

use App\Entity\BankAccount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<BankAccount>
 *
 * @method BankAccount|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankAccount|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankAccount[]    findAll()
 * @method BankAccount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankAccountRepository extends ServiceEntityRepository
{
    public function __construct(
        private LoggerInterface $logger,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, BankAccount::class);
    }

    public function save(BankAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BankAccount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(BankAccount::class)
                ->getFieldNames(),
            [
                'userId',
                'username',
                'createdAt_gte',
                'createdAt_lt',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('bankAccount')
            ->from(BankAccount::class, 'bankAccount')
            ->leftJoin('bankAccount.user', 'user');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['userId'])) {
                // using strlen instead of plain integer for human readability
                $field = lcfirst(substr($key, strlen('user')));
                $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
            } elseif (in_array($key, ['username'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('user.' . $key, ':' . $key));
            } elseif (in_array($key, ['providerId', 'displayName'])) {
                // loose string match
                $qb->andWhere($qb->expr()->like('bankAccount.' . $key, ':' . $key));
            } elseif (in_array($key, ['createdAt_gte', 'createdAt_lt'])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'bankAccount.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'bankAccount.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('bankAccount.' . $key, ':' . $key));
            }

            if (in_array($key, ['username', 'providerId', 'displayName'])) {
                // note that the preceding % wildcard has a high performance cost
                $qb->setParameter($key, '%' . addcslashes($value, '%_') . '%');
            } elseif (in_array($key, ['uuid'])) {
                // Attempt to convert to binary for database comparison
                $value = Uuid::isValid($value)
                    ? Uuid::fromString($value)->toBinary()
                    : $value;
                $qb->setParameter($key, $value);
            } elseif ($value instanceof \DateTimeInterface) {
                // Datetimes should be formatted to ISO date
                $qb->setParameter($key, $value->format('Y-m-d'));
            } else {
                $qb->setParameter($key, $value);
            }
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('bankAccount.' . $key, $direction);
            }
        }
        // $this->logger->warning("parameters: ", [$qb->getParameter('providerId')?->getValue()]);
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
