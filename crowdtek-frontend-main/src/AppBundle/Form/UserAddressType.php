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
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAddressType extends AbstractType
{
    private $user;

    private function getAllCountries()
    {
        \Locale::setDefault('en');
        $countries = Countries::getNames();
        $arr = [];
        if (is_array($countries)) {
            $arr = array_combine($countries, $countries);
        } else {
            $arr = ['United Kingdom' => 'United Kingdom']; //return common countries
        }
        return $arr;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $options['user'];

        $builder
            ->add('building', TextType::class, [
                'required' => false,
                'data' => isset($this->user['address']['building']) ? $this->user['address']['building'] : ''
            ])
            ->add('street_address', TextType::class, [
                'required' => false,
                'data' => isset($this->user['address']['street_address']) ? $this->user['address']['street_address'] : ''
            ])
            ->add('country', ChoiceType::class, [
                'required' => false,
                'choices' => $this->getAllCountries(), 'preferred_choices' => ['United Kingdom'],
                'data' => isset($this->user['address']['country']) ? $this->user['address']['country'] : ''
            ])->add('city', TextType::class, [
                'required' => false,
                'data' => isset($this->user['address']['city']) ? $this->user['address']['city'] : ''
            ])
            ->add('postal_code', TextType::class, [
                'required' => false,
                'data' => isset($this->user['address']['postal_code']) ? $this->user['address']['postal_code'] : ''
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => null,
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
