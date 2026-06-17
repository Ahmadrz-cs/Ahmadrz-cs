<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MangopayRefundType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('Tag', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. Incorrect initial amount',
            ],
            'help' => 'Explain why a refund is being made. Optional, but providing one is highly recommended.',
            'label' => 'Tag or Description',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \MangoPay\Refund::class,
        ]);
    }
}
