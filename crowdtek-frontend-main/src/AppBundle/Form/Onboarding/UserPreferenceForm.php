<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 27/07/18
 * Time: 16:16
 */

namespace AppBundle\Form\Onboarding;

use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserPreferenceForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        switch ($options['flow_step']) {
            case 1:

                $builder
                    ->add('contact_via_email', CheckboxType::class, [
                        'label' => 'Email',
                        'required' => false,
                    ])
                    ->add('contact_via_tele', CheckboxType::class, [
                        'label' => 'Telephone',
                        'required' => false,
                    ])
                    ->add('contact_via_sms', CheckboxType::class, [
                        'label' => 'SMS',
                        'required' => false,
                    ]);
                break;
            case 2:
                $builder
                    // ->add('investor_type', ChoiceType::class, [
                    //     'choices' => [
                    //         'cxb_restricted_investor' => 'cxb_restricted_investor',
                    //         'cxb_sophisticated_investor' => 'cxb_sophisticated_investor',
                    //         'cxb_worth_investor' => 'cxb_worth_investor',
                    //     ],
                    //     'expanded' => true,
                    //     'attr' => ['onclick' => 'showcontent']
                    // ])
                    ->add('fatca', CheckboxType::class, [
                        'label' => 'fatca',
                        'required' => false,
                    ]);
                break;
        }
    }

    public function getBlockPrefix(): string
    {
        return 'userPreference';
    }
}
