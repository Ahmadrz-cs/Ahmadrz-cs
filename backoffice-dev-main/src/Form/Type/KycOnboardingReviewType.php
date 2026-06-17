<?php

namespace App\Form\Type;

use App\Entity\Enum\KycDueDiligenceLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycOnboardingReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supportedCountry', CheckboxType::class, [
                'label' => 'Supported country reviewed',
                'required' => true,
            ])
            ->add('contegoKyc', CheckboxType::class, [
                'label' => 'Contego KYC reviewed',
                'required' => true,
            ])
            ->add('mangopayKyc', CheckboxType::class, [
                'label' => 'Mangopay KYC reviewed',
                'required' => true,
            ])
            ->add('dueDiligenceLevel', EnumType::class, [
                'class' => KycDueDiligenceLevel::class,
                'expanded' => true,
                // 'label' => '',
                'label_attr' => [
                    'class' => 'radio-inline',
                ],
                'required' => true,
            ])
            ->add('notes', KycNoteType::class, [
                'help' => '240 character limit. You should provide a comment if you are rejecting, or if you are approving despite a non-green RAG or Mangopay KYC intervention',
            ])
            ->add('pass', SubmitType::class, ['label' => 'Approve'])
            ->add('fail', SubmitType::class, [
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
