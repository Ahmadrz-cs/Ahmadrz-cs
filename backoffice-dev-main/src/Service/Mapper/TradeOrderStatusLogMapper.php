<?php

namespace App\Service\Mapper;

use App\Dto\TradeOrder\TradeOrderStatusLogRequestDto;
use App\Dto\TradeOrder\TradeOrderStatusLogResponseDto;
use App\Entity\TradeOrderStatusLog;
use Psr\Log\LoggerInterface;

class TradeOrderStatusLogMapper
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function mapToDto(TradeOrderStatusLog $entity): TradeOrderStatusLogResponseDto
    {
        return new TradeOrderStatusLogResponseDto(
            id: $entity->getId(),
            tradeOrderId: $entity->getTradeOrder()->getId(),
            status: $entity->getStatus(),
            notes: $entity->getNotes(),
            occuredAt: $entity->getOccuredAt(),
        );
    }

    /**
     * @param \Traversable<TradeOrderStatusLog> $entityList
     * @return TradeOrderStatusLogResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than TradeOrderStatusLog objects
     */
    public function mapMultipleToDto(\Traversable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof TradeOrderStatusLog) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . TradeOrderStatusLog::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(
        TradeOrderStatusLogRequestDto $dto,
        ?TradeOrderStatusLog $entity = null,
    ): TradeOrderStatusLog {
        // Overwrite existing entity if provided, else create a new entity
        $entity ??= new TradeOrderStatusLog();
        $entity->setStatus($dto->status ?? $entity->getStatus());
        $entity->setNotes($dto->notes ?? $entity->getNotes());
        $entity->setOccuredAt($dto->occuredAt ?? $entity->getOccuredAt());
        return $entity;
    }
}
