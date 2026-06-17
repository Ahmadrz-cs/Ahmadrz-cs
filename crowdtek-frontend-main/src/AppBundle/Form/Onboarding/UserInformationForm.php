<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 27/07/18
 * Time: 16:16
 */

namespace AppBundle\Form\Onboarding;

use AppBundle\Form\FileType;
use AppBundle\Form\Onboarding\UserAddressForm;
use AppBundle\Form\Onboarding\UserDocumentForm;
use AppBundle\Util\Util;
use Gregwar\CaptchaBundle\Type\CaptchaType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserInformationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        switch ($options['flow_step']) {
            case 1:

                break;
            case 2:
                $builder
                    ->add('honorific_prefix', ChoiceType::class, [
                        'choices' => [
                            'Select an option' => 'Select an option',
                            'Mr' => 'Mr',
                            'Ms' => 'Ms',
                            'Mrs' => 'Mrs',
                            'Miss' => 'Miss',
                            'Dr' => 'Dr',
                            'Prof' => 'Prof',
                            'Other' => 'Other',
                        ],
                    ])
                    ->add('gender', ChoiceType::class, [
                        'choices' => [
                            'Select an option' => 'Select an option',
                            'Male' => 'MALE',
                            'Female' => 'FEMALE',
                            'Other' => 'OTHER',
                        ],
                    ])
                    ->add('firstname', TextType::class)
                    ->add('lastname', TextType::class)
                    ->add('birthDate', BirthdayType::class, [
                        //                        'widget' => 'single_text',
                        'format' => 'ddMMyyyy',
                        'years' => range(date('Y') - 0, date('Y') - 98),
                        'placeholder' => [
                            'year' => 'yyyy', 'month' => 'mm', 'day' => 'dd',
                        ]
                    ])
                    ->add('nationality', ChoiceType::class, [
                        'choices' => Util::getAllCountries('name', 'name'), 'preferred_choices' => ['United Kingdom']

                    ])
                    ->add('info', UserCustomInfoForm::class)
                    ->add('address', UserAddressForm::class)
                    ->add('phone1', TelType::class)
                    ->add('phone2', TelType::class, ['required' => false])
                    ->add('document', UserDocumentForm::class);
        }
    }

    public function getBlockPrefix(): string
    {
        return 'userInformation';
    }
}
