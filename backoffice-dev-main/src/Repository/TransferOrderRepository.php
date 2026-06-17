<?php

namespace App\Repository;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\TransferOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<TransferOrder>
 *
 * @method TransferOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method TransferOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method TransferOrder[]    findAll()
 * @method TransferOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransferOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransferOrder::class);
    }

    public function save(TransferOrder $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TransferOrder $entity, bool $flush = false): void
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
        $filtersAllowed = array_merge($this->getClassMetadata()->getFieldNames(), [
            'assetId',
            'assetName',
            'createdAt_gte',
            'createdAt_lt',
            'scheduledFor_gte',
            'scheduledFor_lt',
        ]);

        $qb = $this->createQueryBuilder('transferOrder')->leftJoin(
            'transferOrder.asset',
            'asset',
        );

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['assetId', 'assetName'])) {
                $field = lcfirst(substr($key, strlen('asset')));
                if (in_array($field, ['name'])) {
                    $qb->andWhere($qb->expr()->like('asset.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
                }
            } elseif (in_array($key, [
                'createdAt_gte',
                'createdAt_lt',
                'scheduledFor_gte',
                'scheduledFor_lt',
            ])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'transferOrder.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'transferOrder.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('transferOrder.' . $key, ':' . $key));
            }

            if (in_array($key, ['assetName'])) {
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
                $qb->addOrderBy('transferOrder.' . $key, $direction);
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

    public function findForCurrentMonthend(
        Asset $asset,
        TransferOrderPreset $preset,
    ): ?TransferOrder {
        return $this
            ->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'description' => $preset->value,
                'scheduledFor_gte' => new \DateTime('first day of this month')->setTime(
                    0,
                    0,
                ),
                'scheduledFor_lt' => new \DateTime('first day of next month')->setTime(
                    0,
                    0,
                ),
                'status' => [
                    AbstractOrder::STATE_DRAFT,
                    AbstractOrder::STATE_APPROVED,
                    AbstractOrder::STATE_IN_PROGRESS,
                    AbstractOrder::STATE_COMPLETED,
                ],
            ], ['id' => 'DESC'])
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
