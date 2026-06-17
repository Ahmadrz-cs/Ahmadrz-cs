<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PrefundingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('numberOfShares', IntegerType::class, [
                'constraints' => [
                    new Assert\Positive(message: 'Must invest at least 1 share'),
                ],
                'label' => 'Total Number of Shares',
                'required' => true,
            ])
            ->add('sharesToKeep', IntegerType::class, [
                'label' => 'Number of Shares to Retain',
                'required' => true,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([]);
    }
}
