<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 2:15 PM
 */

namespace AppBundle\Form\Onboarding;

use AppBundle\Util\Util;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;

class UserCustomInfoForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //                ->add('building_number', TextType::class, array(
            //                    'required' => false,
            //                    'data' => Util::getInfo($this->user, 'building_number', '')
            //                ))
            //                ->add('building_name', TextType::class, array(
            //                    'required' => false
            //                ))
            ->add('corporate_investor', CheckboxType::class, [ // Investing through a limited company
                'label' => 'Investing through a Limited Company.',
                'required' => false
            ])
            ->add('company_name', TextType::class, [
                'required' => false
            ])
            ->add('company_registration_country', ChoiceType::class, [
                'required' => false,
                'choices' => Util::getAllCountries('name', 'name'), 'preferred_choices' => ['United Kingdom']
            ])
            ->add('company_registered_number', TextType::class, [
                'required' => false
            ])
            ->add('company_nature_of_business', TextType::class, [
                'required' => false
            ])
            ->add('company_telephone', TelType::class, [
                'required' => false
            ])

            // not required as of yielders requirement
            //                ->add('company_other_name', TextType::class, array(
            //                    'required' => false
            //                ))
            ->add('company_website', TextType::class, [
                'required' => false
            ])
            ->add('company_registered_address_1', TextType::class, [
                'required' => false
            ])
            ->add('company_registered_address_2', TextType::class, [
                'required' => false
            ])
            ->add('company_registered_address_3', TextType::class, [
                'required' => false
            ])
            ->add('company_postcode', TextType::class, [
                'required' => false
            ])
            ->add('operating_address', TextType::class, [
                'required' => false
            ])
            ->add('operating_postcode', TextType::class, [
                'required' => false
            ])
            ->add('company_beneficial_owners', CollectionType::class, [
                'required' => false,
                'entry_type' => BeneficialOwnersForm::class,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false
                ],
            ])
            ->add('company_directors', CollectionType::class, [
                'required' => false,
                'entry_type' => DirectorsForm::class,
                'allow_add' => true,
                'allow_delete' => true,
                'entry_options' => [
                    'label' => false
                ],
            ])
            ->add('referral', ChoiceType::class, [
                'choices' => [
                    'Flyer' => 'Flyer',
                    'Word of Mouth' => 'Word of Mouth',
                    'Google' => 'Google',
                    'Facebook' => 'Facebook',
                    'Linkedin' => 'Linkedin',
                    'Twitter' => 'Twitter',
                    'Instagram' => 'Instagram',
                    'Email' => 'Email',
                    'Press' => 'Press',
                    'Event' => 'Event',
                    'Youtube' => 'Youtube',
                    'TV Advert' => 'TV Advert',
                    'Other' => 'Other'
                ],
                // 'choice_attr' => function($choiceValue, $key, $value) {
                //     if (in_array($value, $this->referrers)) {
                //         return ['class' => 'hidden'];
                //     }
                //     else {
                //         return [];
                //     }
                // },
            ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'custom_info_type';
    }
}
