<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycVipReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sourceOfFunds', CheckboxType::class, [
            'label' => 'Source of funds reviewed',
            'required' => true,
        ])->add('notes', KycNoteType::class, [
            'help' => '240 character limit. If you reject, you must state why.',
        ])->add('pass', SubmitType::class, [
            'label' => 'Approve',
        ])->add('fail', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-outline-danger',
            ],
            'label' => 'Reject',
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
