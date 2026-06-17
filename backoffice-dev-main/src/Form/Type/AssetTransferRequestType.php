<?php

namespace App\Form\Type;

use App\Entity\TransferRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class AssetTransferRequestType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $transferOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->transferOrderStateMachine->can(
            $builder->getData()->getTransferOrder(),
            'approve',
        );
        $builder->add('debitWalletId', ChoiceType::class, [
            'choices' =>
                $options['debitWalletChoices'] ?? $options['creditWalletChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($key);
            },
            'disabled' => $options['lockDebitWallet'] || $options['restricted'],
            'label' => 'Transfer From',
            'required' => true,
        ])->add('creditWalletId', ChoiceType::class, [
            'choices' => $options['creditWalletChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($key);
            },
            'disabled' => $options['lockCreditWallet'] || $options['restricted'],
            'label' => 'Transfer To',
            'required' => true,
        ])->add('description', TextType::class, [
            'attr' => [
                'list' => 'description-presets',
                'placeholder' => 'Type a custom transfer description or choose a preset if available',
            ],
            'help' => 'Max 240 characters. Keep this as succinct as possible. Avoid full sentences!',
            'label' => 'What is the transfer for?',
            'required' => true,
        ])->add('amount', MoneyType::class, [
            'attr' => [
                'placeholder' => 'e.g. 124.85',
            ],
            'currency' => 'GBP',
            'disabled' => $options['restricted'],
            'label' => 'Amount to Transfer',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferRequest::class,
            'debitWalletChoices' => null,
            'creditWalletChoices' => [],
            'lockDebitWallet' => false,
            'lockCreditWallet' => false,
        ]);
    }
}
