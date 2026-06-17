<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductLaunchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('numberOfShares', IntegerType::class, [
            'attr' => [
                'placeholder' => 'e.g. 502',
            ],
            'help' => 'Number of shares to buy or sell. Defaults to the asset shares available.',
        ])->add('pricePerShare', BcMoneyType::class, [
            'attr' => [
                'placeholder' => 'E.g. 3.64',
            ],
            'help' => 'Share price to list at. Defaults to the asset share price.',
            'input' => 'string',
            'scale' => 2,
        ])->add('minimumInvestment', BcMoneyType::class, [
            'attr' => [
                'placeholder' => 'E.g. 3.64',
            ],
            'help' => 'Minimum investment amount (monetary value). Defaults to asset minimum investment (or £100 if not configured) for retail, and £25000 for prefunding.',
            'input' => 'string',
            'scale' => 2,
        ])->add('maximumInvestment', BcMoneyType::class, [
            'attr' => [
                'placeholder' => 'E.g. 3.64',
            ],
            'help' => 'Maximum investment amount (monetary value). Defaults to the asset funding goal (or shares to repay if switching from prefunding to retail).',
            'input' => 'string',
            'scale' => 2,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
