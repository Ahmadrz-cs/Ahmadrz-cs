<?php

namespace App\Service\Manager;

use App\Entity\Address;
use App\Service\Manager\BaseManager;

class AddressManager extends BaseManager
{
    protected $entityClass = Address::class;
}
