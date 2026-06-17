<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class CategoryHnwType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hnwType', ChoiceType::class, [
                'choices' => [
                    'Annual income of £100,000 or more' => 'income',
                    'Net Assets of £250,000 or more' => 'wealth-assets',
                ],
                'expanded' => true,
                'label' => 'High Net Worth Basis',
                'required' => true,
            ])
            ->add('amount', IntegerType::class, [
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'e.g. 125000',
                ],
                'constraints' => [new GreaterThanOrEqual(0)],
                'label' => 'Specify your income to the nearest £10,000 or net assets to the nearest £250,000 in the last financial year.',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
