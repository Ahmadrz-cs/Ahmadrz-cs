<?php

namespace App\Form\DataTransformer;

use App\Entity\Offering;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class OfferingToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a offering object into a string representing the offering id
     * @param  Offering|null $offering
     */
    public function transform($offering): ?string
    {
        if (null === $offering) {
            return null;
        }

        return (string) $offering->getId();
    }

    /**
     * @param  string $offeringId
     * @return Offering|null
     * @throws TransformationFailedException if object (offering) is not found.
     */
    public function reverseTransform($offeringId): ?Offering
    {
        if (!$offeringId) {
            return null;
        }

        $offering = $this->entityManager
            ->getRepository(Offering::class)
            ->find($offeringId);

        if (null === $offering) {
            throw new TransformationFailedException(sprintf(
                'An offering with id "%s" does not exist!',
                $offeringId,
            ));
        }
        return $offering;
    }
}
