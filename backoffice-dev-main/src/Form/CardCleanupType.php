<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CardCleanupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('confirmation', CheckboxType::class, [
            'label' => 'Confirm deletion of user card registrations',
            'required' => true,
        ])->add('batchSize', ChoiceType::class, [
            'choices' => range(1, 10),
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
            'help' => 'Defaults to 10. Maximum of 10 (size of one page of cards).',
            'preferred_choices' => [10],
            'label' => 'Number of cards to deactivate in one go',
            'required' => true,
        ])->add('submit', SubmitType::class, [
            'label' => 'Cleanup User Card Registrations',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
