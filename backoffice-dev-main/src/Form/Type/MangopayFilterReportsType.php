<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayFilterReportsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflectionClass = new \ReflectionClass(\MangoPay\TransactionStatus::class);
        $transactionStatusChoices = $reflectionClass->getConstants();

        $reflectionClass = new \ReflectionClass(\MangoPay\TransactionType::class);
        $transactionTypeChoices = $reflectionClass->getConstants();

        $reflectionClass = new \ReflectionClass(\MangoPay\TransactionNature::class);
        $transactionNatureChoices = $reflectionClass->getConstants();

        $builder
            ->add('Status', ChoiceType::class, [
                'choices' => $transactionStatusChoices,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice;
                },
                'expanded' => true,
                'help' => 'Leave empty or select all for any. Defaults to succeeded only.',
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('Type', ChoiceType::class, [
                'choices' => $transactionTypeChoices,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice;
                },
                'expanded' => true,
                'help' => 'Leave empty or select all for any.',
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('Nature', ChoiceType::class, [
                'choices' => $transactionNatureChoices,
                'choice_label' => function ($choice, $key, $value) {
                    return $choice;
                },
                'expanded' => true,
                'help' => 'Leave empty or select all for any.',
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('ResultCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 000000',
                ],
                'help' => 'Mangopay result code. Mainly useful for error codes.',
                'required' => false,
            ])
            ->add('AuthorId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Filter transactions created by a particular user. Leave this empty to show transactions created by any user.',
                'required' => false,
            ])
            ->add('WalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => '[REQUIRED] Only get transactions from a particular wallet. If you want to export ALL transactions irrespective of wallet, use the Mangopay dashboard instead.',
                'required' => true,
            ])
            ->add('MinDebitedFundsAmount', MoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'currency' => false,
                'divisor' => 100,
                'help' => 'This is in £s not pennies.',
                'required' => false,
            ])
            ->add('MaxDebitedFundsAmount', MoneyType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'currency' => false,
                'divisor' => 100,
                'help' => 'This is in £s not pennies.',
                'required' => false,
            ]);

        if ($options['showAllFields']) {
            $builder->add('AfterDate', DateTimeType::class, [
                'input' => 'timestamp',
                'help' => 'Time in UTC, recommend using midnight. Limit of 6 months between start and end date.',
                'label' => 'Creation Date Start',
                'required' => false,
                'widget' => 'single_text',
            ])->add('BeforeDate', DateTimeType::class, [
                'input' => 'timestamp',
                'help' => 'Time in UTC, recommend using midnight. Limit of 6 months between start and end date.',
                'label' => 'Creation Date End',
                'required' => false,
                'widget' => 'single_text',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MangoPay\FilterReports::class,
            'showAllFields' => true,
        ]);
    }
}
