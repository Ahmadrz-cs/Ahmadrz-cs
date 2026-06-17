<?php

namespace App\Form\Type;

use App\Entity\Transaction;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class)
            ->add('paymentStatus', TextType::class, [
                'label' => 'Status',
            ])
            ->add('referenceId', TextType::class)
            ->add('debitUserId', TextType::class)
            ->add('creditUserId', TextType::class)
            ->add('debitResourceId', TextType::class, [
                'label' => 'Debit wallet',
            ])
            ->add('creditResourceId', TextType::class, [
                'label' => 'Credit wallet',
            ])
            ->add('amount', MoneyType::class, [
                'currency' => 'GBP',
                'divisor' => 100,
                'label' => 'Amount',
            ])
            ->add('fee', MoneyType::class, [
                'currency' => 'GBP',
                'divisor' => 100,
                'label' => 'Fees',
            ])
            ->add('currency', TextType::class)
            ->add('shareAmount', TextType::class)
            ->add('invId', TextType::class, [
                'label' => 'Investment Id',
            ])
            ->add('offeringId', TextType::class)
            ->add('comments', TextType::class, [
                'label' => 'Notes',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
            'disabled' => true, // intended as a read-only entity
        ]);
    }
}
