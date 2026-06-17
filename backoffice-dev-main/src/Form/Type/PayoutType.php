<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Payout;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PayoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];

        $builder
            // ->add('investment', InvestmentSelectorType::class, [
            //     'disabled' => true,
            //     'label' => 'Related Investment (Legacy)',
            //     'placeholder' => 'Legacy payouts only',
            //     'required' => false,
            // ])
            ->add('investment', TextType::class, [
                'disabled' => true,
                'attr' => [
                    'placeholder' => 'Legacy payouts only',
                ],
                'label' => 'Related Investment (Legacy)',
                'required' => false,
            ])
            ->add('asset', AssetSelectorType::class, [
                'placeholder' => 'Choose an Asset',
                'required' => true,
                'disabled' => $readOnly,
            ])
            ->add('creditedUser', UserSelectorType::class, [
                'placeholder' => 'Choose a User',
                'required' => true,
                'disabled' => $readOnly,
            ])
            ->add('payoutType', ChoiceType::class, [
                'choices' => [
                    'Dividend' => 0,
                    'Divestment (Profit-share)' => 1,
                ],
                'disabled' => $readOnly,
                'expanded' => true,
                'multiple' => false,
                'required' => true,
            ])
            ->add('currency', CurrencyType::class, [
                'required' => true,
                'disabled' => $readOnly,
            ])
            ->add('payoutAmount', NumberType::class, [
                'input' => 'string',
                'required' => true,
                'label' => 'Payout Amount (£)',
                'disabled' => $readOnly,
                'scale' => 2,
            ])
            ->add('additionalType', TextType::class, [
                'required' => false,
                'disabled' => $readOnly,
            ])
            ->add('dueDate', DateType::class, [
                'required' => true,
                'widget' => 'single_text',
                'disabled' => $readOnly,
            ])
            ->add('transactionId', TextType::class, [
                'required' => false,
                'disabled' => $readOnly,
                'help' => 'External transaction id, e.g. from Mangopay',
            ])
            ->add('shareholding', IntegerType::class, [
                'disabled' => $readOnly,
                'help' => 'Shareholding on which payout was made',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, ['disabled' => $readOnly]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payout::class,
            'read_only' => false,
        ]);
    }
}
