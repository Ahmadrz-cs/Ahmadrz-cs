<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use App\Form\Type\PaginationLimitType;
use App\Form\Type\PaginationOrderByType;
use App\Form\Type\PaginationOrderDirectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryKycReportType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'KYC Report Id',
                'required' => false,
            ])
            ->add('subjectId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Subject User Id',
                'required' => false,
            ])
            ->add('subjectUsername', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'label' => 'Subject Username',
                'required' => false,
            ])
            ->add('providerName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. mangopay',
                ],
                'help' => 'Name of the KYC provider. Usually mangopay or contego',
                'label' => 'KYC Provider Name',
                'required' => false,
            ])
            ->add('providerReferenceId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 471829',
                ],
                'help' => 'Reference Id of the KYC check performed by the KYC provider',
                'label' => 'KYC Check Reference Id',
                'required' => false,
            ])
            ->add('verified', ChoiceType::class, [
                'choices' => [
                    'Verified' => 1,
                    'Failed' => 0,
                ],
                'expanded' => true,
                'help' => 'Whether the subject passed or failed the KYC check',
                'label' => 'Kyc Outcome',
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('checkType', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. NATURAL',
                ],
                'help' => 'Type of check being performed. Varies by KYC provider',
                'required' => false,
            ])
            ->add('result', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. REGULAR',
                ],
                'help' => 'Result code or information',
                'required' => false,
            ])
            ->add('score', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1',
                ],
                'help' => 'Result score or equivalent if applicable',
                'required' => false,
            ])
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('checkedAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CheckedAt Start',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('checkedAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CheckedAt End',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class, [
                'choices' => ['id', 'createdAt', 'checkedAt'],
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
