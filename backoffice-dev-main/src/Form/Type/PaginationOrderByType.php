<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaginationOrderByType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => ['id', 'updatedAt', 'createdAt'],
            'choice_label' => function ($choice, $key, $value) {
                return $value;
            },
            'data' => 'id',
            'empty_data' => 'id',
            'label' => 'Order by Field',
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
