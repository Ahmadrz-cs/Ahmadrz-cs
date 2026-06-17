<?php

namespace App\Service\Mapper;

use App\Dto\BankAccount\BankAccountRequestDto;
use App\Dto\BankAccount\BankAccountResponseDto;
use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountType;
use App\Service\BankAccountService;
use Psr\Log\LoggerInterface;

class BankAccountMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private BankAccountService $bankAccountService,
    ) {}

    public function mapToDto(BankAccount $entity): BankAccountResponseDto
    {
        return new BankAccountResponseDto(
            id: $entity->getId(),
            uuid: $entity->getUuid(),
            userId: $entity->getUser()->getId(),
            country: $entity->getCountry(),
            currency: $entity->getCurrency(),
            accountHolderType: $entity->getAccountHolderType(),
            accountNumber: $entity->getAccountNumber(),
            bic: $entity->getBankIdentifierCode(),
            status: $entity->getStatus(),
            method: match ($entity->getAccountType()) {
                BankAccountType::GB => 'local_bank_transfer',
                default => 'international_bank_transfer',
            },
            displayName: $entity->getDisplayName(),
            providerId: $entity->getProviderId(),
            description: $entity->getDescription(),
            metadata: $entity->getMetadata(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<BankAccount> $entityList
     * @return BankAccountResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than BankAccount objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof BankAccount) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . BankAccount::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(
        BankAccountRequestDto $dto,
        ?BankAccount $entity = null,
    ): BankAccount {
        // Overwrite existing BankAccount entity if provided, else create a new BankAccount entity
        $entity ??= new BankAccount();
        $entity->setCountry($dto->country ?? $entity->getCountry());
        $entity->setAccountHolderType(
            $dto->accountHolderType ?? $entity->getAccountHolderType(),
        );
        $entity->setAccountNumber($dto->accountNumber ?? $entity->getAccountNumber());
        $entity->setBankIdentifierCode($dto->bic ?? $entity->getBankIdentifierCode());
        $entity->setDescription($dto->description ?? $entity->getDescription());
        $entity->setAccountType(match ($entity->getCountry()) {
            'GB' => BankAccountType::GB,
            default => BankAccountType::International,
        });
        // Only set the fingerprint if an accountNumber exists
        // Possible for the account details (number and bic) to be wiped
        // After which the fingerprint is the primary identifier for deduplication
        if (!empty($entity->getAccountNumber())) {
            $entity->setFingerprint($this->bankAccountService->getFingerprint($entity));
            $entity->setDisplayName($this->bankAccountService->createDisplayName(
                $entity,
            ));
        }
        return $entity;
    }
}
