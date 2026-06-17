<?php

namespace AppBundle\Entity\Enum;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Acquiring = 'acquiring'; // transitional state - useful for prefunding
    case Active = 'active'; // actively being held and/or managed
    case Closing = 'closing'; // transitional state - when term is (nearly) over and we are preparing for exit
    case Archived = 'archived'; // asset has successfully exited
    case Cancelled = 'cancelled'; // did not go ahead

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
}
