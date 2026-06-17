<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class LifecycleStatus extends Constraint
{
    public $message = 'status invalid: available options are "draft", "cancelled", "submitted", "rejected", "approved", "published", "restricted", "closed", "settled". "archived"';
}
