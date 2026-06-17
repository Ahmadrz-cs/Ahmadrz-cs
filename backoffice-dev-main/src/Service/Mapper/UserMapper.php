<?php

namespace App\Service\Mapper;

use App\Dto\User\UserRequestDto;
use App\Dto\User\UserResponseDto;
use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public function mapToDto(User|UserInterface $entity): UserResponseDto
    {
        return new UserResponseDto(
            id: $entity?->getId(),
            username: $entity->getUserIdentifier(),
            contactEmail: $entity?->getEmail(),
            firstName: $entity?->getFirstname(),
            lastName: $entity?->getLastname(),
            middleNames: $entity?->getMiddlename(),
            status: $entity?->getCurrentStatus(),
            createdAt: $entity?->getCreatedAt(),
            updatedAt: $entity?->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<User> $entityList
     * @return UserResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than User objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof User) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . User::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(UserRequestDto $dto, ?User $entity = null): User
    {
        // If no user entity provided, create a new user entity with username and password
        if ($entity === null) {
            //Set a random password if no password is set in $userDTO
            $entity = new User();
            $entity->setUsername($dto->username);
            $encodedPassword = $this->userPasswordHasher->hashPassword(
                $entity,
                $dto->password ?? bin2hex(random_bytes(32)),
            );
            $entity->setPassword($encodedPassword);
        }
        $entity->setEmail(
            $dto->contactEmail ?? $entity->getEmail() ?? $entity->getUserIdentifier(),
        );
        return $entity;
    }
}
