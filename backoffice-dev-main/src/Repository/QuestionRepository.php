<?php

namespace App\Repository;

use App\Entity\Enum\QuestionType;
use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    // /**
    //  * @return Question[] Returns an array of Question objects
    //  */
    // public function findByExampleField($value): array
    // {
    //     return $this->createQueryBuilder('q')
    //         ->andWhere('q.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->orderBy('q.id', 'ASC')
    //         ->setMaxResults(10)
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }

    // public function findOneBySomeField($value): ?Question
    // {
    //     return $this->createQueryBuilder('q')
    //         ->andWhere('q.exampleField = :val')
    //         ->setParameter('val', $value)
    //         ->getQuery()
    //         ->getOneOrNullResult()
    //     ;
    // }

    /**
     * Join and select choices and responses
     * Prevents N+1 query problem if accessing both relations via Question
     *
     * TODO - genericise criteria
     *
     * @param string[] $areas
     * @return Question[]
     */
    public function findAllQuestionsByArea(
        QuestionType $questionType,
        array $areas,
        ?bool $activeOnly = null,
    ): array {
        $qb = $this
            ->createQueryBuilder('q')
            ->leftJoin('q.choices', 'choices')
            ->addSelect('choices')
            ->leftJoin('q.responses', 'responses')
            ->addSelect('responses')
            ->andWhere('q.questionType = :qtype')
            ->setParameter('qtype', $questionType)
            ->andWhere('q.section IN (:areas)')
            ->setParameter('areas', $areas)
            ->orderBy('q.id', 'ASC');
        if (!is_null($activeOnly)) {
            $qb->andWhere('q.active = :isActive')->setParameter(
                'isActive',
                $activeOnly,
            );
        }
        return $qb->getQuery()->getResult();
    }
}
