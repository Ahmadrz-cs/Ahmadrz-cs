<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 24/01/17
 * Time: 15:01
 */

namespace App\Form\Type;

use App\Entity\InvestmentAddFields;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvestmentAddFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('fieldKey', TextType::class, [
            'required' => true,
        ])->add('fieldValue', TextType::class, [
            'required' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvestmentAddFields::class,
        ]);
    }
}
