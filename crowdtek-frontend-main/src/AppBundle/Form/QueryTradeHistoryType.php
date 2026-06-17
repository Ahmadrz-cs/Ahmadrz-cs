<?php

namespace AppBundle\Form;

use ClientBundle\Dto\ShareTradeQueryDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class QueryTradeHistoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'Start Date',
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'constraints' => [
                    new Constraints\Callback(function ($object, ExecutionContextInterface $context) {
                        $start = $context->getRoot()->getData()->createdAt_gte;
                        $stop = $object;
                        if ($stop <= $start) {
                            $context
                                ->buildViolation('End Date must be after Start Date')
                                ->addViolation();
                        }
                    }),
                    new Constraints\Callback(function ($object, ExecutionContextInterface $context) {
                        $start = $context->getRoot()->getData()->createdAt_gte;
                        $stop = $object;
                        $interval = $stop->diff($start);
                        if ($interval->days > 367) {
                            $context
                                ->buildViolation('Date range cannot be greater than 12 months.')
                                ->addViolation();
                        }
                    }),
                ],
                'help' => 'This date is exclusive (< this date)',
                'label' => 'End Date',
                'placeholder' => 'Any',
                'required' => true,
                'widget' => 'single_text',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShareTradeQueryDto::class,
        ]);
    }
}
