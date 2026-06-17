<?php

namespace App\Form\Type;

use App\Entity\PaymentOrder;
use App\Form\Type\AbstractQueryType;
use App\Service\PaymentService;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryPaymentOrderType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Payment Order Id',
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
            ->add('paymentType', ChoiceType::class, [
                'choices' => [
                    PaymentService::TYPE_DIVIDEND,
                    PaymentService::TYPE_REPAYMENT,
                    PaymentService::TYPE_DIVESTMENT,
                    PaymentService::TYPE_INVESTMENT_EXIT,
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
            ->add('status', ChoiceType::class, [
                'choices' => [
                    PaymentOrder::STATE_DRAFT,
                    PaymentOrder::STATE_APPROVED,
                    PaymentOrder::STATE_IN_PROGRESS,
                    PaymentOrder::STATE_COMPLETED,
                    PaymentOrder::STATE_CLOSED,
                    PaymentOrder::STATE_ABANDONED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst(str_replace('_', '-', $value));
                },
                'expanded' => true,
                'help' => 'All empty is equivalent to "any"',
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
