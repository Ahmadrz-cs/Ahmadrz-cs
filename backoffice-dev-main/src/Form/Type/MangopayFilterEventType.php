<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayFilterEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflectionClass = new \ReflectionClass(\MangoPay\EventType::class);
        $eventTypeChoices = $reflectionClass->getConstants();

        $builder->add('AfterDate', DateTimeType::class, [
            'input' => 'timestamp',
            'label' => 'From Creation Date',
            'required' => false,
            'widget' => 'single_text',
            'with_seconds' => true,
        ])->add('BeforeDate', DateTimeType::class, [
            'input' => 'timestamp',
            'label' => 'To Creation Date',
            'required' => false,
            'widget' => 'single_text',
            'with_seconds' => true,
        ])->add('EventType', ChoiceType::class, [
            'choices' => $eventTypeChoices,
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
            'data_class' => \MangoPay\FilterEvents::class,
        ]);
    }
}
