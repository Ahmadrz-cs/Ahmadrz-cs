<?php

namespace App\Form\Type;

use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class MonthendOrderEditType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $transferOrderStateMachine,
        private WorkflowInterface $paymentOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (TransferOrder::class === $options['data_class']) {
            $options['restricted'] = !$this->transferOrderStateMachine->can(
                $builder->getData(),
                'approve',
            );
        } elseif (PaymentOrder::class === $options['data_class']) {
            $options['restricted'] = !$this->paymentOrderStateMachine->can(
                $builder->getData(),
                'approve',
            );
        } else {
            $options['restricted'] = true;
        }

        $builder->add('scheduledFor', DateType::class, [
            'disabled' => $options['restricted'],
            'help' => 'Monthends are always done in retrospect. E.g. 2020-05 will cover activities in the previous calendar month 2020-04 (the period between 2020-04-01 and 2020-04-30).',
            'label' => 'Scheduled monthend',
            'widget' => 'single_text',
        ])->add('description', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Describe what the order is for',
                'style' => 'min-height:3rem;',
            ],
            'help' => 'Customise the description for any exceptional circumstances',
            'label' => 'Description',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
