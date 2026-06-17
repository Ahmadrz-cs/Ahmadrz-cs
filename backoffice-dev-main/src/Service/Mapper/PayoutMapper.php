<?php

namespace App\Service\Mapper;

use App\Dto\Payout\PayoutResponseDto;
use App\Entity\Enum\PayoutType;
use App\Entity\Payout;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class PayoutMapper
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function mapToDto(Payout $entity): PayoutResponseDto
    {
        return new PayoutResponseDto(
            id: (string) $entity->getId(),
            userId: (string) $entity->getCreditedUser()?->getId(),
            assetId: (string) $entity->getAsset()?->getId(),
            assetName: (string) $entity->getAsset()?->getName(),
            shares: new Number($entity->getShareholding() ?? 0),
            value: new Number((string) $entity->getPayoutAmount() ?? '0')->round(2),
            type: PayoutType::tryFrom($entity->getPayoutType()),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<Payout> $entityList
     * @return PayoutResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than Payout objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof Payout) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . Payout::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }
}
