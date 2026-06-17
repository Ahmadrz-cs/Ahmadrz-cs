<?php

namespace App\Dto\Struct;

use App\Entity\User;

class UserShares
{
    public function __construct(
        public User $user,
        public int $shares,
    ) {}
}
