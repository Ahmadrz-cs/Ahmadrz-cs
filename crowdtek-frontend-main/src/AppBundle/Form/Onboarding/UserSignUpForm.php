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
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserSignUpForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        switch ($options['flow_step']) {
            case 1:

                $builder
                    ->add('firstname', TextType::class, [
                        'required' => true,
                        'label' => 'First Name'
                    ])
                    ->add('lastname', TextType::class, [
                        'label' => 'Surname'
                    ])
                    ->add('email', EmailType::class, [
                        'constraints' => [
                            new NotBlank(),
                        ],
                        'required' => true,
                        'label' => 'Email Address'
                    ])
                    ->add('password', RepeatedType::class, [
                        'type' => PasswordType::class,
                        'first_options' => ['label' => 'Password'],
                        'second_options' => ['label' => 'Confirm Password'],
                        'invalid_message' => 'The password fields must match',
                        'required' => true,
                    ]);
                break;
            case 2:
                $builder
                    ->add('term_service_accepted', CheckboxType::class, [
                        'constraints' => new NotBlank(),
                        'required' => true, 'label' => false,
                        'invalid_message' => 'Please accept terms',
                    ]);
                break;
        }
    }

    public function getBlockPrefix(): string
    {
        return 'signUpUser';
    }
}
