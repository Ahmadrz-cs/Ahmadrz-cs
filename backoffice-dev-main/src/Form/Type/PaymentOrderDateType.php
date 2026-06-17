<?php

namespace App\Form\Type;

use App\Entity\PaymentOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class PaymentOrderDateType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $paymentOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->paymentOrderStateMachine->can(
            $builder->getData(),
            'approve',
        );
        $builder->add('scheduledFor', DateType::class, [
            'disabled' => $options['restricted'],
            'help' => 'Monthends are always done in retrospect. E.g. 2020-05 will cover activities in the previous calendar month 2020-04 (the period between 2020-04-01 and 2020-04-30).',
            'label' => 'Scheduled monthend',
            'widget' => 'single_text',
        ])->add('debitWallet', ChoiceType::class, [
            'choices' => [
                'main',
                'distribution',
            ],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'disabled' => $options['restricted'],
            'help' => 'Select the wallet that contains the funds to pay shareholders',
            'label' => 'Pay From Wallet',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentOrder::class,
        ]);
    }
}
