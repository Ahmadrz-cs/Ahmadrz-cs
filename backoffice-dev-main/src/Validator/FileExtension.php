<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class FileExtension extends Constraint
{
    public $message = 'File type invalid: available file types are "PDF", "JPEG", "PNG", "GIF"';
}
