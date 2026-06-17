<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 19/08/18
 * Time: 23:46
 */

namespace App\Entity;

/**
 * Class OB_STEP_CONSTANT
 * @package App\Entity
 *
 * descriptions for the onboarding steps
 *
 */
class OB_STEP_CONSTANT
{
    public const STEP1 = 'Signed Up';
    public const STEP2 = 'Email Verified';
    public const STEP3 = 'Questionnaire';
    public const STEP3_F = 'Questionnaire-Failed';
    public const STEP4 = 'Compliance';
    public const STEP5 = 'Onboarding Complete';
    public const STEP6 = 'STEP 6';
    public const STEP7 = 'STEP 7';

    public const STEP1_INT = 1;
    public const STEP2_INT = 2;
    public const STEP3_INT = 3;
    public const STEP4_INT = 4;
    public const STEP5_INT = 5;
    public const STEP6_INT = 6;
    public const STEP7_INT = 7;
}
