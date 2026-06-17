<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeSearchGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('dateStart', DateType::class, [
            'help' => 'This date is inclusive (>= this date)',
            'label' => "Search {$options['dateFieldName']} From",
            'placeholder' => 'Any',
            'required' => true,
            'widget' => 'single_text',
        ])->add('dateEnd', DateType::class, [
            'help' => 'This date is exclusive (< this date)',
            'label' => "Search {$options['dateFieldName']} To",
            'placeholder' => 'Any',
            'required' => true,
            'widget' => 'single_text',
        ])->add('search', SubmitType::class)->add('generate', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'dateFieldName' => 'Date',
        ]);
    }
}
