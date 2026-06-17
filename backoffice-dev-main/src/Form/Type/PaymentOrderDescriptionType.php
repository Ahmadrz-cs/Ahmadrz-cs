<?php

namespace App\Form\Type;

use App\Entity\PaymentOrder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentOrderDescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('description', TextareaType::class, [
            'attr' => [
                'placeholder' => 'e.g. Dividend top-up',
                'style' => 'min-height:3rem;',
            ],
            'help' => 'A description is only recommended for unscheduled or reduced payments',
            'label' => 'Description (optional)',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentOrder::class,
        ]);
    }
}
