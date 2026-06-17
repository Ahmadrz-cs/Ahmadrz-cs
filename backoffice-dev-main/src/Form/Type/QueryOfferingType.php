<?php

namespace App\Form\Type;

use App\Entity\BaseEntity;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryOfferingType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Offering Id',
                'required' => false,
            ])
            ->add('assetId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Clarence Hold',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'label' => 'Offering Name',
                'required' => false,
            ])
            ->add('isSecondaryMrkt', ChoiceType::class, [
                'choices' => [
                    'Primary' => 0,
                    'Secondary' => 1,
                ],
                'help' => 'Defaults to secondary',
                'label' => 'Primary or Secondary',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('isFeatured', ChoiceType::class, [
                'choices' => [
                    'Not featured' => 0,
                    'Featured' => 1,
                ],
                'label' => 'Featured',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('sell_investment', ChoiceType::class, [
                'choices' => [
                    'First Party Only' => 0,
                    'Relisted Only' => 1,
                ],
                'label' => 'First party or Relisted',
                'placeholder' => 'All',
                'required' => false,
            ])
            ->add('createdBy', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow). Checks offering createdBy field',
                'label' => 'Seller Username',
                'required' => false,
            ])
            ->add('offeringType', ChoiceType::class, [
                'choices' => [
                    'retail',
                    'prefunding',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'label' => 'Retail or Prefunding',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('visibility', ChoiceType::class, [
                'choices' => [
                    'Auto' => BaseEntity::VISIBILITY_AUTO,
                    'Admin' => BaseEntity::VISIBILITY_ADMIN,
                    'Vip' => BaseEntity::VISIBILITY_VIP,
                ],
                'placeholder' => 'Any',
                'required' => false,
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
            ->add('tradeOrder', ChoiceType::class, [
                'choices' => [
                    'Not Linked' => 0,
                    'Linked' => 1,
                ],
                'label' => 'Has Linked Trade Order',
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
}
