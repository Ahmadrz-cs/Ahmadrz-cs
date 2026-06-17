<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayHookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reflectionClass = new \ReflectionClass(\MangoPay\EventType::class);
        $eventTypeChoices = $reflectionClass->getConstants();

        $reflectionClass = new \ReflectionClass(\MangoPay\HookStatus::class);
        $hookStatusChoices = $reflectionClass->getConstants();

        $builder->add('Url', UrlType::class, [
            'attr' => [
                'placeholder' => 'https://example.com/webhooks',
            ],
            'help' => 'Our endpoint url that will handle the webhook',
            'label' => 'Endpoint Url',
            'required' => true,
        ])->add('EventType', ChoiceType::class, [
            'choices' => $eventTypeChoices,
            'choice_label' => function ($choice, $key, $value) {
                return $choice;
            },
            'help' => 'The event that triggers the webhook',
            'placeholder' => 'Choose an event type',
            'required' => true,
        ])->add('Tag', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. For managing payout status',
            ],
            'help' => 'What the webhook will be used for',
            'label' => 'Tag or Description',
            'required' => false,
        ])->add('Status', ChoiceType::class, [
            'choices' => $hookStatusChoices,
            'choice_label' => function ($choice, $key, $value) {
                return $choice;
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MangoPay\Hook::class,
            'create' => false,
        ]);
    }
}
