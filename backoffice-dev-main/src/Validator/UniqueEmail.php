<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueEmail extends Constraint
{
    public $message = 'Email already exists. The email address "{{ email }}" has already been registered';
}
