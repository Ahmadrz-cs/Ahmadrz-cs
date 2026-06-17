<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BankAccountReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bankStatement', CheckboxType::class, [
                'label' => 'Bank statement within last 3 months provided',
                'required' => true,
            ])
            ->add('accountHolderName', CheckboxType::class, [
                'label' => 'Account holder name matches statement',
                'required' => true,
            ])
            ->add('accountHolderAddress', CheckboxType::class, [
                'label' => 'Account holder address matches statement',
                'required' => true,
            ])
            ->add('accountNumber', CheckboxType::class, [
                'label' => 'Account number (or IBAN) matches statement',
                'required' => true,
            ])
            ->add('bankId', CheckboxType::class, [
                'label' => 'Sort code or BIC matches statement (if applicable)',
                'required' => true,
            ])
            ->add('notifyUser', CheckboxType::class, [
                'label' => 'Send approval email notification to user',
                'required' => false,
            ])
            ->add('pass', SubmitType::class, ['label' => 'Approve Bank Account'])
            ->add('fail', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-danger',
                ],
                'label' => 'Reject Bank Account',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
