<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('lifecycleStatus', ChoiceType::class, [
            'choices' => $options['statusChoices'],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'help' => 'Changing this will also affect other fields including the recorded dates for statuses',
            'required' => true,
        ])->add('confirmation', CheckboxType::class, [
            'label' => 'I understand this may have unforseen circumstances and want to change the status anyway',
            'mapped' => false,
            'required' => true,
        ])->add('submit', SubmitType::class, [
            'label' => 'Save Changes',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'statusChoices' => [],
        ]);
    }
}
