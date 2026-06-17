<?php

namespace ClientBundle\Exception;

class PrefundNotAllowedException extends \Exception
{
    /**
     * Exception for when requirements for prefunding are not met
     *
     * 1. User is not VIP
     * 2. Opportunity is not prefunding (offering is wrong type)
     * 3. Opportunity is not open for sale (offering is fully funded)
     */
}
