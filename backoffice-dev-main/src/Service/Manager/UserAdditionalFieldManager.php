<?php

namespace App\Service\Manager;

use App\Entity\UserCustomFields;
use App\Service\Manager\BaseManager;

class UserAdditionalFieldManager extends BaseManager
{
    protected $entityClass = UserCustomFields::class;
}
