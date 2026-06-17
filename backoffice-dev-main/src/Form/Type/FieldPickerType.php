<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldPickerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('chosenFields', ChoiceType::class, [
            'choices' => $options['choices'],
            'choice_attr' => function ($choice, $key, $value) {
                return ['data-table-column' => $value];
            },
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'expanded' => true,
            // 'help' => 'Choose fields to show or hide in the table',
            'label' => 'Show/Hide Columns',
            'multiple' => true,
            'required' => false,
        ])->getForm();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'choices' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
