<?php

namespace App\Form;

use App\Entity\Enum\UserCategory;
use App\Entity\OnboardingProfile;
use App\Form\Type\NullableBoolChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnboardingProfileForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cooloffEnd', DateTimeType::class, [
                'date_widget' => 'single_text',
                'help' => 'When the user able to accept the cooldown warning',
                'required' => false,
                'time_widget' => 'single_text',
            ])
            ->add('cooloffAccepted', NullableBoolChoiceType::class, [
                'help' => 'Whether the user has reviewed the cooldown warning and their decision',
                'required' => false,
            ])
            ->add('riskWarningAccepted', NullableBoolChoiceType::class, [
                'help' => 'Whether the user has reviewed the risk warning and their decision',
                'required' => false,
            ])
            ->add('category', EnumType::class, [
                'choice_label' => function ($choice, $key, $value) {
                    return ucwords($choice->name);
                },
                'class' => UserCategory::class,
                'help' => 'What area or section is the question for?',
                'placeholder' => 'N/A',
                'required' => false,
            ])
            ->add('categoryReviewedAt', DateTimeType::class, [
                'date_widget' => 'single_text',
                'help' => 'When the user last performed self categorisation',
                'required' => false,
                'time_widget' => 'single_text',
            ])
            ->add('assessmentPassed', NullableBoolChoiceType::class, [
                'help' => 'Whether the user has attempted and passed the appropriateness test/assessment',
                'required' => false,
            ])
            ->add('realEstatePlanAccess', CheckboxType::class, [
                'help' => 'Whether the user has unlocked access to "real estate development - planning phase" products',
                'required' => false,
            ])
            ->add('realEstateBuildAccess', CheckboxType::class, [
                'help' => 'Whether the user has unlocked access to "real estate development - building phase" products',
                'required' => false,
            ])// ->add('features', EntityType::class, [
        //     'choice_label' => function ($choice, $key, $value) {
        //         return ucwords($choice->getName());
        //     },
        //     'class' => Feature::class,
        //     'expanded' => true,
        //     'help' => 'Additional features the user has unlocked',
        //     'multiple' => true,
        //     'required' => false,
        // ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OnboardingProfile::class,
        ]);
    }
}
