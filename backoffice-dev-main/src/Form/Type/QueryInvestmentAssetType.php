<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryInvestmentAssetType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('assetId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
