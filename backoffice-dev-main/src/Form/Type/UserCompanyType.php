<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 23/02/17
 * Time: 21:38
 */

namespace App\Form\Type;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['required' => false])
            ->add('buildingName', TextType::class, ['required' => false])
            ->add('registrationNumber', TextType::class, ['required' => false])
            ->add('otherName', TextType::class, ['required' => false])
            ->add('businessNature', TextType::class, ['required' => false])
            ->add('regAddress1', TextType::class, ['required' => false])
            ->add('regAddress2', TextType::class, ['required' => false])
            ->add('regAddress3', TextType::class, ['required' => false])
            ->add('regCountry', CountryType::class, [
                'placeholder' => 'please select',
                'required' => false,
            ])
            ->add('postCode', TextType::class, ['required' => false])
            ->add('telephone', TextType::class, ['required' => false])
            ->add('beneficialOwners', TextType::class, ['required' => false])
            ->add('directors', TextType::class, ['required' => false])
            ->add('companyWebsite', TextType::class, ['required' => false])
            ->add('operatingAddress', TextType::class, ['required' => false])
            ->add('operatingPostCode', TextType::class, ['required' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
