<?php

namespace AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
* @Annotation
*/
class UniqueEmail extends Constraint
{
    public $message = 'The email address {{ email }} is already registered on the platform.';
}
