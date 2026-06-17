<?php

namespace App\Entity\Enum;

enum Visibility: string
{
    case Auto = 'auto'; // No restrictions
    case Admin = 'admin'; // Admin only
    case Vip = 'vip'; // Top Yielders or admin

    /**
     * For converting from old integer values for visibility in src/Entity/BaseEntity.php
     *
     * Integers are what are currently persisted in the database
     */
    public static function fromInt(?int $visibilityInt): Visibility
    {
        return match ($visibilityInt) {
            1 => Visibility::Admin,
            2 => Visibility::Vip,
            default => Visibility::Auto,
        };
    }

    /**
     * Convert enum to int representation found in src/Entity/BaseEntity.php
     */
    public function toInt(): int
    {
        return match ($this) {
            Visibility::Admin => 1,
            Visibility::Vip => 2,
            default => 0,
        };
    }
}
