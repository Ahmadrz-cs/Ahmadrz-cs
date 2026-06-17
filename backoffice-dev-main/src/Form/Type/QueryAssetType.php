<?php

namespace App\Form\Type;

use App\Entity\BaseEntity;
use App\Entity\Enum\AssetStatus;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryAssetType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Asset Id',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Clarence Hold',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'label' => 'Asset Name',
                'required' => false,
            ])
            ->add('companyNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. SPVT000123',
                ],
                'help' => 'Does a rough/fuzzy string match (slow) on companyNumber',
                'label' => 'SPV Id',
                'required' => false,
            ])
            ->add('assetType', ChoiceType::class, [
                'choices' => [
                    'Residential',
                    'Commercial',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'help' => 'Defaults to any. All empty is equivalent to "any"',
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
            ->add('featured', ChoiceType::class, [
                'choices' => [
                    'No' => false,
                    'Yes' => true,
                ],
                'help' => 'Whether the asset is featured. Any value greater than 0 is considered "Yes".',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('buyRestricted', ChoiceType::class, [
                'choices' => [
                    'Open' => 0,
                    'Closed' => 1,
                ],
                'help' => 'Whether any user can invest (buy shares) in this asset',
                'label' => 'Buy Market',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('sellRestricted', ChoiceType::class, [
                'choices' => [
                    'Open' => 0,
                    'Closed' => 1,
                ],
                'help' => 'Whether shareholders can sell shares in this asset',
                'label' => 'Sell Market',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    AssetLifecycle::STATE_DRAFT,
                    AssetLifecycle::STATE_SUBMITTED,
                    AssetLifecycle::STATE_APPROVED,
                    AssetLifecycle::STATE_PUBLISHED,
                    AssetLifecycle::STATE_ARCHIVED,
                    AssetLifecycle::STATE_REJECTED,
                    AssetLifecycle::STATE_CANCELLED,
                    AssetLifecycle::STATE_RESTRICTED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'label' => 'Legacy Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => AssetStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'label' => 'Status',
                'multiple' => true,
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
            ->getForm();
    }
}
