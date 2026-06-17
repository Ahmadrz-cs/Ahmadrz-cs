<?php

namespace App\Form\Type;

use App\Entity\TransferOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class TransferOrderDateType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $transferOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->transferOrderStateMachine->can(
            $builder->getData(),
            'approve',
        );
        $builder->add('scheduledFor', DateType::class, [
            'disabled' => $options['restricted'],
            'help' => 'Monthends are always done in retrospect. E.g. 2020-05 will cover activities in the previous calendar month 2020-04 (the period between 2020-04-01 and 2020-04-30).',
            'label' => 'Scheduled monthend',
            'widget' => 'single_text',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferOrder::class,
        ]);
    }
}
