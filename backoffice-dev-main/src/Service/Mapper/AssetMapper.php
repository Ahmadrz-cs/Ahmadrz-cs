<?php

namespace App\Service\Mapper;

use App\Dto\Asset\AssetAddressResponseDto;
use App\Dto\Asset\AssetRequestDto;
use App\Dto\Asset\AssetResponseDto;
use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\Enum\Visibility;
use Psr\Log\LoggerInterface;

class AssetMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private DocumentMapper $documentMapper,
    ) {}

    public function mapToDto(Asset $entity): AssetResponseDto
    {
        return new AssetResponseDto(
            id: $entity->getId(),
            name: $entity->getName(),
            companyName: $entity->getCompanyNumber(),
            description: $entity->getBriefDescription(),
            pricePerShare: $entity->getPricePerShare(),
            numberOfShares: $entity->getAmountOfShares(),
            sharesAvailable: $entity->getSharesAvailable(),
            minimumInvestment: $entity->getMinimumInvestment(),
            type: $entity->getAssetType(),
            status: $entity->getCurrentStatus(),
            statusOccuredAt: $entity->getCurrentStatusLog()
                ? $entity->getCurrentStatusLog()->getOccuredAt()
                : $entity->getCreatedAt(),
            termStart: $entity->getTermStart(),
            termEnd: $entity->getTermEnd(),
            termRemaining: $entity->getTermRemaining(),
            termLength: $entity->getTermLength(),
            netProjectedIncome: $entity->getNetProjectedIncome(),
            netProjectedYield: $entity->getNetProjectedYield(),
            featured: $entity->getFeatured(),
            buyRestricted: $entity->isBuyRestricted(),
            sellRestricted: $entity->isSellRestricted(),
            visibility: Visibility::fromInt($entity->getVisibility()),
            documents: $this->documentMapper->mapMultipleToDto($entity->getDocuments()),
            address: $this->mapAddressToDto($entity->getMainAddress()),
            fees: $entity->getFeesGrouped(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<Asset> $entityList
     * @return AssetResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than Asset objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof Asset) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . Asset::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(AssetRequestDto $dto, ?Asset $entity = null): Asset
    {
        // Overwrite existing asset entity if provided, else create a new asset entity
        $entity ??= new Asset();
        $entity->setName($dto->name ?? $entity->getName());
        $entity->setInvestmentTerm($dto->termLength ?? $entity->getInvestmentTerm());
        $entity->setNetProjectedIncome(
            $dto->netProjectedIncome ?? $entity->getNetProjectedIncome(),
        );
        $entity->setNetProjectedYield(
            $dto->netProjectedYield ?? $entity->getNetProjectedYield(),
        );
        return $entity;
    }

    private function mapAddressToDto(AssetAddress $entity): AssetAddressResponseDto
    {
        return new AssetAddressResponseDto(
            assetId: $entity->getAsset()?->getId(),
            address1: $entity->getAddress1(),
            address2: $entity->getAddress2(),
            address3: $entity->getAddress3(),
            city: $entity->getCity(),
            postCode: $entity->getPostCode(),
            country: $entity->getCountry(),
            latitude: $entity->getLatitude(),
            longitude: $entity->getLongitude(),
        );
    }
}
