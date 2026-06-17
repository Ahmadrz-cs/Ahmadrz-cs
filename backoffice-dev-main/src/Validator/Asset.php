<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Asset extends Constraint
{
    public $message = 'asset id: {{ assetId }} does not exist or is unpublished.';
}
