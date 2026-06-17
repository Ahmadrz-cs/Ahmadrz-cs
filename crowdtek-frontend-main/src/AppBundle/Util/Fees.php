<?php

namespace AppBundle\Util;

class Fees
{
    public static function getMonthlyRelistingAmount(
        array $offerings,
        int $assetId,
        int $userId
    ): float {
        /**
         * Aggregate relistings made in current month
         */
        $existingAmount = 0;
        foreach ($offerings as $offering) {
            if ($offering['asset_id'] == $assetId && $offering['user_id'] == $userId) {
                $createdTime = \Datetime::createfromformat(
                    DATE_W3C,
                    $offering['created_at']
                );
                if ($createdTime->format('Y-m') === date('Y-m', time())) {
                    $existingAmount += (float)$offering['funding_goal'];
                }
            }
        }
        return $existingAmount;
    }

    public static function getRelistingFeeDue(
        array $fees,
        float $existingAmount,
        float $relistingAmount
    ): int {
        /**
         * Determine the relisting fee due
         */
        $existingFeesPaid = self::getFeeCap($fees, $existingAmount);
        $newFeeCap = self::getFeeCap($fees, $existingAmount + $relistingAmount);
        return $newFeeCap - $existingFeesPaid;
    }

    public static function getFeeCap(array $fees, float $amount): int
    {
        /**
         * Determine the correct fee band for a given relisting amount
         */
        $fee = 0;
        foreach ($fees as $band => $feeCap) {
            if (!empty($amount) && $amount > (int)$band) {
                $fee = (int)$feeCap;
            }
        }
        return $fee;
    }
}
