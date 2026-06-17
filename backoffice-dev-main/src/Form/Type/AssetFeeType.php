<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 07/02/17
 * Time: 10:15
 */

namespace App\Form\Type;

use App\Entity\AssetFee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('type', ChoiceType::class, [
            //     'choices' => [
            //         "Relisting" => "relisting"
            //     ],
            // ])
            ->add('type', HiddenType::class, [
                'data' => 'relisting',
            ])
            ->add('band', IntegerType::class, [
                'required' => true,
                'label' => 'Fee band start (£)',
            ])
            ->add('fee', IntegerType::class, [
                'required' => true,
                'label' => 'Fee band cap (£)',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetFee::class,
        ]);
    }
}
