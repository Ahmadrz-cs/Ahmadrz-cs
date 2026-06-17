<?php

namespace App\Form\Type;

use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UuidType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryTradeType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Share trade id',
                'required' => false,
            ])
            ->add('uuid', UuidType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 019d4349-0fff-7e2d-9765-a67d3fa2df5c',
                ],
                'help' => 'UUIDs should be with the dashes. Must be exact match.',
                'label' => 'Share trade uuid',
                'required' => false,
            ])
            ->add('sellOrder', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 875',
                ],
                'help' => 'Sell side trade order',
                'label' => 'Sell order id',
                'required' => false,
            ])
            ->add('buyOrder', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 4412',
                ],
                'help' => 'Buy side trade order',
                'label' => 'Buy order id',
                'required' => false,
            ])
            ->add('sellerId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'help' => 'Sell side user',
                'required' => false,
            ])
            ->add('sellerUsername', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('buyerId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'help' => 'Buy side user',
                'required' => false,
            ])
            ->add('buyerUsername', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('sellOrderType', EnumType::class, [
                'class' => TradeOrderType::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ])
            ->add('buyOrderType', EnumType::class, [
                'class' => TradeOrderType::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => TradeStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
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
            ->add('orderBy', PaginationOrderByType::class, [
                'data' => 'createdAt',
                'empty_data' => 'createdAt',
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->add('export', HiddenType::class, [
                'empty_data' => 0,
            ]);

        if ($options['asset_filters']) {
            $builder->add('assetId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])->add('assetName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. lodge',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ]);
        }
        if ($options['user_filters']) {
            $builder->add('userId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'help' => 'User that appears on EITHER buy OR sell side',
                'required' => false,
            ]);
        }
    }
}
