<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Form\Type\VisibilityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];

        $builder
            ->add('name', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('additionalType', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('alternateName', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('briefDescription', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('companyNumber', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('displayName', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('detailedDesc', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('orgEmail', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('sector', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('taxId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('fundingGoal', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Funding goal (£)',
                'required' => false,
            ])
            ->add('amountOfShares', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('setupFee', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Setup fee (%)',
                'required' => false,
            ])
            ->add('adminFee', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Admin fee (£)',
                'required' => false,
            ])
            ->add('managementFee', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Management fee (%)',
                'required' => false,
            ])
            ->add('profitShare', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Profit share fee (%)',
                'required' => false,
            ])
            ->add('stampDutyUser', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('assetType', ChoiceType::class, [
                'choices' => [
                    'Commercial' => 'Commercial',
                    'Residential' => 'Residential',
                ],
                'attr' => [
                    'disabled' => $readOnly,
                ],
            ])
            ->add('investmentTerm', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Investment term (months)',
                'required' => false,
            ])
            ->add('termStart', DateType::class, [
                'disabled' => $readOnly,
                'label' => 'Investment term start',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('grossRentalReturnPA', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('netRentalReturnPA', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('grossCapitalAppreciation', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('netCapitalAppreciation', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('netCapitalAppreciationYield', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('netProjectedYield', PercentType::class, [
                'disabled' => $readOnly,
                'help' => 'The income investors should expect to receive per year after all deductions. Expressed as a percentage.',
                'required' => false,
                'scale' => 2,
            ])
            ->add('netProjectedIncome', MoneyType::class, [
                'currency' => 'GBP',
                'help' => 'The income investors should expect to receive per year after all deductions. Expressed as a monetary value.',
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('pointsOfInterest', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('featured', IntegerType::class, [
                'disabled' => $readOnly,
                'help' => 'Whether to feature this asset with a weighting. 0 (zero) means not featured. Any positive number means featured, ranked by largest weighting first.',
                'required' => false,
            ])
            ->add('buyRestricted', CheckboxType::class, [
                'disabled' => $readOnly,
                'help' => 'Prevent any user investing (buying shares) in this asset',
                'required' => false,
            ])
            ->add('sellRestricted', CheckboxType::class, [
                'disabled' => $readOnly,
                'help' => 'Prevent shareholders selling shares in this asset. Enabling this will close the secondary market for this asset.',
                'required' => false,
            ])
            ->add('pricePerShare', TextType::class, [
                'disabled' => $readOnly,
                'label' => 'Price per share (£)',
                'required' => false,
            ])
            ->add('mangoPayUserId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('holdWalletId', TextType::class, [
                'disabled' => $readOnly,
                'help' => 'Aka mangoPayWalletId',
                'required' => false,
            ])
            ->add('settlementWalletId', TextType::class, [
                'disabled' => $readOnly,
                'help' => 'Aka additional_wallet or the actual wallet',
                'required' => false,
            ])
            ->add('depositWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('expensesWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('taxWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('distributionWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('treasuryWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('visibility', VisibilityType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('createdById', IntegerType::class, ['disabled' => true])
            ->add('financialYearStart', DateType::class, [
                'disabled' => $readOnly,
                'help' => 'The year does not matter. Only the month and day.',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('addFields', CollectionType::class, [
                'entry_type' => AssetAdditionalFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('addresses', CollectionType::class, [
                'entry_type' => AssetAddressType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('members', CollectionType::class, [
                'entry_type' => AssetMemberType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('fees', CollectionType::class, [
                'entry_type' => AssetFeeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('submit', SubmitType::class, ['disabled' => $readOnly]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
            'read_only' => false,
        ]);
    }
}
