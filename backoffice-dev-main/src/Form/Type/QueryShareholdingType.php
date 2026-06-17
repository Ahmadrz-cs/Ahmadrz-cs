<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryShareholdingType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assetId', NumberType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 8',
                ],
                'required' => false,
            ])
            ->add('userId', NumberType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 32',
                ],
                'required' => false,
            ])
            ->add('currentHolding', ChoiceType::class, [
                'choices' => [
                    'Current/Active' => 1,
                    'Historical' => 0,
                ],
                'empty_data' => null,
                'help' => 'Show only current, historical or all shareholders',
                'label' => 'Shareholder Type',
                'placeholder' => 'All',
                'required' => false,
            ])
            ->add('capitalRepayments', CheckboxType::class, [
                'help' => 'Only show shareholdings with capital repayments > 0',
                'label' => 'Has Repaid Shareholding',
                'required' => false,
            ])
            ->getForm();
    }
}
