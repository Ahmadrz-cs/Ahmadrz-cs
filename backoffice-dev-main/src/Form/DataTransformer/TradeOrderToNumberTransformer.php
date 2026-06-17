<?php

namespace App\Form\DataTransformer;

use App\Entity\TradeOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TradeOrderToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a TradeOrder object into a string representing the TradeOrder id
     * @param  TradeOrder|null $tradeOrder
     */
    public function transform($tradeOrder): ?string
    {
        if (null === $tradeOrder) {
            return null;
        }

        return (string) $tradeOrder->getId();
    }

    /**
     * @param  string $tradeOrderId
     * @return TradeOrder|null
     * @throws TransformationFailedException if object (TradeOrder) is not found.
     */
    public function reverseTransform($tradeOrderId): ?TradeOrder
    {
        if (!$tradeOrderId) {
            return null;
        }

        $tradeOrder = $this->entityManager
            ->getRepository(TradeOrder::class)
            ->find($tradeOrderId);

        if (null === $tradeOrder) {
            throw new TransformationFailedException(sprintf(
                'A TradeOrder with id "%s" does not exist!',
                $tradeOrderId,
            ));
        }

        return $tradeOrder;
    }
}
