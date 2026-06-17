<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class KycNoteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'placeholder' => 'If manual intervention was required or a non-standard decision was made, please comment what and why',
                'style' => 'min-height:6rem;',
            ],
            'constraints' => [
                new Length([
                    'min' => 0,
                    'max' => 240,
                ]),
            ],
            'help' => '240 character limit. Comments are optional.',
            'label' => 'Additional Notes',
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
