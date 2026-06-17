<?php

namespace App\Form\Type;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetFinancialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pricePerShare', MoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1.23',
                ],
                'currency' => 'GBP',
                'help' => 'Finest granularity is a whole pence. Any fractional pence will be rounded',
                'label' => 'Share Price',
                'required' => false,
                'scale' => 2,
            ])
            ->add('amountOfShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 100000',
                ],
                'label' => 'Number of shares issued',
                'required' => false,
            ])
            ->add('investmentTerm', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 60',
                ],
                'help' => 'Combines with investment term start to determine an expected end date.',
                'label' => 'Investment term length (months)',
                'required' => false,
            ])
            ->add('netProjectedIncome', MoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12050.60',
                ],
                'currency' => 'GBP',
                'help' => 'The (rental) income investors should expect to receive per year after all deductions. Expressed as a monetary value. Used to derive the percentage netProjectedYield.',
                'required' => false,
            ])
            ->add('financialYearStart', DateType::class, [
                'help' => 'The year does not matter. Only the month and day. Used for accounting purposes only.',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('termStart', DateType::class, [
                'help' => "When the investment term officially starts. Used in combination with 'Investment Term' to derive the term duration and end date.",
                'label' => 'Investment term start',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('minimumInvestment', BcMoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 100',
                ],
                'help' => 'The default minimum investment permitted. Can be overriden by individual Trade Orders.',
                'label' => 'Minimum Single Commit/Investment (£)',
                'required' => false,
                'scale' => 2,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
