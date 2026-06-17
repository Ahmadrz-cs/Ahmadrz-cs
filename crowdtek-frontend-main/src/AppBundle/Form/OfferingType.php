<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('life_cycle_stage', HiddenType::class)
            ->add('name', TextType::class, [
                'required' => false
            ])
            ->add('organization_id', ChoiceType::class, [
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'placeholder' => 'Choose your organization',
                'empty_data' => null,
                'choices' => $options['organization_choices'],
            ])
            ->add('funding_goal', TextType::class, ['required' => false])
            ->add('max_commitment', TextType::class, ['required' => false])
            ->add('credit_score', TextType::class, ['required' => false])
            ->add('term', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    "0 year" => 0,
                    "1 year" => 1,
                    "2 year" => 2,
                    "3 year" => 3,
                    "4 year" => 4,
                    "5 year" => 5,
                ]
            ])
            // CROWDTEK CHANGE.
            ->add('SPVFile', FileType::class)
            ->add('info', CustomInfo2Type::class)
            // ->add('file', new FileType())

        ;
    }

    // if no option organization_choice given, need to handle by setting to null
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'organization_choices' => null,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'offering_type';
    }
}
