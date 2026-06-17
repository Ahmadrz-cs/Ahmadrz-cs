<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class MimeType extends Constraint
{
    public $message = 'Invalid file type found. Available file types are "PDF", "JPEG", "PNG", "GIF"';
}
