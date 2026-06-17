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

class CreateRegulationFlow extends FormFlow
{
    /**
     * @return array
     */
    protected function loadStepsConfig()
    {
        return [
            [
                'label' => 'Marketing preferences',
                'form_type' => 'AppBundle\Form\Onboarding\UserPreferenceForm',
            ],
            [
                'label' => 'Investor Declaration',
                'form_type' => 'AppBundle\Form\Onboarding\UserPreferenceForm',
            ],
        ];
    }
}
