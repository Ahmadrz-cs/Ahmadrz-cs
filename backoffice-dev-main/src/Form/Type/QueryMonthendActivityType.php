<?php

namespace App\Form\Type;

use App\Entity\AbstractOrder;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\LessThan;

class QueryMonthendActivityType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startMonth', DateType::class, [
                'constraints' => [
                    new LessThan([
                        'value' => new \DateTime('first day of next month')->setTime(
                            0,
                            0,
                        ),
                    ]),
                ],
                'help' => 'Defaults to current month. Only the year and month are used.',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('endMonth', DateType::class, [
                'constraints' => [
                    new LessThan([
                        'value' => new \DateTime('first day of next month')->setTime(
                            0,
                            0,
                        ),
                    ]),
                ],
                'help' => 'Defaults to current month. Only the year and month are used.',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    AbstractOrder::STATE_DRAFT,
                    AbstractOrder::STATE_APPROVED,
                    AbstractOrder::STATE_IN_PROGRESS,
                    AbstractOrder::STATE_COMPLETED,
                    AbstractOrder::STATE_CLOSED,
                    AbstractOrder::STATE_ABANDONED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst(str_replace('_', '-', $value));
                },
                'expanded' => true,
                'help' => 'Defaults to completed only. If none set, the default (completed only) will be used.',
                'label' => 'Status of the Order/Activity',
                'multiple' => true,
                'required' => false,
            ])
            ->getForm();
    }
}
