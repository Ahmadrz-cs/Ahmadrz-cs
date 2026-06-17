<?php

namespace App\Form\DataTransformer;

use App\Entity\Investment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class InvestmentToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a investment object into a string representing the investment id
     * @param  Investment|null $investment
     */
    public function transform($investment): ?string
    {
        if (null === $investment) {
            return null;
        }

        return (string) $investment->getId();
    }

    /**
     * @param  string $investmentId
     * @return Investment|null
     * @throws TransformationFailedException if object (investment) is not found.
     */
    public function reverseTransform($investmentId): ?Investment
    {
        if (!$investmentId) {
            return null;
        }

        $investment = $this->entityManager
            ->getRepository(Investment::class)
            ->find($investmentId);

        if (null === $investment) {
            throw new TransformationFailedException(sprintf(
                'A investment with id "%s" does not exist!',
                $investmentId,
            ));
        }

        return $investment;
    }
}
