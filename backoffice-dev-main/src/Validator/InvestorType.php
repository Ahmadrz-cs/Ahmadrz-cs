<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class InvestorType extends Constraint
{
    public $message = 'InvestorType invalid: available options are "everyday", "sophisticated", "high net worth", "institutional"';
}
