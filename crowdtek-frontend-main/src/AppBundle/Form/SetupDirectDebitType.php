<?php

namespace AppBundle\Form;

use AppBundle\Entity\DirectDebit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class SetupDirectDebitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $firstName = $options['first_name'];
        $lastName = $options['last_name'];
        $address1 = $options['address1'];
        $address2 = $options['address2'];
        $address3 = $options['address3'];
        $city = $options['city'];
        $postcode = $options['postcode'];
        $country = $options['country'];
        $bankAccounts = $options['bankAccounts'];


        $builder
            ->add('first_name', TextType::class, [
                'required' => false,
                'data' => $firstName,
                'disabled' => true
            ])
            ->add('last_name', TextType::class, [
                'required' => false,
                'data' => $lastName,
                'disabled' => true

            ])
            ->add('address_1', TextType::class, [
                'required' => false,
                'data' => $address1,
                'disabled' => true
            ])
            ->add('address_2', TextType::class, [
                'required' => false,
                'data' => $address2,
                'disabled' => true
            ])
            ->add('address_3', TextType::class, [
                'required' => false,
                'data' => $address3,
                'disabled' => true
            ])
            ->add('town_city', TextType::class, [
                'required' => false,
                'data' => $city,
                'disabled' => true
            ])
            ->add('post_code', TextType::class, [
                'required' => false,
                'data' => $postcode,
                'disabled' => true
            ])
            ->add('addressCheck', CheckboxType::class, [
                'required' => true,
                'value' => true,
            ])
            //            ->add('bankAccountType', ChoiceType::class, [
            //                'choices'  => array(
            //                    'UK Bank Account' => "uk bank account",
            //                    'EU Bank Account' => "eu bank account"
            //                ),
            //                'data' => "uk bank account",
            //                'expanded' => true,
            //                'multiple' => false,
            //                'required' => true,
            //                'data' => 'uk bank account',
            //            ])

            ->add('bankAccountId', ChoiceType::class, [
                'choices' => $bankAccounts,
                'multiple' => false,
                'required' => true,
                'placeholder' => 'Choose a bank account',
            ])

            //            ->add('accountIban', TextType::class, array(
            //                'required' => true,
            //            ))
            //            ->add('sortBic', TextType::class, array(
            //                'required' => true,
            //            ))

            ->add('amount', MoneyType::class, [
                'divisor' => 100,
                'currency' => false,
                'required' => true,
                'attr' => [
                    'placeholder' => 'Enter Amount:',
                ],
                'constraints' => [
                    new Range(['min' => 5000, 'minMessage' => 'Please enter a value greater than £50']),

                ],
            ])

            ->add('submit', SubmitType::class, ['label' => 'Continue to mandate']);
    }

    public function getBlockPrefix(): string
    {
        return 'setup_direct_debit_type';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DirectDebit::class,
            'first_name' => null,
            'last_name' => null,
            'address1' => null,
            'address2' => null,
            'address3' => null,
            'city' => null,
            'postcode' => null,
            'country' => null,
            'bankAccounts' => null
        ]);
    }
}
