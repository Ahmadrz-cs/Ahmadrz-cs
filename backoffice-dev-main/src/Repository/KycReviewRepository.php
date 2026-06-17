<?php

namespace App\Repository;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Entity\User;
use App\Service\KycReviewService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<KycReview>
 *
 * @method KycReview|null find($id, $lockMode = null, $lockVersion = null)
 * @method KycReview|null findOneBy(array $criteria, array $orderBy = null)
 * @method KycReview[]    findAll()
 * @method KycReview[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class KycReviewRepository extends ServiceEntityRepository
{
    public function __construct(
        private LoggerInterface $logger,
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, KycReview::class);
    }

    public function save(KycReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(KycReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return KycReview[] Returns an array of KycReview objects
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

    //    public function findOneBySomeField($value): ?KycReview
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
            'reviewedByType',
            'createdAt_gte',
            'createdAt_lt',
            'completedAt_gte',
            'completedAt_lt',
        ]);

        $qb = $this->createQueryBuilder('kycReview')->leftJoin(
            'kycReview.subject',
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

            if (in_array($key, ['subjectId', 'subjectUsername'])) {
                $field = lcfirst(substr($key, strlen('subject')));
                if (in_array($field, ['username'])) {
                    $qb->andWhere($qb->expr()->like('user.' . $field, ':' . $key));
                } else {
                    $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
                }
            } elseif (in_array($key, ['reviewedByType'])) {
                // reviewedByType is a nullable boolean, null is ignored
                // true == has a human reviewer
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('kycReview.reviewedBy'));
                } else {
                    $qb->andWhere($qb->expr()->isNull('kycReview.reviewedBy'));
                }
                // Don't bind any parameters, so continue to next filter
                continue;
            } elseif (in_array($key, [
                'createdAt_gte',
                'createdAt_lt',
                'completedAt_gte',
                'completedAt_lt',
            ])) {
                // range filters
                $criteriaParts = explode('_', $key);
                if (2 === count($criteriaParts)) {
                    if ('gte' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->gte(
                            'kycReview.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                    if ('lt' === $criteriaParts[1]) {
                        $qb->andWhere($qb->expr()->lt(
                            'kycReview.' . $criteriaParts[0],
                            ':' . $key,
                        ));
                    }
                }
            } else {
                $qb->andWhere($qb->expr()->in('kycReview.' . $key, ':' . $key));
            }

            if (in_array($key, ['subjectUsername'])) {
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
                $qb->addOrderBy('kycReview.' . $key, $direction);
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
     * @param string[] $actions
     * @return KycReview[]
     */
    public function findOpenReviews(
        User $user,
        KycReviewType $reviewType,
        array $actions,
    ): array {
        $filters = [
            'subjectId' => $user->getId(),
            'reviewType' => $reviewType,
            'status' => KycReviewStatus::editableCases(),
        ];
        $validActions = array_intersect(
            $actions,
            KycReviewService::CONFIGURABLE_ACTIONS,
        );
        $otherActions = array_diff(
            KycReviewService::CONFIGURABLE_ACTIONS,
            $validActions,
        );
        foreach (KycReviewService::CONFIGURABLE_ACTIONS as $action) {
            if (\in_array($action, $otherActions)) {
                $filters[$action] = false;
            } else {
                $filters[$action] = true;
            }
        }
        // $this->logger->debug('KycReview filters', $filters);
        $matches = $this->buildQueryWithAssociations($filters)->getResult();
        // $this->logger->debug('Matching kyc reviews: ' . count($matches));
        return $matches ?? [];
    }
}
