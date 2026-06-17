<?php

namespace App\Exception;

use Exception;

class LogActionNotDefinedException extends \Exception
{
    public function __construct($action)
    {
        parent::__construct(sprintf('No log action defined for event %s', $action));
    }
}
