<?php

namespace App\Form\DataTransformer;

use App\Entity\ShareTrade;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ShareTradeToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a ShareTrade object into a string representing the ShareTrade id
     * @param  ShareTrade|null $shareTrade
     */
    public function transform($shareTrade): ?string
    {
        if (null === $shareTrade) {
            return null;
        }

        return (string) $shareTrade->getId();
    }

    /**
     * @param  string $shareTradeId
     * @return ShareTrade|null
     * @throws TransformationFailedException if object (ShareTrade) is not found.
     */
    public function reverseTransform($shareTradeId): ?ShareTrade
    {
        if (!$shareTradeId) {
            return null;
        }

        $shareTrade = $this->entityManager
            ->getRepository(ShareTrade::class)
            ->find($shareTradeId);

        if (null === $shareTrade) {
            throw new TransformationFailedException(sprintf(
                'A ShareTrade with id "%s" does not exist!',
                $shareTradeId,
            ));
        }

        return $shareTrade;
    }
}
