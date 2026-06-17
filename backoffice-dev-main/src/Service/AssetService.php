<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\AssetStatusLog;
use App\Entity\Enum\AssetStatus;
use App\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * High level service for business domain orientated activities
 */
class AssetService
{
    public const BASELINE_SHARE_AMOUNT = 100000;

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function generateSharePriceRange(
        int $fundingGoal,
        ?int $sharePriceUserFloor,
        ?int $sharePriceUserCap,
    ): array {
        // Note that even though funding goal should already be in pence
        // We need to multiple by 100 again, since we're doing the square root
        // And we need the share price cap in pence as well
        // E.g. sqrt(£100) == £10, but in pence, £10 == 1000p, 1000^2 == 1,000,000 == £10,000
        $sharePriceHardCap = sqrt($fundingGoal * 100);
        $sharePriceSoftCap = (int) ceil((2 * $fundingGoal)
        / self::BASELINE_SHARE_AMOUNT);
        $sharePriceSoftFloor = (int) ceil($fundingGoal / self::BASELINE_SHARE_AMOUNT);

        // If null or 0 provided (neither or which are valid), use the safe soft limits
        $sharePriceUserCap = empty($sharePriceUserCap)
            ? $sharePriceSoftCap
            : $sharePriceUserCap;
        $sharePriceUserFloor = empty($sharePriceUserFloor)
            ? $sharePriceSoftFloor
            : $sharePriceUserFloor;

        return [
            'min' => max($sharePriceUserFloor, 1),
            'max' => min($sharePriceUserCap, $sharePriceHardCap),
        ];
    }

    /**
     * @return int[]
     */
    public function suggestSharePrice(
        int $fundingGoal,
        int $minSharePrice,
        int $maxSharePrice,
    ): array {
        $suggestions = [];
        for ($i = $minSharePrice; $i <= $maxSharePrice; $i++) {
            if (($fundingGoal % $i) == 0) {
                $suggestions[] = (int) $i;
            }
        }
        return $suggestions;
    }

    public function applyStatusChange(
        Asset $asset,
        ?AssetStatus $status = null,
        ?string $reason = null,
        ?User $transitionedBy = null,
        ?\DateTime $occuredAt = null,
    ): AssetStatusLog {
        if (is_null($status)) {
            $status = $asset->getCurrentStatus();
        }

        $assetStatusLog = new AssetStatusLog(status: $status);
        $asset->addStatusLog($assetStatusLog);
        $assetStatusLog->setTransitionedBy($transitionedBy);
        $assetStatusLog->setNotes($reason);
        if ($occuredAt) {
            // Set a custom occuredAt datetime is given
            // Else uses default "now"
            $assetStatusLog->setOccuredAt($occuredAt);
        }
        return $assetStatusLog;
    }
}
