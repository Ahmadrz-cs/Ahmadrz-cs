<?php

namespace AppBundle\Form;

use AppBundle\Entity\OnboardingProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnboardingProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cooloffAccepted', CheckboxType::class, [
                'help' => 'Whether the user has reviewed the cooldown warning and their decision',
                'required' => false,
            ])
            ->add('riskWarningAccepted', CheckboxType::class, [
                'help' => 'Whether the user has reviewed the risk warning and their decision',
                'required' => false,
            ])
            ->add('assessmentPassed', CheckboxType::class, [
                'help' => 'Whether the user has attempted and passed the appropriateness test/assessment',
                'required' => false,
            ])
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
