<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryVisualisationOverTime extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // legacy version, remove when next version works
            // ->add('filter', ChoiceType::class, [
            //     'label' => 'Filter By Last X Months',
            //     'choices' => [
            //         '1' => 1,
            //         '3' => 3,
            //         '6' => 6,
            //         '12' => 12 ,
            //         '24' => 24,
            //         '36' => 36,
            //         '48' => 48,
            //         '60' => 60,
            //         'All Time' => date('Ym') - date('Ym'),
            //     ],
            //     'required' => true,
            // ])
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'Start',
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'End',
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->getForm();
    }
}
