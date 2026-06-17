<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('reason', TextType::class, [
            'attr' => [
                'placeholder' => $options['reasonPlaceholder'],
            ],
            'help' => $options['reasonHelpText'],
            'label' => 'Reason (optional)',
            'required' => false,
        ]);
        if ($options['additionalAction']) {
            $builder->add($options['additionalAction']['name'], CheckboxType::class, [
                'label' => $options['additionalAction']['label'],
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'reasonPlaceholder' => 'Provide a reason',
            'reasonHelpText' => '',
            'additionalAction' => null,
        ]);
    }
}
