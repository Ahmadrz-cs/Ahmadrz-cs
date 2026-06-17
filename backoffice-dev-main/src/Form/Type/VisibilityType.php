<?php

namespace App\Form\Type;

use App\Entity\BaseEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisibilityType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => [
                'Auto' => BaseEntity::VISIBILITY_AUTO,
                'Admin' => BaseEntity::VISIBILITY_ADMIN,
                'Vip' => BaseEntity::VISIBILITY_VIP,
                // 'Normal' => BaseEntity::VISIBILITY_NORMAL,
                'All' => BaseEntity::VISIBILITY_ALL,
            ],
            'choice_label' => function ($choice, $key, $value) {
                if ('Auto' === $key) {
                    return 'Auto (Default)';
                }
                if ('Normal' === $key) {
                    return 'Normal Users';
                }

                return $key;
            },
            'preferred_choices' => ['Auto'],
        ]);
    }

    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
