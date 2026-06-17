<?php

namespace App\Form\Type;

use App\Service\MangopayReportService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayReportRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('Filters', MangopayFilterReportsType::class, [
            'showAllFields' => $options['showAllFields'],
        ])->add('Sort', ChoiceType::class, [
            'choices' => [
                'Newest First' => 'CreationDate:DESC',
                'Oldest First' => 'CreationDate:ASC',
            ],
            'help' => 'What order should the transactions be in? Defaults to newest first (reverse chronological).',
            'required' => true,
        ])->add('Columns', ChoiceType::class, [
            'choices' => MangopayReportService::REPORT_COLUMNS_ALL,
            'choice_label' => function ($choice, $key, $value) {
                return $choice;
            },
            'expanded' => true,
            'help' => 'Which fields to include in the export.',
            'multiple' => true,
            'required' => true,
        ])->add('Tag', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. Transactions for SPV123',
            ],
            'help' => 'Describe what the report covers.',
            'label' => 'Tag or Description',
            'required' => false,
        ])->add('disableAutoSave', CheckboxType::class, [
            'help' => 'By default, a callback url is set to automatically save the report when it is ready. You can optionally disable this, e.g. for test reports',
            'label' => 'Do not autosave report when ready',
            'mapped' => false,
            'required' => false,
        ]);

        if ($options['showAllFields']) {
            $builder->add('Preview', CheckboxType::class, [
                'help' => 'Limit to first 10 results. Useful if you want to test whether the report you want to make is correct.',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MangoPay\ReportRequest::class,
            'showAllFields' => true,
        ]);
    }
}
