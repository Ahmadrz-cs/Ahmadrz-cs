<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaginationLimitType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [5, 10, 20, 25, 50, 100],
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
            'data' => '10',
            'empty_data' => '10',
            'label' => 'Items Per Page',
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
