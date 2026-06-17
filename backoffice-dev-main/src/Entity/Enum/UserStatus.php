<?php

namespace App\Entity\Enum;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';

    /**
     * @return UserStatus[]
     */
    public static function inactive(): array
    {
        return [
            UserStatus::Suspended,
            UserStatus::Closed,
        ];
    }
}
