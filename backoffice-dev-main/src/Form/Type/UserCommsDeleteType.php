<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;

class UserCommsDeleteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('createdAt_lt', DateType::class, [
            'constraints' => [new LessThanOrEqual('-1 month')],
            'help' => 'This date is exclusive (< this date). Must be older than 1 month.',
            'label' => 'Comms older than',
            'required' => true,
            'widget' => 'single_text',
        ])->add('subject', ChoiceType::class, [
            'choices' => $options['subjectChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return "{$value}";
            },
            'help' => 'Subject line of email to search/delete',
            'label' => 'Email Subject Line',
            'placeholder' => 'Choose a subject line',
            'required' => true,
        ])->add('search', SubmitType::class, [
            'label' => 'Search Comms',
        ])->add('delete', SubmitType::class, [
            'label' => 'Delete Comms',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'subjectChoices' => [],
        ]);
    }
}
