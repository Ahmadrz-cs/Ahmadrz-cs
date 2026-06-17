<?php

namespace App\Form\Type;

use App\Entity\TransferRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IncomeTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('debitWalletId', HiddenType::class, [
            'disabled' => true,
            'required' => true,
        ])->add('creditWalletId', ChoiceType::class, [
            'choices' => $options['creditWalletChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($key);
            },
            'expanded' => true,
            'label' => 'Transfer To',
            'label_attr' => [
                'class' => 'radio-inline',
            ],
            'placeholder' => 'Choose a wallet to transfer to',
            'required' => true,
        ])->add('description', TextType::class, [
            'attr' => [
                'list' => 'description-presets',
                'placeholder' => 'Type a custom transfer description or choose a preset',
            ],
            'help' => 'Keep this as succinct as possible. Avoid full sentences!',
            'label' => 'What is the transfer for?',
            'required' => true,
        ])->add('amountType', ChoiceType::class, [
            'choices' => [
                'absolute',
                'percentage',
            ],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'expanded' => true,
            'label' => 'Amount Type',
            'label_attr' => [
                'class' => 'radio-inline',
            ],
            'mapped' => false,
            'required' => true,
        ])->add('amount', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 124.85',
            ],
            'help' => 'Either an absolute monetary amount or a percentage of some balance',
            'label' => 'Amount to Transfer (£ or %)',
            'required' => true,
            'scale' => 2,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferRequest::class,
            'creditWalletChoices' => [],
        ]);
    }
}
