<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('birth_date', TextType::class)
            ->add('tax_id', TextType::class)
            ->add('phone_1', TextType::class)
            ->add('localtion', HiddenType::class)
            ->getForm();
    }

    public function getBlockPrefix(): string
    {
        return 'profile_type';
    }
}
