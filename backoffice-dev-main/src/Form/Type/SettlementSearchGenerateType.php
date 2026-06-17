<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettlementSearchGenerateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('createdAt_gte', DateType::class, [
            'help' => 'This date is inclusive (>= this date)',
            'input' => 'datetime_immutable',
            'label' => 'Investments From',
            'placeholder' => 'Any',
            'required' => true,
            'widget' => 'single_text',
        ])->add('createdAt_lt', DateType::class, [
            'help' => 'This date is exclusive (< this date)',
            'input' => 'datetime_immutable',
            'label' => 'Investments To',
            'placeholder' => 'Any',
            'required' => true,
            'widget' => 'single_text',
        ])->add('search', SubmitType::class, [
            'label' => 'Search Transfers',
        ])->add('generate', SubmitType::class, [
            'label' => 'Generate Transfers',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
