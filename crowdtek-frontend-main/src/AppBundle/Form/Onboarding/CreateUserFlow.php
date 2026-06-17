<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 27/07/18
 * Time: 16:22
 */

namespace AppBundle\Form\Onboarding;

use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

class CreateUserFlow extends FormFlow
{
    /**
     * @return array
     */
    protected function loadStepsConfig()
    {
        return [
            [
                'label' => 'Sign Up',
                'form_type' => 'AppBundle\Form\Onboarding\UserSignUpForm',
                'form_options' => [
                    'validation_groups' => ['registration'],
                ],
            ],
            [
                'label' => 'Terms & Conditions and Privacy Policy',
                'form_type' => 'AppBundle\Form\Onboarding\UserSignUpForm',
            ],
        ];
    }
}
