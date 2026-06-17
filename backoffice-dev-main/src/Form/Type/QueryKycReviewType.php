<?php

namespace App\Form\Type;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Form\Type\AbstractQueryType;
use App\Form\Type\PaginationLimitType;
use App\Form\Type\PaginationOrderByType;
use App\Form\Type\PaginationOrderDirectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryKycReviewType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'KYC Review Id',
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
            ->add('reviewType', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($value);
                },
                'class' => KycReviewType::class,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('reviewedByType', ChoiceType::class, [
                'choices' => [
                    'Human' => true,
                    'System' => false,
                ],
                'help' => 'Whether the review was completed automated by the system or performed by a member of staff.',
                'label' => 'Reviewed',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('decision', ChoiceType::class, [
                'choices' => [
                    'Pass' => 1,
                    'Fail' => 0,
                ],
                'expanded' => true,
                'help' => 'Whether the subject passed or failed the KYC review',
                'multiple' => true,
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($value);
                },
                'class' => KycReviewStatus::class,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => 'Any',
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
            ->add('completedAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CompletedAt Start',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('completedAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CompletedAt End',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class, [
                'choices' => ['id', 'createdAt', 'completedAt'],
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
