<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 2:15 PM
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\Range;

class DirectDebitAmountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $amount = $options['amount'];

        $builder
            ->add('amount', MoneyType::class, [
                'divisor' => 100,
                'currency' => false,
                'required' => true,
                'data' => $amount,
                // 'attr' => array(
                //         //'placeholder' => 'Enter Amount:',
                // ),
                'constraints' => [
                    new Range([
                        'min' => 5000,
                        'minMessage' => 'Please enter a value greater than £50',
                    ]),

                ],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Update Amount']);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'direct_debit_amount_type';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'amount' => null,

        ]);
    }
}
