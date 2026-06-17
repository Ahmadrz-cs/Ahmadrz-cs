<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaginationOrderDirectionType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Descending' => 'DESC',
                'Ascending' => 'ASC',
            ],
            'data' => 'DESC',
            'empty_data' => 'DESC',
            'label' => 'Order Direction',
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
