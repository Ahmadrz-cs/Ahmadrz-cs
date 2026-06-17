<?php

namespace App\Form;

use App\Entity\UserAssessment;
use App\Form\Type\AssessmentResponseType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // $edit_mode = !is_null($builder->getData()?->getId());
        $builder->add('responses', CollectionType::class, [
            'entry_type' => AssessmentResponseType::class,
            'entry_options' => ['label' => false],
            // 'help' => '',
            // 'label' => 'Has Passed Assessment?',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserAssessment::class,
        ]);
    }
}
