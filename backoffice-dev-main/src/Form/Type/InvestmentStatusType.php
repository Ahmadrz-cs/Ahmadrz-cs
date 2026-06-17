<?php

namespace App\Form\Type;

use App\Entity\InvestmentStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvestmentStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('settledOn', DateType::class, [
            'widget' => 'choice',
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();

            $data = $event->getData();

            $existing = null === $data->getId() ? false : true;

            $fieldOptions = [
                'choices' => [
                    'Open' => 'open',
                    'Rejected' => 'rejected',
                    'Approved' => 'approved',
                    'Withdrawn' => 'withdrawn',
                    'Settled' => 'settled',
                ],
                'choice_attr' => function ($key, $val, $index) {
                    // don't allow setting of states beside approved and settled
                    return (
                        !in_array($key, ['approved', 'settled'])
                            ? ['disabled' => 'disabled']
                            : []
                    );
                },
                'disabled' => $existing,
                // 'expanded' => true,
                'placeholder' => 'Choose an option',
                'preferred_choices' => ['approved', 'settled'],
                'required' => true,
            ];
            // force user to choose lifecycleStatus if creating new
            if (!$existing) {
                $fieldOptions['data'] = null;
            }

            $form->add('lifecycleStatus', ChoiceType::class, $fieldOptions);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvestmentStatus::class,
        ]);
    }

    public function getName(): string
    {
        return 'status';
    }
}
