<?php

namespace App\Form;

use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Form\DataTransformer\TradeOrderToNumberTransformer;
use App\Form\Type\BcMoneyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShareTradeType extends AbstractType
{
    public function __construct(
        private TradeOrderToNumberTransformer $tradeOrderTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sellOrder', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 875',
            ],
            'help' => 'Which trade order (by id) represents the seller',
            'label' => 'Sell Order Id',
        ])->add('buyOrder', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 4412',
            ],
            'help' => 'Which trade order (by id) represents the buyer',
            'label' => 'Buy Order Id',
        ])->add('numberOfShares', IntegerType::class, [
            'attr' => [
                'placeholder' => 'e.g. 502',
            ],
            'help' => 'Number of shares in this trade',
        ])->add('pricePerShare', BcMoneyType::class, [
            'attr' => [
                'placeholder' => 'E.g. 3.64',
            ],
            'help' => 'Up to 6 decimal places (1/10000th of a penny) is supported, but you should use 2 (whole pennies) where possible.',
            'input' => 'string',
            'scale' => 6,
        ])->add('tradeValue', BcMoneyType::class, [
            'attr' => [
                'placeholder' => 'E.g. 3.64',
            ],
            'help' => 'Set this to a negative number to derive (auto-calculate) the total from the share price and quantity. Up to 2 decimal places is supported (whole pennies).',
            'input' => 'string',
            'scale' => 2,
        ]);
        if ($builder->getData()->getId() === null) {
            $builder->add('status', EnumType::class, [
                'class' => TradeStatus::class,
                'disabled' => $builder->getData()->getId() ? true : false,
                // 'expanded' => true,
                'help' => 'What status the share trade should be created with. Defaults to Draft.',
                'label' => 'Status',
                'mapped' => false,
                'placeholder' => 'Choose a status',
                'required' => false,
            ]);
        }

        $builder->get('sellOrder')->addModelTransformer($this->tradeOrderTransformer);
        $builder->get('buyOrder')->addModelTransformer($this->tradeOrderTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShareTrade::class,
        ]);
    }
}
