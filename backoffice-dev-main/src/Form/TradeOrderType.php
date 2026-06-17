<?php

namespace App\Form;

use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeStatus;
use App\Entity\TradeOrder;
use App\Form\DataTransformer\TradeOrderToNumberTransformer;
use App\Form\DataTransformer\TransactionToNumberTransformer;
use App\Form\Type\AssetSelectorUnfilteredType;
use App\Form\Type\BcMoneyType;
use App\Form\Type\UserSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TradeOrderType extends AbstractType
{
    public function __construct(
        private TransactionToNumberTransformer $transactionTransformer,
        private TradeOrderToNumberTransformer $tradeOrderTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('direction', EnumType::class, [
                'class' => TradeDirection::class,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice->name;
                },
                'help' => 'Is this a buy or a sell order?',
                'placeholder' => 'Choose a direction',
            ])
            ->add('type', EnumType::class, [
                'class' => \App\Entity\Enum\TradeOrderType::class,
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords(str_replace('_', ' ', $choice->value));
                },
                // 'expanded' => true,
                'help' => 'What type of trade order is it',
                'placeholder' => 'Choose a type',
            ])
            ->add('user', UserSelectorType::class, [
                'help' => 'Ensure this user has shareholdings in the asset you choose',
                'placeholder' => 'Choose an User',
                'required' => true,
            ])
            ->add('asset', AssetSelectorUnfilteredType::class, [
                'placeholder' => 'Choose an Asset',
                'required' => true,
            ])
            ->add('numberOfShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 502',
                ],
                'help' => 'Number of shares to buy or sell',
            ])
            ->add('pricePerShare', BcMoneyType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 3.64',
                ],
                'help' => 'Up to 6 decimal places (1/10000th of a penny) is supported, but you should use 2 (whole pennies) where possible.',
                'input' => 'string',
                'scale' => 6,
            ])
            ->add('fees', BcMoneyType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 3.64',
                ],
                'help' => 'Defaults to 0. Any fees taken or expected to be taken for this trade order/request',
                'input' => 'string',
                'scale' => 2,
            ])
            ->add('taxes', BcMoneyType::class, [
                'attr' => [
                    'placeholder' => 'E.g. 3.64',
                ],
                'help' => 'Defaults to 0. Any relevant taxes paid or expected to be taken. E.g. stamp duty',
                'input' => 'string',
                'scale' => 2,
            ])
            ->add('minimumShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 154',
                ],
                'help' => '(Optional) Only applies to sell orders. Minimum permitted shareholding.',
                'required' => false,
            ])
            ->add('maximumShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1056',
                ],
                'help' => '(Optional) Only applies to sell orders. Maximum permitted shareholding.',
                'required' => false,
            ])
            ->add('transaction', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 875',
                ],
                'help' => '(Optional) Yielders transaction record id associated with this order',
                'label' => 'Transaction Id',
                'required' => false,
            ])
            ->add('transactionReference', TextType::class, [
                'attr' => [
                    'placeholder' => 'xfer_test_12456',
                ],
                'help' => '(Optional)  Transaction reference id, should match transaction if set. Recommended to set something for off-market investments/trades.',
                'required' => false,
            ])
            ->add('expiration', DateTimeType::class, [
                'help' => '(Optional) Not in used yet. The date and time the order should expire and subsequently cancel if not completed.',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('notes', TextType::class, [
                'attr' => [
                    'placeholder' => 'State why this trade order is being created',
                ],
                'help' => '(Optional) Recommended for custom and/or manual trades orders',
                'required' => false,
            ])
            ->add('complementaryOrder', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 4412',
                ],
                'help' => '(Optional) Which trade order (by id) represents (prefunding) complementary order. They should be opposite directions.',
                'label' => 'Prefunding Complementary Order Id',
                'required' => false,
            ]);
        if ($builder->getData()->getId() === null) {
            $builder->add('status', EnumType::class, [
                'class' => TradeOrderStatus::class,
                'disabled' => $builder->getData()->getId() ? true : false,
                // 'expanded' => true,
                'help' => 'What status the trade order should be created with. Defaults to Draft.',
                'label' => 'Status',
                'mapped' => false,
                'placeholder' => 'Choose a status',
                'required' => false,
            ]);
        }

        $builder->get(
            'transaction',
        )->addModelTransformer($this->transactionTransformer);
        $builder->get(
            'complementaryOrder',
        )->addModelTransformer($this->tradeOrderTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TradeOrder::class,
        ]);
    }
}
