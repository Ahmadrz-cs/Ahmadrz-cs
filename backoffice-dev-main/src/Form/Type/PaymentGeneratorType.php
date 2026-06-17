<?php

namespace App\Form\Type;

use App\Service\PaymentService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentGeneratorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('amount', NumberType::class, [
            'attr' => [
                'placeholder' => '180.45',
            ],
            'disabled' => PaymentService::TYPE_REPAYMENT == $options['paymentType'],
            'empty_data' => 0,
            'label' => 'Amount to Pay Shareholders (£)',
            'scale' => 2,
        ])->add('method', ChoiceType::class, [
            'choices' => [
                'Accrue (default)' => 'accrue',
                'Distribute' => 'distribute',
            ],
            'disabled' => PaymentService::TYPE_DIVIDEND != $options['paymentType'],
            'empty_data' => 'accrue',
            'expanded' => true,
            'help' => 'What should happen to the leftovers after rounding down?',
            'label' => 'Calculation Method',
        ])->add('shares', IntegerType::class, [
            'attr' => [
                'placeholder' => '1000',
            ],
            'disabled' => in_array($options['paymentType'], [
                PaymentService::TYPE_DIVIDEND,
                PaymentService::TYPE_INVESTMENT_EXIT,
            ]),
            'help' => 'This will be reduced to the shares in circulation if it is higher than it',
            'label' => 'Shares to liquidate',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'paymentType' => PaymentService::TYPE_DIVIDEND,
        ]);
    }
}
