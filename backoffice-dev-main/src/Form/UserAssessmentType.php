<?php

namespace App\Form;

use App\Entity\UserAssessment;
use App\Form\Type\NullableBoolChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $edit_mode = !is_null($builder->getData()?->getId());
        $builder->add('passed', NullableBoolChoiceType::class, [
            'disabled' => !$edit_mode,
            'help' => 'On completion, did the user pass the assessment? Cannot be set during initial creation',
            'label' => 'Has Passed Assessment?',
            'required' => false,
        ])->add('complete', CheckboxType::class, [
            'disabled' => !$edit_mode,
            'help' => 'Whether the assessment is considered complete or not and should count as an attempt',
            'label' => 'Is Complete?',
            'required' => false,
        ])->add('notes', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Staff notes relating to this assessment',
                'style' => 'min-height:5rem;',
            ],
            'help' => '240 character limit. Any staff notes relating to this assessment',
            'label' => 'Staff Notes',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAssessment::class,
        ]);
    }
}
