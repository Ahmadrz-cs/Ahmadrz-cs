<?php

namespace App\Form\Type;

use App\Entity\Offering;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductFinancialType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('asset', AssetFinancialType::class, [
            'required' => false,
        ])->add('netRentProjected', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 4.96',
            ],
            'help' => '[Deprecated] Rental dividends expected per year. Note that this field is being abandoned in favour of "Net Projected Income" which will automatically calculate the (rental) yield.',
            'label' => 'Annual Expected Return (%)',
            'required' => false,
        ])->add('grossProjectReturn', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 28.46',
            ],
            'help' => '[Deprecated] Cumulative rental dividends plus any capital gains expected. Note that this field is considered legacy and no longer actively promoted to customers.',
            'label' => 'Total Expected Return (%)',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offering::class,
        ]);
    }
}
