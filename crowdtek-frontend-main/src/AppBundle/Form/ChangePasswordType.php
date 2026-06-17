<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'required' => true,
                'invalid_message' => 'Please enter your current password.',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The password fields must match.',
                //'options' => array('attr' => array('class' => '')),
                'required' => true,
                'trim' => true,
                'first_name' => 'new_password',
                'second_name' => 'new_password_confirm',
                //                    'first_options' => array('label' => 'New Password'),
                //                    'second_options' => array('label' => 'Confirm Password'),
            ])
            ->add('submit', SubmitType::class, ['label' => 'Change Password'])
            ->getForm();
    }

    public function getBlockPrefix(): string
    {
        return 'change_password_type';
    }
}
