<?php

namespace AppBundle\Form;

use AppBundle\Form\OrganizationAddressType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class OrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('display_name', TextType::class, [
                'required' => true,
            ])
            ->add('brief_desc', TextareaType::class, [
                'attr' => ['rows' => '5'],
                'required' => false
            ])
            ->add('detail_desc', TextareaType::class, [
                'attr' => ['rows' => '5'],
                'required' => false
            ])
            ->add('sector', TextType::class, [
                'required' => false
            ])
            ->add('location', TextType::class, [
                'required' => false
            ])
            ->add('life_cycle_stage', HiddenType::class)
            ->add('org_email', TextType::class, [
                'required' => false
            ])
            ->add('telephone', TextType::class, [
                'required' => false
            ])
            ->add('website', TextType::class, [
                'required' => false
            ])
            ->add('facebook', TextType::class, [
                'required' => false
            ])
            ->add('twitter', TextType::class, [
                'required' => false
            ])
            ->add('linkedin', TextType::class, [
                'required' => false
            ])
            ->add('youtube', TextType::class, [
                'required' => false
            ])
            ->add('legal_name', TextType::class, [
                'required' => false
            ])->add('addtional_type', ChoiceType::class, [
                'choices' => [
                    'add new listing' => 'Add new listing',
                    'add off-market listing' => 'Add off-market listing',
                    'add secondary market listing' => 'Add secondary market listing',
                ]
            ])
            ->add('credit_score', ChoiceType::class, [
                'choices' => [
                    'Low' => 'Low',
                    'Medium' => 'Medium',
                    'High' => 'High'
                ],
                'required' => true,
                'expanded' => false,
                'multiple' => false,
            ])
            ->add('address', OrganizationAddressType::class)
            ->add('file', FileType::class)
            ->add('info', OrganizationInfoType::class);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'organization_type';
    }
}
