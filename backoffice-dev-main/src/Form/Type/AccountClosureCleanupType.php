<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccountClosureCleanupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('actions', ChoiceType::class, [
            'choices' => $options['actions'],
            'choice_label' => function ($choice, string $key, mixed $value): string {
                return $choice->name;
            },
            'choice_name' => function ($choice, string $key, mixed $value): string {
                return $choice->name;
            },
            'expanded' => true,
            'multiple' => true,
        ])->add('confirmation', CheckboxType::class, [
            'label' => 'Confirm account closure cleanup actions',
        ])->add('confirmationMailchimp', CheckboxType::class, [
            'label' => 'Confirm user has already been removed from Mailchimp mailing lists',
        ])->add('confirmationExtraWallets', CheckboxType::class, [
            'label' => 'Confirm user has no more than 1 wallet',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'actions' => [],
        ]);
    }
}
