<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToNumberTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Convert a user object into a string representing the user id
     * @param  User|null $value
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return null;
        }

        return (string) $value->getId();
    }

    /**
     * @param  string $value
     * @return User|null
     * @throws TransformationFailedException if object (user) is not found.
     */
    public function reverseTransform($value): ?User
    {
        if (!$value) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->find($value);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'A user with id "%s" does not exist!',
                $value,
            ));
        }

        return $user;
    }
}
