<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayFilterKycDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflectionClass = new \ReflectionClass(\MangoPay\KycDocumentStatus::class);
        $transactionStatusChoices = $reflectionClass->getConstants();

        $reflectionClass = new \ReflectionClass(\MangoPay\KycDocumentType::class);
        $transactionTypeChoices = $reflectionClass->getConstants();

        $builder->add('AfterDate', DateType::class, [
            'input' => 'timestamp',
            'label' => 'From Creation Date',
            'required' => false,
            'widget' => 'single_text',
        ])->add('BeforeDate', DateType::class, [
            'input' => 'timestamp',
            'label' => 'To Creation Date',
            'required' => false,
            'widget' => 'single_text',
        ])->add('Status', ChoiceType::class, [
            'choices' => $transactionStatusChoices,
            'choice_label' => function ($choice, $key, $value) {
                return $choice;
            },
            'placeholder' => 'Any',
            'required' => false,
        ])->add('Type', ChoiceType::class, [
            'choices' => $transactionTypeChoices,
            'choice_label' => function ($choice, $key, $value) {
                return $choice;
            },
            'placeholder' => 'Any',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MangoPay\FilterKycDocuments::class,
        ]);
    }
}
