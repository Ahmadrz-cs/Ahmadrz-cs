<?php

namespace App\Form;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Form\Type\KycNoteType;
use App\Form\Type\UserSelectorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycReviewFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $isNew = empty($options['data']->getId());

        $builder
            ->add('reviewType', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($choice->name);
                },
                'class' => KycReviewType::class,
                'required' => true,
            ])
            ->add('status', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($choice->name);
                },
                'class' => KycReviewStatus::class,
                'required' => true,
            ])
            ->add('subject', UserSelectorType::class, [
                'help' => 'Which user is the subject of the KYC review?',
                'required' => true,
            ])
            ->add('notes', KycNoteType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Identity document expired',
                    'style' => 'min-height:6rem;',
                ],
                'label' => 'Notes',
                'help' => '240 character limit. Why are you creating this review?',
                'required' => true,
            ])
            ->add('identityReview', CheckboxType::class, [
                'help' => 'Will the review involve an ID verification?',
                'required' => false,
            ])
            ->add('addressReview', CheckboxType::class, [
                'help' => 'Will the review involve an address verification?',
                'required' => false,
            ])
            ->add('countryReview', CheckboxType::class, [
                'help' => 'Will the subject country (nationality or residency) be reviewed?',
                'required' => false,
            ])
            ->add('kycProviderReview', CheckboxType::class, [
                'help' => 'Will the review involve one of our KYC providers (Mangopay or Contengo-Northrow)?',
                'required' => false,
            ])
            ->add('dueDiligenceLevelReview', CheckboxType::class, [
                'help' => 'Will the due diligence level (standard or enhanced) be reviewed?',
                'required' => false,
            ])
            // ->add('kycSurveyReview', CheckboxType::class, [
            //     'help' => 'Will the review involve any KYC survey questions (e.g. employment status, amount they will invest)?',
            //     'required' => false,
            // ])
            ->add('transactionsReview', CheckboxType::class, [
                'help' => 'Will transactions (or source of funds) be reviewed?',
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
