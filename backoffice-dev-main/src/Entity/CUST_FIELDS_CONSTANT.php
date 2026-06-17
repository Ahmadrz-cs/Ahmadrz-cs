<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 19/10/18
 * Time: 23:46
 */

namespace App\Entity;

/***
 * Class CUST_FIELDS_CONSTANT
 * @package App\Entity
 *
 * list of custom fields used with yielders
 *
 */
class CUST_FIELDS_CONSTANT
{
    public const CF_VIA_EMAIL = 'contact_via_email';
    public const CF_VIA_SMS = 'contact_via_sms';
    public const CF_VIA_TELE = 'contact_via_tele';
    public const CF_Q_ATTEMPS = 'questionnaire_attempts';
    public const CF_Q_PASSED = 'questionnaire_passed';
}
