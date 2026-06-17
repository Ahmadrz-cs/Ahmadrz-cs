<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Offering;
use App\Entity\User;
use App\Form\DataTransformer\InvestmentToNumberTransformer;
use App\Form\DataTransformer\TradeOrderToNumberTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingType extends AbstractType
{
    public function __construct(
        private InvestmentToNumberTransformer $investmentTransformer,
        private TradeOrderToNumberTransformer $tradeOrderTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];

        $investmentDisabled = $options['read_only'];
        if ($options['data']->getId()) {
            $investmentDisabled = true;
        }

        $builder
            ->add('sell_investment', NumberType::class, [
                'attr' => [
                    'placeholder' => 'Associate the offering with an investment by ID',
                ],
                'disabled' => $investmentDisabled,
                'label' => 'Investment',
                'required' => false,
            ])
            // ->add('sell_investment', InvestmentSelectorType::class, [
            //     'disabled' => $investmentDisabled,
            //     'label' => 'Investment',
            //     'required' => false,
            // ])
            ->add('offeringType', ChoiceType::class, [
                'choices' => [
                    'Retail' => 'retail',
                    'Prefunding' => 'prefunding',
                ],
                'disabled' => $readOnly,
                'expanded' => true,
                'label' => 'Type',
            ])
            // ->add('asset', EntityType::class, [
            //     'class' => Asset::class,
            //     'required' => true,
            //     'disabled' => $readOnly
            // ])
            ->add('asset', AssetSelectorUnfilteredType::class, [
                'placeholder' => 'Choose an Asset',
                'required' => true,
                'disabled' => $readOnly,
            ])
            ->add('name', TextType::class, ['disabled' => $readOnly])
            ->add('category', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('additionalType', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('comments', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('fundingGoal', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Funding goal (£)',
            ])
            ->add('externalCommitments', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            //boolean
            ->add('isFeatured', CheckboxType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            //boolean
            ->add('isSecondaryMrkt', CheckboxType::class, [
                'disabled' => $readOnly,
                'label' => 'Is Secondary Market Listing',
                'required' => false,
            ])
            ->add('valuation', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('equityOffered', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('noOfShares', IntegerType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('pricePerShare', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Price per share (£)',
                'required' => false,
            ])
            ->add('openDate', DateType::class, [
                'required' => false,
                'disabled' => $readOnly,
                'widget' => 'single_text',
            ])
            ->add('closeDate', DateType::class, [
                'required' => false,
                'disabled' => $readOnly,
                'widget' => 'single_text',
            ])
            ->add('minCommitUser', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Minimum commit (£)',
                'required' => false,
            ])
            ->add('maxCommitUser', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Maximum commit (£)',
                'required' => false,
            ])
            ->add('maxOverFunding', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Max over funding (£)',
                'required' => false,
            ])
            ->add('offeringTerm', IntegerType::class, [
                'disabled' => $readOnly,
                'label' => 'Offering term (years)',
                'required' => false,
            ])
            ->add('grossProjectReturn', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Gross projected return (%)',
                'required' => false,
            ])
            ->add('grossRentProjected', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Gross rent projected (%)',
                'required' => false,
            ])
            ->add('netRentProjected', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Net rent projected (%)',
                'required' => false,
            ])
            ->add('transactionId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('visibility', VisibilityType::class, ['disabled' => $readOnly])
            ->add('createdById', IntegerType::class, ['disabled' => true])
            ->add('addFields', CollectionType::class, [
                'entry_type' => OfferingAddFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('tradeOrder', NumberType::class, [
                'attr' => [
                    'placeholder' => 'Associate the offering with a trade order as part of migration process',
                ],
                'disabled' => $readOnly,
                'label' => 'Trade Order Id',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, ['disabled' => $readOnly]);

        $builder->get(
            'sell_investment',
        )->addModelTransformer($this->investmentTransformer);
        $builder->get('tradeOrder')->addModelTransformer($this->tradeOrderTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offering::class,
            'read_only' => false,
        ]);
    }
}
