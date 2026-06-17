<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class CategoryRestrictedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('last12M', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 10,
                    'placeholder' => 'e.g. 1',
                ],
                'constraints' => [
                    new GreaterThanOrEqual(0),
                    new LessThanOrEqual(10),
                ],
                'label' => 'Over the last 12 months, roughly what percentage (to the nearest whole percent) of your net assets have you invested in high-risk investments?',
                'required' => true,
            ])
            ->add('next12M', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'max' => 10,
                    'placeholder' => 'e.g. 1',
                ],
                'constraints' => [
                    new GreaterThanOrEqual(0),
                    new LessThanOrEqual(10),
                ],
                'label' => 'In the next 12 months, roughly what percentage (to the nearest whole percent) of your net assets do you intend to invest in high-risk investments?',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
