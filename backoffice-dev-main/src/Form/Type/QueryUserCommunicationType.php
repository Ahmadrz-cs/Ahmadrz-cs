<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use App\Form\Type\PaginationLimitType;
use App\Form\Type\PaginationOrderByType;
use App\Form\Type\PaginationOrderDirectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryUserCommunicationType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'User Comm Id',
                'required' => false,
            ])
            ->add('subject', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. dividend',
                ],
                'help' => 'Does a rough/fuzzy string match (slow) on companyNumber',
                'required' => false,
            ])
            ->add('status', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1',
                ],
                'help' => 'Must be an exact match',
                'required' => false,
            ])
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class, [
                'choices' => ['id', 'createdAt'],
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
