<?php

namespace App\Form\Type;

use App\Entity\AssetAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address1', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 123 Building Name or 123 Street Name',
                ],
                'help' => 'Number or name of the property and the building name or street address',
                'label' => 'Address line 1',
                'required' => true,
            ])
            ->add('address2', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Street Name',
                ],
                'help' => 'Only use if address line 1 is not a street address',
                'label' => 'Address line 2',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. London',
                ],
                'required' => true,
            ])
            ->add('postCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. N1 B23',
                ],
                'required' => true,
            ])
            ->add('country', CountryType::class, [
                'preferred_choices' => ['GB'],
                'required' => true,
            ])
            ->add('latitude', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 50.12345',
                ],
                'help' => 'Degrees North or South of the equator. UK is around 50 degrees',
                'required' => true,
            ])
            ->add('longitude', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. -0.12345',
                ],
                'help' => 'Degrees East or West of the prime meridian. UK is around 0 degrees',
                'required' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetAddress::class,
        ]);
    }
}
