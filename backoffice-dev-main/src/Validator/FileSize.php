<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class FileSize extends Constraint
{
    public $message = 'File size exceeded: The max size limit for a file is 4MB';
}
