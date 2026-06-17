<?php

namespace App\Entity\Enum;

/**
 * Type safety for specifying valid email templates
 * Email templates are stored in the directory `templates/mail/`
 */
enum EmailTemplate: string
{
    // i_logo for image, t_logo for text, no risk warnings - intended for staff emails
    case Basic = 'i_logo_basic';

    // Same as Basic, but has various risk warnings
    case BasicCustomer = 'i_logo_basic_customer';

    // dedicated auth code template
    case LoginAuthCode = 'i_logo_auth_code';

    // compatibility template for classic emails that use default.html.twig
    case Compatibility = 'i_logo_compatibility';
}
