<?php

namespace App\Repository;

use App\Entity\AssetDocuments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<AssetDocuments>
 *
 * @method AssetDocuments|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssetDocuments|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssetDocuments[]    findAll()
 * @method AssetDocuments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssetDocuments::class);
    }

    public function save(AssetDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AssetDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return AssetDocuments[] Returns an array of AssetDocuments objects
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

    //    public function findOneBySomeField($value): ?AssetDocuments
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByAssetId(
        int $assetId,
        int $page = 1,
        int $limit = 15,
    ): ?Pagerfanta {
        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('d')
            ->from(AssetDocuments::class, 'd')
            ->leftJoin('d.asset', 'asset')
            ->where('asset.id = :assetId')
            ->setParameter('assetId', $assetId);

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($limit);
        $pagerfanta->setCurrentPage($page);

        return $pagerfanta;
    }

    public function findByAssetIdAndDocId(
        int $assetId,
        int $assetDocId,
    ): ?AssetDocuments {
        return $this
            ->getEntityManager()
            ->getRepository(AssetDocuments::class)
            ->findOneBy(['asset' => $assetId, 'id' => $assetDocId]);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(AssetDocuments::class)
                ->getFieldNames(),
            ['documentTag', 'assetId', 'documentId'],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('assetdoc')
            ->from(AssetDocuments::class, 'assetdoc')
            ->leftJoin('assetdoc.asset', 'asset')
            ->leftJoin('assetdoc.document', 'document');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['assetId'])) {
                $field = lcfirst(substr($key, strlen('asset')));
                $qb->andWhere($qb->expr()->in('asset.' . $field, ':' . $key));
            } elseif (in_array($key, ['documentTag', 'documentId'])) {
                $field = lcfirst(substr($key, strlen('document')));
                $qb->andWhere($qb->expr()->in('document.' . $field, ':' . $key));
            } else {
                $qb->andWhere($qb->expr()->in('assetdoc.' . $key, ':' . $key));
            }

            $qb->setParameter($key, $value);
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('assetdoc.' . $key, $direction);
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
     * Criteria is applied to the Document, not the AssetDocument parent
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
            ->leftJoin('edocs.asset', 'entity')
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
