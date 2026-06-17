<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryProductReviewListingsType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hideNoInvestors', CheckboxType::class, [
                'label' => 'Hide assets with no investors',
                'required' => false,
            ])
            ->add('hideNoListings', CheckboxType::class, [
                'label' => 'Hide assets with no active listings',
                'required' => false,
            ])
            ->add('hideNoAvailable', CheckboxType::class, [
                'label' => 'Hide assets with nothing available to buy',
                'required' => false,
            ])
            ->getForm();
    }
}
