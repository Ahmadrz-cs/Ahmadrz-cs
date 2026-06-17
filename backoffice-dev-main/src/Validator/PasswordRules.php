<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PasswordRules extends Constraint
{
    public $message = 'Password invalid. A password must contain at least 8 characters, 1 lowercase character, 1 uppercase character and 1 number.';
}
