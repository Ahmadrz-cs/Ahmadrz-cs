<?php

namespace App\Repository;

use App\Entity\OfferingDocuments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<OfferingDocuments>
 *
 * @method OfferingDocuments|null find($id, $lockMode = null, $lockVersion = null)
 * @method OfferingDocuments|null findOneBy(array $criteria, array $orderBy = null)
 * @method OfferingDocuments[]    findAll()
 * @method OfferingDocuments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OfferingDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OfferingDocuments::class);
    }

    public function save(OfferingDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OfferingDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return OfferingDocuments[] Returns an array of OfferingDocuments objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?OfferingDocuments
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByOfferingId(int $offeringId): array
    {
        return $this->findBy(['offering' => $offeringId]);
    }

    public function getDocumentByOfferingIdAndDocumentId(
        int $offeringId,
        int $offeringDocId,
    ): ?OfferingDocuments {
        return $this->findOneBy(['offering' => $offeringId, 'id' => $offeringDocId]);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(OfferingDocuments::class)
                ->getFieldNames(),
            [
                'documentTag',
                'offeringId',
                'offeringIsSecondaryMrkt',
                'hasCreatedById',
                'documentId',
                'hasTag',
                'hasDocumentUrl',
                'hasSell_investment',
            ],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('offeringdoc')
            ->from(OfferingDocuments::class, 'offeringdoc')
            ->leftJoin('offeringdoc.offering', 'offering')
            ->leftJoin('offeringdoc.document', 'document');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['offeringId', 'offeringIsSecondaryMrkt'])) {
                $field = lcfirst(substr($key, strlen('offering')));
                $qb->andWhere($qb->expr()->in('offering.' . $field, ':' . $key));
            } elseif (in_array($key, ['documentTag', 'documentId'])) {
                $field = lcfirst(substr($key, strlen('document')));
                $qb->andWhere($qb->expr()->in('document.' . $field, ':' . $key));
            } elseif (in_array($key, ['hasSell_investment'])) {
                $field = lcfirst(substr($key, strlen('has')));
                // null checks
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('offering.' . $field));
                } else {
                    $qb->andWhere($qb->expr()->isNull('offering.' . $field));
                }
                continue;
            } elseif (in_array($key, ['hasDocumentUrl', 'hasTag'])) {
                $field = lcfirst(substr($key, strlen('has')));
                // null checks
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('document.' . $field));
                } else {
                    $qb->andWhere($qb->expr()->isNull('document.' . $field));
                }
                continue;
            } elseif (in_array($key, ['hasCreatedById'])) {
                $field = lcfirst(substr($key, strlen('has')));
                // null checks
                if ($value) {
                    $qb->andWhere($qb->expr()->isNotNull('offeringdoc.' . $field));
                } else {
                    $qb->andWhere($qb->expr()->isNull('offeringdoc.' . $field));
                }
                continue;
            } else {
                $qb->andWhere($qb->expr()->in('offeringdoc.' . $key, ':' . $key));
            }

            $qb->setParameter($key, $value);
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('offeringdoc.' . $key, $direction);
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
     * @deprecated
     */
    public function getOfferingAssetNames(): array
    {
        // Return array of the asset names related to each offering
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT offerings.name as OfferingName, assets.name as AssetName
        FROM assets
        inner join offerings
        ON assets.id = offerings.asset_id';

        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Criteria is applied to the Document, not the OfferingDocument parent
     */
    public function getDocumentInfoWithDocCriteria(
        array $criteria = [],
        bool $includeContent = true,
        int $limit = -1,
    ): array {
        $qb = $this->createQueryBuilder('edocs');

        if ($includeContent) {
            $qb->select('edocs, entity, docs');
        } else {
            $qb->select(
                'edocs, entity, partial docs.{ id, filename, description, tag, documentUrl }',
            );
        }

        $qb
            ->leftJoin('edocs.offering', 'entity')
            ->leftJoin('edocs.document', 'docs')
            ->orderBy('edocs.id', 'DESC');
        if (!empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $condition = 'docs.' . $field . ' ' . $value;
                $qb->andWhere($condition);
            }
        }
        if ($limit > 0) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->execute();
    }
}
