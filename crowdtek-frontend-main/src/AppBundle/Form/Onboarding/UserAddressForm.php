<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 3:14 PM
 */

namespace AppBundle\Form\Onboarding;

use AppBundle\Util\Util;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;

class UserAddressForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('address1', TextType::class, [
                'required' => false,
                'label' => 'Address Line 1'
            ])
            ->add('address2', TextType::class, [
                'required' => false,
                'label' => 'Address Line 2'
            ])
            ->add('address3', TextType::class, [
                'required' => false,
                'label' => 'Address Line 3'
            ])
            ->add('country', ChoiceType::class, [
                'required' => false,
                'choices' => Util::getAllCountries('name', 'name'), 'preferred_choices' => ['United Kingdom']
            ])->add('city', TextType::class, [
                'required' => false,
                'label' => "Town / City"
            ])
            ->add('postal_code', TextType::class, [
                'required' => false
            ])
            ->add('region', HiddenType::class, [
                'required' => false,
                'data' => "UNKNOWN"
            ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'user_address_form_type';
    }
}
