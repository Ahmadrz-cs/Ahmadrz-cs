<?php

namespace App\Form;

use App\Entity\Enum\KycDueDiligenceLevel;
use App\Entity\KycReview;
use App\Form\Type\KycNoteType;
use App\Service\KycReviewService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class KycDynamicReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('notes', KycNoteType::class, [
            'help' => '240 character limit.',
        ])->add('pass', SubmitType::class, [
            'label' => 'Approve',
        ])->add('fail', SubmitType::class, [
            'attr' => [
                'class' => 'btn btn-outline-danger',
            ],
            'label' => 'Reject',
        ]);
        ;

        if ($options['kyc_review'] instanceof KycReview) {
            foreach (KycReviewService::CONFIGURABLE_ACTIONS as $action) {
                $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                    ->disableExceptionOnInvalidPropertyPath()
                    ->getPropertyAccessor();
                if ($propertyAccessor->getValue($options['kyc_review'], $action)) {
                    $builder->add($action, CheckboxType::class, [
                        'required' => true,
                    ]);
                }
                if ($options['kyc_review']->isDueDiligenceLevelReview()) {
                    $builder->add('dueDiligenceLevel', EnumType::class, [
                        'class' => KycDueDiligenceLevel::class,
                        'expanded' => true,
                        // 'label' => '',
                        'label_attr' => [
                            'class' => 'radio-inline',
                        ],
                        'required' => true,
                    ]);
                }
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'kyc_review' => null,
        ]);
    }
}
