<?php

namespace App\Form\Type;

use App\Form\DataTransformer\BcNumberToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class BcNumberType extends AbstractType
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
        return NumberType::class;
    }
}
