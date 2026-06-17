<?php

namespace App\Form\Type;

use App\Entity\Enum\UserStatus;
use App\Entity\Lifecycle\UserLifecycle;
use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryUserType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'User Id',
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('email', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'label' => 'Contact Email',
                'required' => false,
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. marsh',
                ],
                'help' => 'Searches first and last name. Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('phoneNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 7072221234',
                ],
                'help' => 'Searches phone1 and phone2. Does a rough/fuzzy string match (slow). Only use numbers, do not include the + symbol or any spaces in the query.',
                'required' => false,
            ])
            ->add('referralCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. BMarsh3',
                ],
                'help' => 'Must be an exact match',
                'required' => false,
            ])
            ->add('isVIP', ChoiceType::class, [
                'choices' => [
                    'Regular' => 0,
                    'Vip (Top Yielder)' => 1,
                ],
                'label' => 'Is VIP',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('wordsOfOwn', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'label' => 'Has Top Yielder Application',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('ob_step', ChoiceType::class, [
                'choices' => [
                    'Signed Up' => 1,
                    'Email Verified' => 2,
                    'Questionnaire' => 3,
                    'Compliance' => 4,
                    'Onboarding Complete' => 5,
                ],
                'label' => 'Onboarding Step',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('corporateInvestor', ChoiceType::class, [
                'choices' => [
                    'Retail' => 0,
                    'Institutional/Corporate' => 1,
                ],
                'help' => 'Leverages the investor "corporateInvestor" field',
                'label' => 'Retail or Corporate Investor',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('mangoPayUserId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. user_m_012345EXAMPLE',
                ],
                'help' => 'Does an exact match',
                'label' => 'Mangopay User Id',
                'required' => false,
            ])
            ->add('mangoPayWalletId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. wlt_m_012345EXAMPLE',
                ],
                'help' => 'Does an exact match',
                'label' => 'Mangopay Wallet Id',
                'required' => false,
            ])
            ->add('companyName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. futures',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('hasInvestments', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether user has previous made any investments, irrespective of status',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasManagedUsers', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether the user is managing any other users',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasKycProfile', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether the user has a KYC profile. Older users will be missing this.',
                'label' => 'Has KYC Profile',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasOnboardingProfile', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether the user has an onboarding profile (for PS22/10). Older users will be missing this.',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasMangoPayUserId', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether user has a Mangopay user ID/account',
                'label' => 'Has Mangopay User Id',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasMangoPayWalletId', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether user has a Mangopay wallet assigned',
                'label' => 'Has Mangopay Wallet Id',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('verified', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether the user has KYC verification. Only works if user has a KYC Profile.',
                'label' => 'Is KYC Profile Verified',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('hasVerifiedBy', ChoiceType::class, [
                'choices' => [
                    'No' => 0,
                    'Yes' => 1,
                ],
                'help' => 'Whether the user has been manually verified by someone. Only works if user has a KYC Profile.',
                'label' => 'Has KYC Manual Verification',
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'choices' => [
                    'MALE',
                    'FEMALE',
                    'OTHER',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst(strtolower($value));
                },
                'placeholder' => 'Any',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => UserStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'label' => 'Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
                    UserLifecycle::STATE_EMAIL_VERIFIED,
                    UserLifecycle::STATE_REGISTRATION_COMPLETE,
                    UserLifecycle::STATE_APPROVED,
                    UserLifecycle::STATE_BLOCKED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst(preg_replace("/[\_]/", ' ', $value));
                },
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any". This only matches against current status',
                'label' => 'Legacy Status',
                'multiple' => true,
                'required' => false,
            ])
            ->add('createdAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('createdAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class)
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
