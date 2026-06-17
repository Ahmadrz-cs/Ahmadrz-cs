<?php

namespace App\Form;

use App\Entity\Enum\OrderStatus;
use App\Form\Type\AbstractQueryType;
use App\Form\Type\PaginationLimitType;
use App\Form\Type\PaginationOrderByType;
use App\Form\Type\PaginationOrderDirectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryShareTransferOrderForm extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Transfer Order Id',
                'required' => false,
            ])
            ->add('assetId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('assetName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. lodge',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($value);
                },
                'class' => OrderStatus::class,
                'expanded' => true,
                'help' => 'All empty is equivalent to "any"',
                'multiple' => true,
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
