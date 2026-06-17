<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class UtilitiesSharePriceType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fundingGoal', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 313761',
                ],
                'help' => 'Do not include any pennies',
                'label' => 'Target Raise / Funding Goal (£)',
            ])
            ->add('sharePriceCap', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 10.21',
                ],
                'constraints' => [
                    new GreaterThanOrEqual(0.01),
                ],
                'help' => '[Optional] Defaults to the funding goal divided by 50k shares. This is internally capped at the square root of the funding goal',
                'label' => 'Maximum Permitted Share Price (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('sharePriceFloor', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1.08',
                ],
                'constraints' => [
                    new GreaterThanOrEqual(0.01),
                ],
                'help' => '[Optional] Defaults to the funding goal divided by 100k shares',
                'label' => 'Minimum Permitted Share Price (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->getForm();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
