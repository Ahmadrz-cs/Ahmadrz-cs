<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class AppSettingForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('yieldersFeeWallet', TextType::class, [
            'attr' => [
                'placeholder' => 'wlt_m_0001EXAMPLE',
            ],
            'help' => 'Main Yielders fee wallet',
            'required' => true,
        ])->add('ypmlFeeWallet', TextType::class, [
            'attr' => [
                'placeholder' => 'wlt_m_0001EXAMPLE',
            ],
            'help' => '(Optional) Segregated fee wallet for Yielders Property Management Limited (YPML) activities',
            'label' => 'YPML fee wallet',
            'required' => false,
        ])->add('orderIssueLimit', IntegerType::class, [
            'attr' => [
                'placeholder' => 'Enter a whole number',
            ],
            'help' => '(Optional) Number of failed payments or transfers before order should prematurely exit',
            'label' => 'Order issue limit',
            'required' => false,
        ]);
    }
}
