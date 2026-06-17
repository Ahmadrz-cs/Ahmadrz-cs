<?php

namespace App\Repository;

use App\Entity\UserDocument;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

/**
 * @extends ServiceEntityRepository<UserDocument>
 *
 * @method UserDocument|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserDocument|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserDocument[]    findAll()
 * @method UserDocument[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserDocument::class);
    }

    public function save(UserDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserDocument $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    //    /**
    //     * @return UserDocument[] Returns an array of UserDocument objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?UserDocument
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function buildQueryWithAssociations(
        array $filters,
        array $orderBy = [],
    ): Query {
        $filtersAllowed = array_merge(
            $this
                ->getEntityManager()
                ->getClassMetadata(UserDocument::class)
                ->getFieldNames(),
            ['documentTag', 'userId'],
        );

        $qb = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('userdoc')
            ->from(UserDocument::class, 'userdoc')
            ->leftJoin('userdoc.user', 'user')
            ->leftJoin('userdoc.document', 'document');

        foreach ($filters as $key => $value) {
            if (
                is_null($value)
                || is_array($value) && empty($value)
                || !in_array($key, $filtersAllowed)
            ) {
                continue; // skip if filter unsupported invalid
            }

            if (in_array($key, ['userId'])) {
                $field = lcfirst(substr($key, strlen('user')));
                $qb->andWhere($qb->expr()->in('user.' . $field, ':' . $key));
            } elseif (in_array($key, ['documentTag'])) {
                $field = lcfirst(substr($key, strlen('document')));
                $qb->andWhere($qb->expr()->in('document.' . $field, ':' . $key));
            } else {
                $qb->andWhere($qb->expr()->in('userdoc.' . $key, ':' . $key));
            }

            $qb->setParameter($key, $value);
        }

        foreach ($orderBy as $key => $direction) {
            if (in_array($key, $filtersAllowed)) {
                $qb->addOrderBy('userdoc.' . $key, $direction);
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

    public function getUserDocumentInfo(): array
    {
        // $conn = $this->getEntityManager()->getConnection();

        // $sql = '
        //     SELECT
        //         usrdocs.id,
        //         usr.username,
        //         docs.filename,
        //         docs.description,
        //         docs.tag
        //     FROM user_docs usrdocs
        //     LEFT JOIN users usr on usrdocs.user_id = usr.id
        //     LEFT JOIN documents docs on usrdocs.document_id = docs.id
        //     ';

        // $stmt = $conn->prepare($sql);
        // $stmt->execute();

        // /** @var array $result */
        // $result =$stmt->fetchAll();

        // return $result;

        return $this
            ->createQueryBuilder('edocs')
            ->select(
                'edocs, entity, partial docs.{id, filename, description, tag, documentUrl}',
            )
            ->leftJoin('edocs.user', 'entity')
            ->leftJoin('edocs.document', 'docs')
            ->orderBy('edocs.id', 'DESC')
            ->getQuery()
            ->execute();
    }

    /**
     * Criteria is applied to the Document, not the UserDocument parent
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
            ->leftJoin('edocs.user', 'entity')
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
