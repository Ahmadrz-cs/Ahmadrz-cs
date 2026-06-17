<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeeDeriveGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('feeDescription', TextType::class, [
            'help' => 'Name of the fee. Use for the fee collection transfer description.',
            'required' => true,
        ])->add('percentageCut', PercentType::class, [
            'help' => 'The percentage cut to take. Up to 2 decimal places.',
            'required' => true,
            'scale' => 2,
        ])->add('feeWalletId', ChoiceType::class, [
            'choices' => $options['feeWalletChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return "{$key} ({$value})";
            },
            'help' => 'Wallet to be credited by the fee transfers',
            'label' => 'Fee Collection Wallet',
            'required' => true,
        ])->add('preview', SubmitType::class, [
            'label' => 'Update Preview',
        ])->add('generate', SubmitType::class, [
            'label' => 'Generate All Transfers',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'feeWalletChoices' => [],
        ]);
    }
}
