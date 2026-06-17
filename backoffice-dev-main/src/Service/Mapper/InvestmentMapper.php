<?php

namespace App\Service\Mapper;

use App\Dto\Investment\InvestmentRequestDto;
use App\Dto\Investment\InvestmentResponseDto;
use App\Entity\Investment;
use App\Repository\OfferingRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;

class InvestmentMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private OfferingRepository $offeringRepository,
        private UserRepository $userRepository,
    ) {}

    public function mapToDto(Investment $entity): InvestmentResponseDto
    {
        return new InvestmentResponseDto(
            id: $entity->getId(),
            userId: $entity->getUser()?->getId(),
            offeringId: $entity->getOffering()?->getId(),
            type: $entity->getType(),
            pricePerShare: $entity->getPricePerShare(),
            numberOfShares: $entity->getShareAmount() ?? $entity->getNumberOfShares(),
            transactionId: $entity->getTransactionId(),
            status: $entity->getLifecycleStatus(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<Investment> $entityList
     * @return InvestmentResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than Investment objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof Investment) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . Investment::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(
        InvestmentRequestDto $dto,
        ?Investment $entity = null,
    ): Investment {
        // Overwrite existing investment entity if provided, else create a new investment entity
        $entity ??= new Investment();
        if ($dto->offeringId) {
            $entity->setOffering(
                $this->offeringRepository->find($dto->offeringId) ?? $entity->getOffering(),
            );
        }
        if ($dto->userId) {
            $entity->setUser(
                $this->userRepository->find($dto->userId) ?? $entity->getUser(),
            );
        }
        if ($dto->status) {
            // Note that applying a lifecycle status is not idempotent as ew DateTime objects will be set
            $entity->setLifecycleStatus($dto->status);
        }
        // Set pricePerShare from the offering or asset
        $entity->setOrgPricePerShare(
            $entity->getOffering()->getPricePerShare() ?? $entity
                ->getOffering()
                ->getAsset()
                ->getPricePerShare(),
        );
        // Set custom pricePerShare if set, else copy from orgPricePerShare
        $entity->setPricePerShare(
            $dto->pricePerShare ?? $entity->getPricePerShare() ?? $entity->getOrgPricePerShare(),
        );
        $entity->setShareAmount($dto->numberOfShares ?? $entity->getShareAmount()); // primary field for numberOfShares
        $entity->setNumberOfShares($dto->numberOfShares ?? $entity->getNumberOfShares()); // duplicate field...
        $entity->setInvestmentValue(round(
            $entity->getShareAmount() * $entity->getPricePerShare(),
            2,
        ));
        $entity->setTransactionId($dto->transactionId ?? $entity->getTransactionId());
        $entity->setType($dto->type ?? $entity->getType());
        return $entity;
    }
}
