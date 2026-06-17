<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MarketingPreference extends Constraint
{
    public $message = 'MarketingPreference invalid: available options are "sms", "email" or "phone"';
}
