<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 13/10/18
 * Time: 22:51
 */

namespace AppBundle\Entity;

/**
 * Class EmailTagConstants
 * @package AppBundle\Entity
 *
 * constants related to the emails that can be sent from the backend
 *
 */
class EmailTagConstants
{
    public const TYPE_OB_COMPLETE = 'user.onboarding.complete';
    public const TYPE_OB_COMPLETE_ADMIN = 'user.onboarding.complete.admin';
    public const TYPE_OB_RESUBMIT = 'user.onboarding.resubmit';
    public const TYPE_OB_RESUBMIT_ADMIN = 'user.onboarding.resubmit.admin';
    public const TYPE_OB_CONTACT = 'user.onboarding.contact';
    public const TYPE_OB_CONTACT_ADMIN = 'user.onboarding.contact.admin';
}
