<?php

namespace App\Form\Type;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address1', TextType::class, ['required' => false])
            ->add('address2', TextType::class, ['required' => false])
            ->add('address3', TextType::class, ['required' => false])
            ->add('city', TextType::class, ['required' => false])
            ->add('region', TextType::class, ['required' => false])
            ->add('postCode', TextType::class, ['required' => false])
            ->add('country', CountryType::class, [
                'preferred_choices' => ['GB'],
                'placeholder' => 'Please select',
                'required' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
