<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryDateRangeType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateStart', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => "Search {$options['dateFieldName']} From",
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('dateEnd', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => "Search {$options['dateFieldName']} To",
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('export', HiddenType::class, [
                'empty_data' => 0,
            ]) // required for export to be valid on submission
            ->add('format', HiddenType::class, [
                'empty_data' => 'csv',
            ]) // required for export to be valid on submission
            ->getForm();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'dateFieldName' => 'Date',
        ]);
    }
}
