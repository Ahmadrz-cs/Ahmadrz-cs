<?php

namespace App\Exception;

use Exception;

class InvalidEmailTypeException extends \Exception
{
    public function __construct($message = '', $code = 0, ?Exception $previous = null)
    {
        $message = sprintf(
            '"%s" is not a valid email type (??Did you forget to run doctrine:fixtures:load/schema:update/clear cache ??)',
            $message,
        );

        parent::__construct($message, $code, $previous);
    }
}
