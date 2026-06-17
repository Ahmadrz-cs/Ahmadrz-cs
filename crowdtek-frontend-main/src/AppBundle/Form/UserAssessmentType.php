<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserAssessmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options['responses'] as $key => $ar) {
            $builder
                ->add($key, AssessmentResponseType::class, [
                    'label' => false,
                    'data' => $ar,
                ])
            ;
        }
        // $builder
        //     ->add('responses', CollectionType::class, [
        //         'entry_type' => AssessmentResponseType::class,
        //         'label' => false,
        //     ])
        // ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'responses' => null,
        ]);
    }
}
