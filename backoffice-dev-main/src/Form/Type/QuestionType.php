<?php

namespace App\Form\Type;

use App\Entity\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('questionType', EnumType::class, [
            'class' => \App\Entity\Enum\QuestionType::class,
            'help' => 'What type of questionnaire is the question for?',
            'required' => true,
        ])->add('section', EnumType::class, [
            'choice_label' => function ($choice, $key, $value) {
                return ucwords($choice->name);
            },
            'class' => \App\Entity\Enum\QuestionArea::class,
            'help' => 'What area or section is the question for?',
            'placeholder' => 'None',
            'required' => false,
        ])->add('content', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Write your question text here',
                'style' => 'min-height:5rem;',
            ],
            'help' => '240 character limit',
            'label' => 'Question Text',
            'required' => true,
        ])->add('active', CheckboxType::class, [
            'help' => 'Enable this question for use. Only toggle this on when you have finished editing the question',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Question::class,
        ]);
    }
}
