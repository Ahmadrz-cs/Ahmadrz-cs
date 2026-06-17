<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 2:15 PM
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomInfo2Type extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('net_rent_projected', TextType::class, [
            'required' => false
        ])->add('gross_projected_return', TextType::class, [
            'required' => false
        ])->add('county', ChoiceType::class, [
            'required' => false, 'label' => 'Choose your county',
            'choices' => [
                "Scotland" => "Scotland",
                "Midlands, North, Wales" => "Midlands, North, Wales",
                "Wider South England" => "Wider South England",
                "Outer Commute" => "Outer Commute",
                "Inner commute" => "Inner Commute",
                "Prime Suburbs" => "Prime Suburbs",
                "Prime London" => "Prime London",
            ]
        ])->add('group_investor', TextType::class, [
            'required' => false
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'custom_info2_type';
    }
}
