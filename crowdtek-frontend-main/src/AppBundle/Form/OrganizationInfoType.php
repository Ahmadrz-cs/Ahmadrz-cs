<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 3:14 PM
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;

class OrganizationInfoType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('funding_goal', NumberType::class, [
                'required' => true
            ])
            ->add('amount_of_shares', NumberType::class, [
                'required' => true,
                'data' => 1000000
            ])
            //                ->add('price_per_share', 'text', array(
            //                    'required' => false
            //                ))
            ->add('setup_fee', NumberType::class, [
                'required' => false,
                'data' => 0
            ])
            ->add('admin_fee', NumberType::class, [
                'required' => true,
                'data' => 50
            ])
            ->add('management_fee', NumberType::class, [
                'required' => false,
                'data' => 10
            ])
            ->add('profit_share', NumberType::class, [
                'required' => false,
                'data' => 15
            ])
            ->add('stamp_duty_user', EmailType::class, [
                'required' => true,
                'attr' => ['placeholder' => 'e.g. stampduty@yielders.co.uk']
            ])
            ->add('asset_type', ChoiceType::class, [
                'choices' => [
                    'Commercial' => 'Commercial',
                    'Residential' => 'Residential'
                ]
            ])
            ->add('gross_yield', TextType::class, [
                'required' => false
            ])
            ->add('investment_term', NumberType::class, [
                'required' => true
            ])
            //->add('gross_roi', 'hidden')
            //->add('net_roi', 'hidden')
            ->add('gross_rental_return_pa', HiddenType::class)
            ->add('net_rental_return_pa', HiddenType::class)
            ->add('gross_capital_appreciation', HiddenType::class)
            ->add('net_capital_appreciation', HiddenType::class)
            ->add('net_capital_appreciation_yield', HiddenType::class)
            //->add('net_yield_per_month', 'hidden')
            ->add('points_of_interest', HiddenType::class)
            ->add('blocked_for_sale', CheckboxType::class, [
                'label' => 'Blocked for sale',
                'required' => false,
            ]);
    }

    // /**
    //  * Returns the name of this type.
    //  *
    //  * @return string The name of this type
    //  */
    // public function getBlockPrefix(): string
    // {
    //     //return 'organization_address_form_type';
    // }
}
