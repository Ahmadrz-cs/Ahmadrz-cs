<?php

namespace App\Form;

use App\Entity\KycReview;
use App\Form\Type\KycNoteType;
use App\Form\Type\UserSelectorType;
use App\Service\KycReviewService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycReviewPresetFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('preset', ChoiceType::class, [
            'choices' => array_keys(KycReviewService::KYC_REVIEW_PRESETS),
            'choice_label' => function ($choice, string $key, mixed $value): string {
                return ucwords(str_replace('_', ' ', $value));
            },
            'mapped' => false,
            'required' => true,
        ])->add('subject', UserSelectorType::class, [
            'help' => 'Which user is the subject of the KYC review?',
            'required' => true,
        ])->add('notes', KycNoteType::class, [
            'attr' => [
                'placeholder' => 'e.g. Identity document expired',
                'style' => 'min-height:6rem;',
            ],
            'label' => 'Notes',
            'help' => '240 character limit. Why are you creating this review? If you leave this empty, a default for the preset will be provided.',
            'required' => false,
        ])->add('skipDuplicateCheck', CheckboxType::class, [
            'help' => 'Allow creation of KYC review even if a similar review exists.',
            'mapped' => false,
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KycReview::class,
        ]);
    }
}
