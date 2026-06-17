<?php

namespace App\Form\Type;

use App\Entity\Lifecycle\InvestmentLifecycle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryInvestmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Investment Id',
                'required' => false,
            ])
            ->add('userId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('assetId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('assetName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. lodge',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('offeringId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('userIsVIP', ChoiceType::class, [
                'choices' => [
                    'Regular' => 0,
                    'Vip (Top Yielder)' => 1,
                ],
                'help' => 'Filter by investments made by regular or top yielders',
                'label' => 'User is VIP',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('corporateInvestor', ChoiceType::class, [
                'choices' => [
                    'Retail' => 0,
                    'Institutional/Corporate' => 1,
                ],
                'help' => 'Leverages the investor "corporateInvestor" field',
                'label' => 'Retail or Corporate Investor',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasDocuments', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether investment has any documents',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'normal',
                    'prefunding',
                    'off-market',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'label' => 'Type',
                'multiple' => true,
                'required' => false,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    InvestmentLifecycle::STATE_OPEN,
                    InvestmentLifecycle::STATE_APPROVED,
                    InvestmentLifecycle::STATE_SETTLED,
                    InvestmentLifecycle::STATE_WITHDRAWN,
                    InvestmentLifecycle::STATE_REJECTED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'label' => 'Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('tradeOrder', ChoiceType::class, [
                'choices' => [
                    'Not Linked' => 0,
                    'Linked' => 1,
                ],
                'label' => 'Has Linked Trade Order',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('shareTrade', ChoiceType::class, [
                'choices' => [
                    'Not Linked' => 0,
                    'Linked' => 1,
                ],
                'label' => 'Has Linked Share Trade',
                'placeholder' => 'Any',
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
            ->add('orderBy', PaginationOrderByType::class)
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->add('compare', HiddenType::class) // required for compare-view pagination to be valid on submission
            ->getForm();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
