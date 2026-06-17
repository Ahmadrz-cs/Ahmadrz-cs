<?php

namespace App\Form\Type;

use App\Entity\Investment;
use App\Entity\InvestmentStatus;
use App\Form\DataTransformer\ShareTradeToNumberTransformer;
use App\Form\DataTransformer\TradeOrderToNumberTransformer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvestmentType extends AbstractType
{
    public function __construct(
        private Security $security,
        private TradeOrderToNumberTransformer $tradeOrderTransformer,
        private ShareTradeToNumberTransformer $shareTradeTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];
        $transactionDisabled = $readOnly;
        if (!empty($options['data']->getId())) {
            $transactionDisabled = $this->security->isGranted('ROLE_ADMIN')
                ? false
                : true;
            $builder->add('offering', OfferingSelectorType::class, [
                'disabled' => true,
                'required' => true,
            ])->add('user', UserSelectorType::class, [
                'disabled' => true,
                'required' => true,
            ])->add('status', InvestmentStatusType::class, [
                'data_class' => InvestmentStatus::class,
                'disabled' => $readOnly,
            ]);
        } else {
            $builder->add('offering', OfferingSelectorType::class, [
                'required' => true,
                'disabled' => $readOnly,
            ])->add('user', UserSelectorType::class, [
                'required' => true,
                'disabled' => $readOnly,
            ])->add('status', InvestmentStatusType::class, [
                'data_class' => InvestmentStatus::class,
                'disabled' => $readOnly,
            ]);
        }

        // just want the single field, don't care about other statuses

        /*
         * COMMENTED OUT CURRENCY AS THE PLATFORM IS GBP
         * ->add('currency', ChoiceType::class, array('choices' =>  Intl::getCurrencyBundle()->getCurrencyNames('British Pound'),
         * 'required' => true,
         * 'label' => 'Currency'))
         * ->add('currency', CurrencyType::class, array('required' => 'false'))
         */
        //Common Fields
        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Normal' => 'normal',
                    'Off-market' => 'off-market',
                    'Prefunding' => 'prefunding',
                ],
                'multiple' => false,
                'expanded' => true,
                'required' => true,
                'disabled' => $readOnly,
            ])
            ->add('investmentValue', TextType::class, [
                'required' => true,
                'label' => 'Amount Invested (£)',
                'attr' => [
                    'placeholder' => 'Should equal (Number of Shares * Original Share Price)',
                    'disabled' => $readOnly,
                ],
            ])
            ->add('share_amount', IntegerType::class, [
                'required' => true,
                'label' => 'Number of Shares',
                'disabled' => $readOnly,
            ])
            ->add('orgPricePerShare', TextType::class, [
                'required' => true,
                'label' => 'Original Share Price (£)',
                'disabled' => $readOnly,
            ])
            // ->add('interestRate')
            ->add('term', IntegerType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('divested_amount', TextType::class, [
                'disabled' => true,
                'required' => false,
            ])
            ->add('divested_shares', IntegerType::class, [
                'disabled' => true,
                'required' => false,
            ])
            ->add('extraSharesDivested', IntegerType::class, [
                'disabled' => $readOnly,
                'help' => 'Used for divestments from payouts',
                'attr' => [
                    'placeholder' => 'Use 0 if none',
                ],
            ])
            ->add('transaction_id', TextType::class, [
                'disabled' => $transactionDisabled,
                'required' => false,
            ])
            ->add('addFields', CollectionType::class, [
                'entry_type' => InvestmentAddFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('comments', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('numberOfShares', IntegerType::class, [
                'required' => false,
                'label' => 'Alternate number of Shares',
                'help' => 'Reserved for internal technical use. This should match the regular "Number of Shares" field. Fix if not!',
                'disabled' => $readOnly,
            ])
            ->add('tradeOrder', NumberType::class, [
                'attr' => [
                    'placeholder' => 'Associate the investment with a trade order as part of migration process',
                ],
                'disabled' => $readOnly,
                'label' => 'Trade Order Id',
                'required' => false,
            ])
            ->add('shareTrade', NumberType::class, [
                'attr' => [
                    'placeholder' => 'Associate the investment with a share order as part of migration process',
                ],
                'disabled' => $readOnly,
                'label' => 'Share Trade Id',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, ['disabled' => $readOnly]);

        $builder->get('tradeOrder')->addModelTransformer($this->tradeOrderTransformer);
        $builder->get('shareTrade')->addModelTransformer($this->shareTradeTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Investment::class,
            'read_only' => false,
        ]);
    }
}
