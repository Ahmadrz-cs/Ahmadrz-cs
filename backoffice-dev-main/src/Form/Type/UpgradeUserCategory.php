<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class UpgradeUserCategory extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 2461',
                ],
                'help' => 'Leave empty to upgrade more than 1 user',
                'label' => 'User Id',
                'required' => false,
            ])
            ->add('amount', IntegerType::class, [
                'attr' => [
                    'min' => 1,
                    'max' => 25,
                    'placeholder' => 'e.g. 12',
                ],
                'constraints' => [
                    new GreaterThanOrEqual(1),
                    new LessThanOrEqual(25),
                ],
                'help' => 'Minimum of 1. Maximum of 25. This will always be 1 if you set a user id',
                'label' => 'Number of Users to Upgrade',
                'required' => true,
            ])
            ->getForm();
    }
}
