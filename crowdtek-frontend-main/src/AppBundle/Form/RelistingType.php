<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RelistingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numberOfShares', IntegerType::class, [
                'constraints' => [
                    new Assert\Range(
                        min: (int)$options['minimum'],
                        max: (int)$options['available'],
                        notInRangeMessage: 'Number of shares to sell must be between {{ min }} and {{ max }} shares',
                    ),
                ],
                'help' => "Minimum of {$options['minimum']} shares",
                'label' => 'Number of Shares',
                'required' => true,
            ])
            ->add('acceptTerms', CheckboxType::class, [
                'label' => 'I have read and accept the terms and conditions for selling on the Yielders Platform',
                'required' => true,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'minimum' => 0,
            'available' => 0,

        ]);
    }
}
