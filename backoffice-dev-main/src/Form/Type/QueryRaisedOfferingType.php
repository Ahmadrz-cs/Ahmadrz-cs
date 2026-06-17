<?php

namespace App\Form\Type;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryRaisedOfferingType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('includePrefunding', CheckboxType::class, [
                'label' => 'Include prefunding investments',
                'help' => 'Whether to include prefunding investments in the raised amount total',
                'required' => false,
            ])
            ->add('fundingProgress', ChoiceType::class, [
                'choices' => [
                    'Any' => null,
                    'None only (0%)' => 0,
                    'Partial only (>0% <100%)' => 1,
                    'Complete only (>=100%)' => 100,
                    'Incomplete (<100%)' => 99,
                    'Any non-zero progress (>0%)' => 101,
                ],
                'required' => true,
            ])
            ->add('firstPartyOnly', ChoiceType::class, [
                'choices' => [
                    'Any' => null,
                    'First Party Only' => 1,
                    'Relisted Only' => 0,
                ],
                'label' => 'First party or Relisted',
                'required' => true,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    OfferingLifecycle::STATE_DRAFT,
                    OfferingLifecycle::STATE_SUBMITTED,
                    OfferingLifecycle::STATE_APPROVED,
                    OfferingLifecycle::STATE_RESTRICTED,
                    OfferingLifecycle::STATE_PUBLISHED,
                    OfferingLifecycle::STATE_CANCELLED,
                    OfferingLifecycle::STATE_CLOSED,
                    OfferingLifecycle::STATE_REJECTED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'expanded' => true,
                'help' => 'Defaults to draft, submitted, approved, published. All empty is equivalent to "any"',
                'label' => 'Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            // ->add('perPage', PaginationLimitType::class)
            // ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->add('export', HiddenType::class, [
                'empty_data' => 0,
            ]) // required for export to be valid on submission
            ->add('format', HiddenType::class, [
                'empty_data' => 'csv',
            ]) // required for export to be valid on submission
            ->getForm();
    }
}
