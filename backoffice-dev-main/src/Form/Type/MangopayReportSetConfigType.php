<?php

namespace App\Form\Type;

use App\Entity\ReportSet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class MangopayReportSetConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('asset', AssetSelectorType::class, [
            'help' => 'The asset the wallet reports will be for',
        ])->add('periodStart', DateType::class, [
            'attr' => [
                'placeholder' =>
                    'E.g. ' . date('Y-m-d', strtotime('first day of last month')),
            ],
            'constraints' => [
                new GreaterThanOrEqual([
                    'value' => new \DateTime('-3 years'),
                ]),
            ],
            'help' => 'This date is inclusive (transactions >= this date). Earliest allowed is 3 years ago.',
            'widget' => 'single_text',
        ])->add('periodEnd', DateType::class, [
            'attr' => [
                'placeholder' =>
                    'E.g. ' . date('Y-m-d', strtotime('first day of this month')),
            ],
            'help' => 'This date is exclusive (transactions < this date).',
            'widget' => 'single_text',
        ])->add('description', TextareaType::class, [
            'attr' => [
                'style' => 'min-height:4rem;',
            ],
            'help' => '(Optional) Describe what the report will be used for. Maximum 240 characters.',
            'required' => false,
        ])->add('submit', SubmitType::class, ['label' => 'Save and Continue']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReportSet::class,
        ]);
    }
}
