<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryShareTradeType extends AbstractQueryType
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
            ->add('buyerId', NumberType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 16',
                ],
                'required' => false,
            ])
            ->add('sellerId', NumberType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 32',
                ],
                'required' => false,
            ])
            ->add('settledFrom', DateType::class, [
                'attr' => [
                    'placeholder' =>
                        'E.g. ' . date('Y-m-d', strtotime('first day of this month')),
                ],
                'help' => 'This date is inclusive (settled >= this date). Defaults to empty (any starting date)',
                'label' => 'Settled From',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('settledTo', DateType::class, [
                'attr' => [
                    'placeholder' =>
                        'E.g. ' . date('Y-m-d', strtotime('last day of this month')),
                ],
                'help' => 'This date is exclusive (settled < this date). Defaults to end of this month',
                'label' => 'Settled To',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('aggregate', CheckboxType::class, [
                'label' => 'Aggregated View',
                'required' => false,
            ])
            ->getForm();
    }
}
