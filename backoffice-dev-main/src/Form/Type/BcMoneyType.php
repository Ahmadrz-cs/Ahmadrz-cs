<?php

namespace App\Form\Type;

use App\Form\DataTransformer\BcNumberToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BcMoneyType extends AbstractType
{
    public function __construct(
        private BcNumberToStringTransformer $bcNumberTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->bcNumberTransformer);
    }

    public function getParent(): string
    {
        return MoneyType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'currency' => 'GBP',
        ]);
    }
}
