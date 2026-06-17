<?php

namespace AppBundle\Form;

use ClientBundle\Dto\AssetQueryDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueryProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('featured', CheckboxType::class, [
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'placeholder' => 'Any',
                'choices' => [
                    'Residential' => 'Residential',
                    'Commercial' => 'Commercial',
                ],
                'expanded' => true,
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetQueryDto::class,
        ]);
    }
}
