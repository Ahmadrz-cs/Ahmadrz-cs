<?php

namespace App\Form\Type;

use App\Entity\Enum\AllocationMethod;
use App\Entity\Enum\PaymentType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentOrderGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (in_array($options['paymentType'], [
            PaymentType::Dividend,
            PaymentType::Divestment,
            PaymentType::InvestmentExit,
            PaymentType::Liquidation,
        ])) {
            $builder->add('amount', MoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 180.45',
                ],
                'currency' => 'GBP',
                'label' => 'Amount to Pay Shareholders',
                'scale' => 2,
            ]);
        }
        if (in_array($options['paymentType'], [
            PaymentType::Divestment,
            PaymentType::Liquidation,
            PaymentType::Repayment,
        ])) {
            $builder->add('shares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1000',
                ],
                'help' => 'This will be reduced to the shares in circulation if it is higher than it',
                'label' => 'Shares to liquidate',
            ]);
        }
        if (in_array($options['paymentType'], [PaymentType::Dividend])) {
            $builder->add('method', EnumType::class, [
                'class' => AllocationMethod::class,
                'empty_data' => 'accrue',
                'expanded' => true,
                'help' => 'What should happen to the leftovers after rounding down? Accrue for future payout, or distribute remainder in this payout?',
                'label' => 'Allocation Method',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'paymentType' => null,
        ]);
    }
}
