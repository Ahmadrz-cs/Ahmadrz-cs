<?php

namespace App\Repository;

use App\Entity\InvestmentDocuments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<InvestmentDocuments>
 *
 * @method InvestmentDocuments|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvestmentDocuments|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvestmentDocuments[]    findAll()
 * @method InvestmentDocuments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvestmentDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvestmentDocuments::class);
    }

    public function save(InvestmentDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InvestmentDocuments $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return InvestmentDocuments[] Returns an array of InvestmentDocuments objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?InvestmentDocuments
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findByInvestmentIdAndDocId(
        int $investmentId,
        int $investmentDocId,
    ): ?InvestmentDocuments {
        return $this->findOneBy([
            'investment' => $investmentId,
            'id' => $investmentDocId,
        ]);
    }

    public function findByInvestmentId(int $investmentId): array
    {
        return $this->findBy(['investment' => $investmentId]);
    }

    public function findByInvestmentIdAndDocumentId(
        int $invId,
        int $docId,
    ): ?InvestmentDocuments {
        return $this->findOneBy(['investment' => $invId, 'id' => $docId]);
    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(InvestmentDocuments::class)
                ->getFieldNames(),
            ['documentTag', 'investmentId', 'userId'],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('investmentdoc')
            ->from(InvestmentDocuments::class, 'investmentdoc')
            ->leftJoin('investmentdoc.investment', 'investment')
            ->leftJoin('investment.user', 'user')
            ->leftJoin('investmentdoc.document', 'document');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['investmentId'])) {
                $field = lcfirst(substr($key, strlen('investment')));
                $qb->andWhere($qb->expr()->in('investment.' . $field, ':' . $key));
            } elseif (in_array($key, ['userId'])) {
                $field = lcfirst(substr($key, strlen('user')));
                $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
            } elseif (in_array($key, ['documentTag'])) {
                $field = lcfirst(substr($key, strlen('document')));
                $qb->andWhere($qb->expr()->in('document.' . $field, ':' . $key));
            } else {
                $qb->andWhere($qb->expr()->in('investmentdoc.' . $key, ':' . $key));
            }

            $qb->setParameter($key, $value);
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('investmentdoc.' . $key, $direction);
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
     * Criteria is applied to the Document, not the InvestmentDocument parent
     */
    public function getDocumentInfoWithDocCriteria(
        $criteria = [],
        $includeContent = true,
        $limit = -1,
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
            ->leftJoin('edocs.investment', 'entity')
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
