<?php

namespace App\Form\Type;

use App\Entity\QuestionChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Write your choice text here',
                'style' => 'min-height:5rem;',
            ],
            'help' => '240 character limit',
            'label' => 'Choice Text',
            'required' => true,
        ])->add('active', CheckboxType::class, [
            'help' => 'Whether the option should be shown',
            'label' => 'Show choice',
            'required' => false,
        ])->add('correct', CheckboxType::class, [
            'help' => 'Whether the choice is the correct one for the question',
            'label' => 'Is correct choice?',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionChoice::class,
        ]);
    }
}
