<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IdentityVerificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('givenNames', TextType::class, [
                'disabled' => true,
                'help' => 'Must be as written in your identity document',
                'required' => false,
            ])
            ->add('lastName', TextType::class, [
                'disabled' => true,
                'help' => 'Also called surname. Must be as written in your identity document',
                'required' => false,
            ])
            ->add('nationality', TextType::class, [
                'disabled' => true,
                'help' => 'Usually the same as the country that issued your identity document',
                'required' => false,
            ])
            ->add('dateOfBirth', DateType::class, [
                'disabled' => true,
                'help' => 'In the format YYYY-MM-DD, e.g. October 4th 2002 will be shown as 2002-10-04',
                'required' => false,
                'html5' => false,
                'widget' => 'single_text',
            ])
            ->add('identityDocument', FileType::class, [
                'help' => 'Accepted file formats: PNG, PDF, JPG, JPEG. Recommended file size: between 150KB and 5MB',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
