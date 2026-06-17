<?php

namespace App\Form\Type;

use App\Entity\Enum\PaymentType;
use App\Entity\PaymentRequest;
use App\Entity\User;
use App\Form\DataTransformer\TradeOrderToNumberTransformer;
use App\Service\PaymentService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentRequestType extends AbstractType
{
    public function __construct(
        private TradeOrderToNumberTransformer $tradeOrderTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('payee', EntityType::class, [
            'choice_label' => function ($user) {
                return '#' . $user->getId() . ' // ' . $user->getUserName();
            },
            'class' => User::class,
            'help' => PaymentService::TYPE_REPAYMENT == $builder
                ->getData()
                ->getPaymentOrder()
                ->getPaymentType()
                ? 'Only prefunders available'
                : 'Only shareholders available',
            'placeholder' => 'Choose a shareholder to pay',
            'query_builder' => function (EntityRepository $er) use ($options) {
                $qb = $er->createQueryBuilder('u');
                $qb->andWhere($qb->expr()->in('u.id', ':userIds'))->setParameter(
                    'userIds',
                    $options['shareholderIds'],
                );
                return $qb;
            },
        ])->add('amount', MoneyType::class, [
            'attr' => [
                'placeholder' => '180.45',
            ],
            'currency' => 'GBP',
            'label' => 'Amount to Pay',
            'scale' => 2,
        ]);

        // BUG: Adding transfers for capital repayments requires an amount
        // Need to fix in PaymentOrderController:addPayment
        // if (!in_array($builder->getData()->getPaymentOrder()->getPaymentType(), [
        //     PaymentType::Repayment->value,
        // ])) {
        //     $builder->add('amount', MoneyType::class, [
        //         'attr' => [
        //             'placeholder' => '180.45',
        //         ],
        //         'currency' => 'GBP',
        //         'label' => 'Amount to Pay',
        //         'scale' => 2,
        //     ]);
        // }

        if (!in_array($builder->getData()->getPaymentOrder()->getPaymentType(), [
            PaymentType::Dividend->value,
        ])) {
            $builder->add('shareholding', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 283',
                ],
                'help' => $builder->getData()->getId()
                    ? 'This will be reduced to their full shareholding if it is set higher than it'
                    : 'Leave this empty to auto-populate with their full shareholding',
                'label' => PaymentService::TYPE_DIVIDEND == $builder
                    ->getData()
                    ->getPaymentOrder()
                    ->getPaymentType()
                    ? 'Shareholding'
                    : 'Shares to Liquidate',
                'required' => $builder->getData()->getId() ? true : false,
            ]);
        }

        if (in_array($builder->getData()->getPaymentOrder()->getPaymentType(), [
            PaymentType::Repayment->value,
        ])) {
            $builder->add('tradeOrder', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 4412',
                ],
                'help' => 'Which trade order (by id) represents the prefunding sell order',
                'label' => 'Trade Order Id',
                'required' => true,
            ]);
            $builder->get(
                'tradeOrder',
            )->addModelTransformer($this->tradeOrderTransformer);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentRequest::class,
            'shareholderIds' => [],
        ]);
    }
}
