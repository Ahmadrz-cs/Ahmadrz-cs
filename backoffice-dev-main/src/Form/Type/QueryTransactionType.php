<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryTransactionType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Asset Id',
                'required' => false,
            ])
            ->add('external_id', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. TRNSF24682468',
                ],
                'help' => 'The wallet provider (MangoPay) transfer reference',
                'label' => 'Transfer Reference',
                'required' => false,
            ])
            ->add('debited_wallet_id', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. MPW24682468',
                ],
                'label' => 'Debit Wallet',
                'required' => false,
            ])
            ->add('credited_wallet_id', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. MPW24682468',
                ],
                'label' => 'Credit Wallet',
                'required' => false,
            ])
            ->add('comments', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Management Fee',
                ],
                'help' => 'Does a rough/fuzzy string match (slow) on comments field',
                'label' => 'Comments/Description/Notes',
                'required' => false,
            ])
            ->add('payment_status', ChoiceType::class, [
                'choices' => [
                    'SUCCEEDED',
                    'CREATED',
                    'FAILED',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst(strtolower($value));
                },
                'label' => 'Status',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class)
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
