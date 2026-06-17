<?php

namespace App\Entity\Enum;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Acquiring = 'acquiring'; // transitional state - useful for prefunding
    case Active = 'active'; // actively being held and/or managed
    case Closing = 'closing'; // transitional state - when term is (nearly) over and we are preparing for exit
    case Archived = 'archived'; // asset has successfully exited
    case Cancelled = 'cancelled'; // did not go ahead

    /**
     * Return list of cases that excludes cancelled status
     * @return AssetStatus[]
     */
    public static function typicalCases(): array
    {
        return [
            AssetStatus::Draft,
            AssetStatus::Acquiring,
            AssetStatus::Active,
            AssetStatus::Closing,
            AssetStatus::Archived,
        ];
    }

    /**
     * Return list of cases that include statuses where the asset under active management
     * @return AssetStatus[]
     */
    public static function activeCases(): array
    {
        return [
            AssetStatus::Acquiring,
            AssetStatus::Active,
            AssetStatus::Closing,
        ];
    }

    /**
     * Return list of include all active cases plus draft (pre-launch) status
     * @return AssetStatus[]
     */
    public static function workingCases(): array
    {
        return [
            AssetStatus::Draft,
            AssetStatus::Acquiring,
            AssetStatus::Active,
            AssetStatus::Closing,
        ];
    }

    /**
     * Return list of include all statuses that investors (the public) should have access to
     * @return AssetStatus[]
     */
    public static function publicCases(): array
    {
        return [
            AssetStatus::Acquiring,
            AssetStatus::Active,
            AssetStatus::Closing,
            AssetStatus::Archived,
        ];
    }
}
