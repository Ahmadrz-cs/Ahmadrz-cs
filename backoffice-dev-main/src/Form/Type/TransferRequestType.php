<?php

namespace App\Form\Type;

use App\Entity\TransferRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class TransferRequestType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $transferOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Restrictions are based on the order that wraps the request
        // Field locking is related to the transfer order state
        // Field restrictions are a separate option passed during form creation
        $options['locked'] = !$this->transferOrderStateMachine->can(
            $builder->getData()->getTransferOrder(),
            'approve',
        );
        $builder->add('debitWalletId', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. MPW24682468',
            ],
            'disabled' => $options['restricted'] || $options['locked'],
            'help' => 'Id of debiting wallet',
            'label' => 'Debit Wallet',
        ])->add('creditWalletId', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. MPW24682468',
            ],
            'disabled' => $options['restricted'] || $options['locked'],
            'help' => 'Id of crediting wallet',
            'label' => 'Credit Wallet',
        ])->add('description', TextareaType::class, [
            'attr' => [
                'placeholder' => 'e.g. Accountancy fees',
                'style' => 'min-height:3rem;',
            ],
            'help' => 'Describe what the transfer is for. This is used as a transfer reference. Max 240 characters.',
        ])->add('amount', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 1.23',
            ],
            'disabled' => $options['locked'],
            'label' => 'Amount to Pay (£)',
            'scale' => 2,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferRequest::class,
            'restricted' => false,
        ]);
    }
}
