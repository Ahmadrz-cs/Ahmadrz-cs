<?php

namespace App\Service\Mapper;

use App\Dto\TradeOrder\TradeOrderRequestDto;
use App\Dto\TradeOrder\TradeOrderResponseDto;
use App\Entity\TradeOrder;
use App\Repository\AssetRepository;
use App\Repository\TradeOrderRepository;
use App\Repository\UserRepository;
use App\Service\TradingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

class TradeOrderMapper
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private UserRepository $userRepository,
        private TradingService $tradingService,
    ) {}

    public function mapToDto(TradeOrder $entity): TradeOrderResponseDto
    {
        return new TradeOrderResponseDto(
            id: $entity->getId(),
            uuid: $entity->getUuid(),
            assetId: $entity->getAsset()->getId(),
            assetName: $entity->getAsset()->getName(),
            userId: $entity->getUser()->getId(),
            pricePerShare: $entity->getPricePerShare(),
            numberOfShares: $entity->getNumberOfShares(),
            minimumShares: $entity->getMinimumShares(),
            maximumShares: $entity->getMaximumShares(),
            sharesTraded: $entity->getSharesTraded(),
            sharesAvailable: $entity->getSharesAvailable(),
            fees: $entity->getFees(),
            taxes: $entity->getTaxes(),
            status: $entity->getStatus(),
            statusOccuredAt: $entity->getCurrentStatusLog()
                ? $entity->getCurrentStatusLog()->getOccuredAt()
                : $entity->getCreatedAt(),
            direction: $entity->getDirection(),
            notes: $entity->getNotes(),
            type: $entity->getType(),
            createdAt: $entity->getCreatedAt(),
            updatedAt: $entity->getUpdatedAt(),
        );
    }

    /**
     * @param iterable<TradeOrder> $entityList
     * @return TradeOrderResponseDto[]
     *
     * @throws \InvalidArgumentException if entityList contains anything other than TradeOrder objects
     */
    public function mapMultipleToDto(iterable $entityList): array
    {
        // This should return a DTO for list views with pagination information
        $dtoList = [];
        foreach ($entityList as $entity) {
            if (!$entity instanceof TradeOrder) {
                throw new \InvalidArgumentException('entityList parameter must only contain objects of type '
                . TradeOrder::class);
            }
            $dtoList[] = $this->mapToDto($entity);
        }
        return $dtoList;
    }

    public function mapToEntity(
        TradeOrderRequestDto $dto,
        ?TradeOrder $entity = null,
    ): TradeOrder {
        // Overwrite existing entity if provided, else create a new entity
        $entity ??= new TradeOrder();
        if ($dto->assetId) {
            $entity->setAsset(
                $this->assetRepository->find($dto->assetId) ?? $entity->getAsset(),
            );
        }
        if ($dto->userId) {
            $entity->setUser(
                $this->userRepository->find($dto->userId) ?? $entity->getUser(),
            );
        }
        /**
         * Note that setStatus is a shortcut for adding a new status log, so it's not idempotent
         * So don't use null coalesce for setStatus, otherwise you'll add duplicate status logs
         */
        if ($dto->status) {
            $entity->setStatus($dto->status);
        }
        $entity->setDirection($dto->direction ?? $entity->getDirection());
        $entity->setPricePerShare(
            $dto->pricePerShare ?? $entity->getPricePerShare() ?? $this->assetRepository
                ->find($dto->assetId)
                ?->getPricePerShare(),
        );
        $entity->setNumberOfShares(
            $dto->numberOfShares ?? $entity->getNumberOfShares(),
        );
        $entity->setMinimumShares($dto->minimumShares ?? $entity->getMinimumShares());
        $entity->setMaximumShares($dto->maximumShares ?? $entity->getMaximumShares());
        $entity->setNotes($dto->notes ?? $entity->getNotes());
        $entity->setType($dto->type ?? $entity->getType());
        $entity->setFees($dto->fees ?? $entity->getFees());
        $entity->setTaxes($dto->taxes ?? $entity->getTaxes());
        if ($dto->complementaryOrderId) {
            $complementaryOrder = $this->tradeOrderRepository->find($dto->complementaryOrderId);
            // The chosen order to complement must not already be complementing another trade order
            if ($complementaryOrder?->getComplementaryOrder() !== null) {
                throw new BadRequestException(
                    'Complementary order must not already be linked to another trade order',
                );
            }
            if (
                $complementaryOrder
                && $this->tradingService->validateComplementaryOrder(
                    $entity,
                    $complementaryOrder,
                )
            ) {
                $entity->setComplementaryOrder($complementaryOrder);
                $complementaryOrder->setComplementaryOrder($entity);
            }
        }
        return $entity;
    }
}
