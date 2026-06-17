<?php

namespace App\Service\Mapper;

use App\Dto\Offering\OfferingRequestDto;
use App\Dto\Offering\OfferingResponseDto;
use App\Entity\Offering;
use App\Repository\AssetRepository;
use Psr\Log\LoggerInterface;

class OfferingMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
    ) {}

    public function mapToDto(Offering $entity): OfferingResponseDto
    {
        return new OfferingResponseDto(
            id: $entity->getId(),
            assetId: $entity->getAsset()?->getId(),
            investmentId: $entity->getSellInvestment()?->getId(),
            name: $entity->getName(),
            type: $entity->getOfferingType(),
            pricePerShare: $entity->getPricePerShare(),
            numberOfShares: $entity->getNoOfShares(),
            numberOfSharesSold: $entity->getSharesSold(),
            featured: $entity->getIsFeatured(),
            status: $entity->getLifecycleStatus(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<Offering> $entityList
     * @return OfferingResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than Offering objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof Offering) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . Offering::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(
        OfferingRequestDto $dto,
        ?Offering $entity = null,
    ): Offering {
        // Overwrite existing offering entity if provided, else create a new offering entity
        $entity ??= new Offering();
        $entity->setName($dto->name ?? $entity->getName());
        if ($dto->assetId) {
            $entity->setAsset(
                $this->assetRepository->find($dto->assetId) ?? $entity->getAsset(),
            );
        }
        if ($dto->status) {
            // Note that applying a lifecycle status is not idempotent as ew DateTime objects will be set
            $entity->setLifecycleStatus($dto->status);
        }
        return $entity;
    }
}
