<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeeSearchGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('scheduledFor_gte', DateType::class, [
            'help' => 'This date is inclusive (>= this date)',
            'input' => 'datetime_immutable',
            'label' => "{$options['dateDescription']} Start",
            'required' => true,
            'widget' => 'single_text',
        ])->add('scheduledFor_lt', DateType::class, [
            'help' => 'This date is exclusive (< this date)',
            'input' => 'datetime_immutable',
            'label' => "{$options['dateDescription']} End",
            'required' => true,
            'widget' => 'single_text',
        ])->add('feeWalletId', ChoiceType::class, [
            'choices' => $options['feeWalletChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return "{$key} ({$value})";
            },
            'help' => 'Wallet to be credited by the fee transfers',
            'label' => 'Fee Collection Wallet',
            'required' => true,
        ])->add('search', SubmitType::class, [
            'label' => 'Search',
        ])->add('generate', SubmitType::class, [
            'label' => 'Generate All Transfers',
        ]);

        if ($options['filterByDescription']) {
            $builder->add('description', TextType::class, [
                'attr' => [
                    'list' => 'description-presets',
                    'placeholder' => 'Type a custom transfer description or choose a preset if available',
                ],
                'help' => 'Must be an exact match',
                'label' => 'Description',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'feeWalletChoices' => [],
            'dateDescription' => 'Transfers Scheduled',
            'filterByDescription' => false,
        ]);
    }
}
