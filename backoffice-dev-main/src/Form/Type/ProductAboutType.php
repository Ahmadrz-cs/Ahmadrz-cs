<?php

namespace App\Form\Type;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductAboutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('companyNumber', TextType::class, [
            'attr' => [
                'placeholder' => 'e.g. SPVY000123',
            ],
            'label' => 'SPV company number',
            'required' => false,
        ])->add('name', TextType::class, [
            'attr' => [
                'placeholder' => 'That new asset',
            ],
            'help' => 'Maximum of 50 characters',
            'label' => 'Product Name',
            'required' => true,
        ])->add('assetType', ChoiceType::class, [
            'choices' => [
                'Residential' => 'Residential',
                'Commercial' => 'Commercial',
            ],
            'help' => 'Defaults to Residential',
            'label' => 'Property use type',
            'required' => true,
        ])->add('briefDescription', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Some information about this asset',
            ],
            'label' => 'Description',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
