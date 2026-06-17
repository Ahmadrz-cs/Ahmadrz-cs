<?php

namespace App\Form\DataTransformer;

use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TransactionToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a Transaction object into a string representing the Transaction id
     * @param  Transaction|null $transaction
     */
    public function transform($transaction): ?string
    {
        if (null === $transaction) {
            return null;
        }

        return (string) $transaction->getId();
    }

    /**
     * @param  string $transactionId
     * @return Transaction|null
     * @throws TransformationFailedException if object (Transaction) is not found.
     */
    public function reverseTransform($transactionId): ?Transaction
    {
        if (!$transactionId) {
            return null;
        }

        $transaction = $this->entityManager
            ->getRepository(Transaction::class)
            ->find($transactionId);

        if (null === $transaction) {
            throw new TransformationFailedException(sprintf(
                'A Transaction with id "%s" does not exist!',
                $transactionId,
            ));
        }

        return $transaction;
    }
}
