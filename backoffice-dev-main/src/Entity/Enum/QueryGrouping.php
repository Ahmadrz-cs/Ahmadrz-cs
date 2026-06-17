<?php

namespace App\Entity\Enum;

enum QueryGrouping: string
{
    case Asset = 'asset';
    case User = 'user';
    case AssetUser = 'asset_user';
}
