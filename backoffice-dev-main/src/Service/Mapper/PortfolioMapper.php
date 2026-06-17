<?php

namespace App\Service\Mapper;

use App\Dto\Portfolio\PortfolioPositionResponseDto;
use App\Dto\Portfolio\PortfolioResponseDto;
use App\Dto\Struct\Portfolio;
use App\Dto\Struct\PortfolioPosition;
use Psr\Log\LoggerInterface;

class PortfolioMapper
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function mapToDto(Portfolio $struct): PortfolioResponseDto
    {
        return new PortfolioResponseDto(
            userId: $struct->userId,
            value: $struct->value,
            dividends: $struct->dividends,
            capitalGains: $struct->capitalGains,
            positions: $this->mapPositionsToDto($struct->positions),
        );
    }

    /**
     * @param PortfolioPosition[] $structList
     * @return PortfolioPositionResponseDto[]
     */
    private function mapPositionsToDto(array $structList): array
    {
        $percentFormatter = new \NumberFormatter('en_GB', \NumberFormatter::PERCENT);
        $percentFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 1);
        $percentFormatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 4);

        $dtoList = [];
        foreach ($structList as $struct) {
            $dtoList[] = new PortfolioPositionResponseDto(
                assetId: (string) $struct->asset?->getId(),
                assetName: $struct->asset?->getName(),
                assetYield: $percentFormatter->format(
                    $struct->asset?->getNetProjectedYield() ?? '0.00',
                ),
                assetTermRemaining: $struct->asset->getTermRemaining() ?? '0',
                averagePrice: $struct->averagePrice,
                shares: $struct->shares,
                value: $struct->value,
                dividends: $struct->dividends,
                capitalGains: $struct->capitalGains,
                buyShares: $struct->buyShares,
                buyValue: $struct->buyValue,
                sellShares: $struct->sellShares,
                sellValue: $struct->sellValue,
                sharesAvailable: $struct->sharesAvailable,
            );
        }
        return $dtoList;
    }
}
