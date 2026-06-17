<?php

namespace App\Service\Manager;

use App\Entity\InvestmentAddFields;
use App\Service\Manager\BaseManager;

class InvestmentAdditionalFieldManager extends BaseManager
{
    protected $entityClass = InvestmentAddFields::class;
}
