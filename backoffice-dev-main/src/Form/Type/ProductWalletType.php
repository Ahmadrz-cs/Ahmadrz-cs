<?php

namespace App\Form\Type;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductWalletType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('holdWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet for holding invested funds before settlement',
                'label' => 'Hold Wallet',
                'required' => false,
            ])
            ->add('settlementWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Aka the Main/Actual wallet. Used for storing funds after settlement and any income for processing (subject to separation in future)',
                'label' => 'Settlement Wallet',
                'required' => false,
            ])
            ->add('depositWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet for receiving funds from pay-ins, like gross income or loans',
                'label' => 'Deposit Wallet',
                'required' => false,
            ])
            ->add('expensesWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet for storing funds allocated to paying expenses',
                'label' => 'Expenses Wallet',
                'required' => false,
            ])
            ->add('taxWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet for storing funds allocated to paying corporation tax',
                'label' => 'Tax Wallet',
                'required' => false,
            ])
            ->add('distributionWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet for paying sharesholders, e.g. dividends or divestments',
                'label' => 'Distribution Wallet',
                'required' => false,
            ])
            ->add('treasuryWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'The wallet that acts as a treasury/reserve for the asset',
                'label' => 'Treasury Wallet',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
