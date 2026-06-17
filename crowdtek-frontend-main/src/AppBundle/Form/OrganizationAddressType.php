<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 3:14 PM
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;

class OrganizationAddressType extends AbstractType
{
    /**
     * @return array
     */
    private function getAllCountries()
    {
        \Locale::setDefault('en');
        $countries = Countries::getNames();
        $arr = [];
        if (is_array($countries)) {
            $arr = array_combine($countries, $countries);
        } else {
            $arr = ['United States' => 'United States', 'United Kingdom' => 'United Kingdom']; //return common countries
        }
        return $arr;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('street_address', TextType::class, [
            'required' => false,
        ])->add('address_2', TextType::class, [
            'required' => false,
        ])->add('address_3', TextType::class, [
            'required' => false,
        ])->add('city', TextType::class, [
            'required' => false,
        ])->add('postal_code', TextType::class, [
            'required' => false,
        ])->add('country', ChoiceType::class, [
            'required' => true,
            'choices' => $this->getAllCountries(), 'preferred_choices' => ['United States', 'United Kingdom']
        ])->add('region', ChoiceType::class, [
            'required' => true,
            'choices' => $this->getRegions(), 'preferred_choices' => ['London', 'London']
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'organization_address_form_type';
    }

    /**
     * Get list of regions
     * @return array
     */
    private function getRegions()
    {
        $arr = [];
        $arr['East Midlands'] = 'East Midlands';
        $arr['East of England'] = 'East of England';
        $arr['London'] = 'London';
        $arr['North East'] = 'North East';
        $arr['North West'] = 'North West';
        $arr['South East'] = 'South East';
        $arr['South West'] = 'South West';
        $arr['West Midlands'] = 'West Midlands';
        $arr['Yorkshire and the Humber'] = 'Yorkshire and the Humber';
        return $arr;
    }
}
