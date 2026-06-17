<?php

namespace App\Service\Mapper;

use App\Dto\ShareTrade\ShareTradeResponseDto;
use App\Entity\Enum\ShareTradeType;
use App\Entity\ShareTrade;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class ShareTradeMapper
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function mapToDto(ShareTrade $entity): ShareTradeResponseDto
    {
        return new ShareTradeResponseDto(
            id: (string) $entity->getId(),
            uuid: $entity->getUuid(),
            assetId: (string) $entity->getBuyOrder()->getAsset()->getId(),
            assetName: $entity->getBuyOrder()->getAsset()->getName(),
            sellerId: (string) $entity->getSellOrder()->getUser()->getId(),
            buyerId: $entity->getBuyOrder()->getUser()->getId(),
            pricePerShare: $entity->getPricePerShare(),
            numberOfShares: new Number($entity->getNumberOfShares() ?? 0),
            tradeValue: $entity->getTradeValue(),
            status: $entity->getStatus(),
            statusOccuredAt: $entity->getCurrentStatusLog()
                ? $entity->getCurrentStatusLog()->getOccuredAt()
                : $entity->getCreatedAt(),
            type: ShareTradeType::fromBuySellTypes(
                $entity->getBuyOrder()->getType(),
                $entity->getSellOrder()->getType(),
            ),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<ShareTrade> $entityList
     * @return ShareTradeResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than ShareTrade objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof ShareTrade) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . ShareTrade::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }
}
