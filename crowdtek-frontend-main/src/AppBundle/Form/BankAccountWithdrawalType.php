<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class BankAccountWithdrawalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('account', ChoiceType::class, [
                'choices' => $options['linkedAccounts'],
                'required' => true,
            ])
            ->add('amount', MoneyType::class, [
                'attr' => ['min' => '10', 'step' => '0.01', 'placeholder' => 'e.g. 10'],
                'constraints' => [new GreaterThanOrEqual(value: 1000, message: "Amount must be greater than or equal to 10")],
                'currency' => "GBP",
                'divisor' => 100,
                'html5' => true,
                'required' => true,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'linkedAccounts' => [],
        ]);
    }
}
