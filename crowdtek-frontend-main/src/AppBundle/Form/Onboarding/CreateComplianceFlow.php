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

class CreateComplianceFlow extends FormFlow
{
    /**
     * @return array
     */
    protected function loadStepsConfig()
    {
        return [
            [
                'label' => 'Questionnaire Passed',
                'form_type' => 'AppBundle\Form\Onboarding\UserInformationForm',
            ],
            [
                'label' => 'Personal Information',
                'form_type' => 'AppBundle\Form\Onboarding\UserInformationForm',
            ],
        ];
    }
}
