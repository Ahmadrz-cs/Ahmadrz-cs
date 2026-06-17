<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class InvestType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('investment_amount', TextType::class, [
                'required' => true
            ])
            ->add('number_of_shares', HiddenType::class)
            ->add('offering_id', HiddenType::class)
            ->add('organization_id', HiddenType::class)
            ->add('invest_type', HiddenType::class);
    }
    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'invest_type';
    }
}
