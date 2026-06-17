<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Form\DataTransformer\UserToNumberTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetConfigureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('createdById', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 123',
                ],
                'disabled' => true,
                'help' => 'The user responsible for the asset. Defaults to superadmin',
                'label' => 'Principal user ID',
                'required' => true,
            ])
            ->add('companyNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. SPVY000123',
                ],
                'label' => 'SPV company number',
                'required' => false,
            ])
            ->add('orgEmail', EmailType::class, [
                'attr' => [
                    'placeholder' => 'e.g. team@yielders.co.uk',
                ],
                'label' => 'Contact email',
                'required' => false,
            ])
            ->add('stampDutyUser', EmailType::class, [
                'attr' => [
                    'placeholder' => 'e.g. stampduty@yielders.co.uk',
                ],
                'label' => 'Stamp duty username',
                'help' => 'User whose wallet is used for holding stamp duty payments',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'That new asset',
                ],
                'help' => 'Maximum of 50 characters. Also sets display name if not already set',
                'required' => true,
            ])
            ->add('assetType', ChoiceType::class, [
                'choices' => [
                    'Residential' => 'Residential',
                    'Commercial' => 'Commercial',
                ],
                'help' => 'Defaults to Residential',
                'label' => 'Property use type',
                'required' => true,
            ])
            // ->add('additionalType', ChoiceType::class, [
            //     'choices' => [
            //         'No Exemptions' => null,
            //         'Development' => 'development',
            //         'Prefunding' => 'prefunding',
            //     ],
            //     'help' => 'Legacy overrides to apply stamp duty exemptions. Defaults to no exemptions',
            //     'label' => 'Stamp duty exemption type',
            //     'required' => true,
            // ])
            ->add('briefDescription', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Some information about this asset',
                ],
                'label' => 'Brief description',
                'required' => false,
            ])
            ->add('detailedDesc', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'In-depth information about this asset',
                ],
                'label' => 'Detailed description',
                'help' => 'More detailed information about an asset. Seen on asset overview pages. If left empty, will re-use brief description',
                'required' => false,
            ])
            ->add('addresses', CollectionType::class, [
                'entry_type' => AddressCreateType::class,
                'entry_options' => ['label' => false],
                'required' => false,
            ])
            ->add('pricePerShare', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1.23',
                ],
                'help' => 'Finest granularity is a whole pence. Any fractional pence will be rounded',
                'label' => 'Share Price (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('amountOfShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 100000',
                ],
                'help' => 'Defaults to 100,000 (one hundred thousand)',
                'label' => 'Number of shares',
                'required' => false,
            ])
            ->add('investmentTerm', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 60',
                ],
                'help' => 'Defaults to 60 months (5 years)',
                'label' => 'Investment term length (months)',
                'required' => false,
            ])
            ->add('fundingGoal', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 123000',
                ],
                'disabled' => true,
                'label' => 'Valuation (£)',
                'help' => 'The funding goal derived from share price and number of shares. Will autogenerate once both are set',
                'required' => false,
            ])
            ->add('holdWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for holding investment funds before settlement',
                'label' => 'Hold Wallet',
                'required' => false,
            ])
            ->add('settlementWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for holding investment funds after settlement if sold by Yielders. Aka "actual" wallet',
                'label' => 'Settlement Wallet',
                'required' => false,
            ])
            ->add('depositWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for receiving deposits like gross income or loans',
                'label' => 'Deposit Wallet',
                'required' => false,
            ])
            ->add('expensesWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for holding funds for expenses',
                'label' => 'Expenses Wallet',
                'required' => false,
            ])
            ->add('taxWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for holding funds for taxes',
                'label' => 'Tax Wallet',
                'required' => false,
            ])
            ->add('distributionWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for making payouts (dividends/divestment)',
                'label' => 'Distribution Wallet',
                'required' => false,
            ])
            ->add('treasuryWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 12345678',
                ],
                'help' => 'Wallet for the asset treasury',
                'label' => 'Treasury Wallet',
                'required' => false,
            ])
            ->add('visibility', VisibilityType::class, [
                'help' => 'Manual override for who should be able to see this asset on frontent. Defaults to Auto (recommended)',
                'required' => true,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    AssetLifecycle::STATE_DRAFT,
                    AssetLifecycle::STATE_SUBMITTED,
                    AssetLifecycle::STATE_APPROVED,
                    AssetLifecycle::STATE_PUBLISHED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'disabled' => true,
                'help' => 'Assets must be published before use for offerings',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Changes',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
