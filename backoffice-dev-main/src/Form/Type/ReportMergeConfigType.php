<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportMergeConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('directionalAmount', CheckboxType::class, [
            'help' => 'Add an amount column in £s with negative/minus sign for debits.',
            'required' => false,
        ])->add('runningBalance', CheckboxType::class, [
            'help' => 'Add a running balance column in £s that shows the net change in balance starting from the most recent transaction.',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
