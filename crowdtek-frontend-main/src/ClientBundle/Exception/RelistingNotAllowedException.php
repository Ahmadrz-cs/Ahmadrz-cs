<?php

namespace ClientBundle\Exception;

class RelistingNotAllowedException extends \Exception
{
    /**
     * Exception for when requirements for investment is not met
     *
     * 1. Insufficient wallet balance
     * 2. Insufficient shares available
     * 3. Amount outside min or max commit
     * 4. Amount outside retention proportion (prefunding only)
     */
}
