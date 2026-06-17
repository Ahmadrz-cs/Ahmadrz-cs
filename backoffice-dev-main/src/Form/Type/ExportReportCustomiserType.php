<?php

namespace App\Form\Type;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExportReportCustomiserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldChoices = $options['fieldChoices'];
        $reportSpecificFilters = $options['reportSpecificFilters'];

        $builder->add('reportFields', ChoiceType::class, [
            'choices' => $fieldChoices,
            'choice_attr' => function ($choice, $key, $value) {
                return ['data-table-column' => $value];
            },
            'choice_label' => function ($choice, $key, $value) {
                if (is_string($key)) {
                    return $key;
                }
                return ucfirst($value);
            },
            'expanded' => true,
            'help' => 'No fields chosen is equivalent to including all fields. Not all fields available',
            'label' => 'Choose Fields/Columns in Report',
            'multiple' => true,
            'required' => false,
        ])->add('exportFormat', ChoiceType::class, [
            'choices' => [
                'csv',
                'xls',
                'json',
            ],
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
        ]);

        if (in_array('createdAt', $fieldChoices)) {
            $builder->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ]);
        }

        if (in_array('assetId', $fieldChoices)) {
            $builder->add('assetId', AssetSelectorType::class, [
                'placeholder' => 'All Assets',
                'required' => false,
            ]);
        }

        foreach ($options['userFields'] as $fieldName) {
            if (in_array($fieldName, $fieldChoices)) {
                $builder->add($fieldName, IntegerType::class, [
                    'attr' => [
                        'placeholder' => 'E.g. 12',
                    ],
                    'required' => false,
                ]);
            }
        }

        if (in_array('tradeStatus', $reportSpecificFilters)) {
            $builder->add('status', EnumType::class, [
                'class' => TradeStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ]);
        }
        if (in_array('tradeOrderStatus', $reportSpecificFilters)) {
            $builder->add('status', EnumType::class, [
                'class' => TradeOrderStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ]);
        }
        if (in_array('tradeOrderType', $reportSpecificFilters)) {
            $builder->add('type', EnumType::class, [
                'class' => TradeOrderType::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ]);
        }
        if (in_array('direction', $reportSpecificFilters)) {
            $builder->add('direction', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name;
                },
                'class' => TradeDirection::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'fieldChoices' => [],
            'userFields' => [],
            'reportSpecificFilters' => [],
        ]);
    }
}
