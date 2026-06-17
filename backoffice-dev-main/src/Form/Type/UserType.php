<?php

namespace App\Form\Type;

use App\Entity\BaseUser;
use App\Entity\Enum\ScaStatus;
use App\Entity\Enum\WalletUserVersion;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\User;
use App\Form\DataTransformer\UserToNumberTransformer;
use App\Form\OnboardingProfileForm;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function __construct(
        private Security $security,
        private UserToNumberTransformer $userTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];
        /**
         * @var User|null $user
         */
        $user = $options['data'];
        if (
            $user?->isSuperAdmin()
            && !$this->security->isGranted(BaseUser::ROLE_SUPER_ADMIN)
        ) {
            $readOnly = true;
        }

        $builder
            ->add('username', TextType::class, [
                'disabled' => true,
                'help' => 'User uses this to login, regardless of what their contact email is',
            ])
            ->add('email', TextType::class, [
                'disabled' => $readOnly,
                'help' => 'User receives all communications through this, including 2FA email codes',
                'label' => 'Contact Email',
            ])
            ->add('firstname', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('lastname', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('gender', ChoiceType::class, [
                'placeholder' => 'please select',
                'choices' => [
                    'Male' => 'MALE',
                    'Female' => 'FEMALE',
                    'Other' => 'OTHER',
                ],
                'attr' => [
                    'disabled' => $readOnly,
                ],
                'required' => false,
            ])
            ->add('type', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('honoricPrefix', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('honoricSuffix', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('jobTitle', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('isVIP', ChoiceType::class, [
                'choices' => ['Yes' => 1, 'No' => 0],
                'attr' => [
                    'disabled' => $readOnly,
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'attr' => [
                    'disabled' => $readOnly,
                ],
                'disabled' => $readOnly || $user?->isSuperAdmin(),
                'help' => 'Active users are enabled (default). Blocked users are not enabled and cannot login.',
                'required' => false,
            ])
            ->add('location', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('nationality', CountryType::class, [
                'placeholder' => 'please select',
                'required' => false,
                'disabled' => $readOnly,
            ])
            ->add('phone1', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('phone2', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('mobile', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('birthCountry', CountryType::class, [
                'placeholder' => 'please select',
                'required' => false,
                'disabled' => $readOnly,
            ])
            ->add('birthDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'disabled' => $readOnly,
            ])
            ->add('birthPlace', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('drivingLicenseNo', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('passportNumber', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('passportCountry', CountryType::class, [
                'placeholder' => 'please select',
                'required' => false,
                'disabled' => $readOnly,
            ])
            ->add('customFields', CollectionType::class, [
                'entry_type' => UserAdditionalFieldType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('addresses', CollectionType::class, [
                'entry_type' => UserAddressType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'label' => false,
                'disabled' => $readOnly,
            ])
            ->add('middlename', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('additionalName', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('additionalType', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('affiliateCode', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('biography', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('externalReferenceId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('referralCode', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('taxId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('website', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('mangoPayUserId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('mangoPayWalletId', TextType::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('obStep', ChoiceType::class, [
                'choices' => [
                    'Unknown' => 0,
                    OB_STEP_CONSTANT::STEP1 => OB_STEP_CONSTANT::STEP1_INT,
                    OB_STEP_CONSTANT::STEP2 => OB_STEP_CONSTANT::STEP2_INT,
                    OB_STEP_CONSTANT::STEP3 => OB_STEP_CONSTANT::STEP3_INT,
                    OB_STEP_CONSTANT::STEP4 => OB_STEP_CONSTANT::STEP4_INT,
                    OB_STEP_CONSTANT::STEP5 => OB_STEP_CONSTANT::STEP5_INT,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return "{$value} - {$key}";
                },
                'disabled' => $readOnly,
                'required' => true,
            ])
            ->add('company', UserCompanyType::class, [
                'label' => false,
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('investor', UserInvestorType::class, [
                'label' => false,
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('onboardingProfile', OnboardingProfileForm::class, [
                'disabled' => $readOnly,
                'required' => false,
            ])
            ->add('gdpr_accepted', ChoiceType::class, [
                'choices' => ['Yes' => 1, 'No' => 0],
                'attr' => [
                    'disabled' => $readOnly,
                ],
            ])
            ->add('managedBy', NumberType::class, [
                'attr' => [
                    'placeholder' => 'ID of the user who manages this user',
                ],
                'disabled' => $readOnly,
                'label' => 'Managed By Id',
                'required' => false,
            ])
            ->add('lastLogin', DateTimeType::class, [
                'required' => false,
                'widget' => 'single_text',
                'disabled' => true,
            ])
            ->add('walletUserVersion', EnumType::class, [
                'class' => WalletUserVersion::class,
                'help' => 'Used to track Mangopay related upgrades. You should not normally need to manually change this. Use None if user is not upgradable (e.g. spam, fraud, blocked, etc).',
                // 'disabled' => true,
                'disabled' => $readOnly,
            ])
            ->add('scaStatus', EnumType::class, [
                'class' => ScaStatus::class,
                'disabled' => $readOnly,
                'help' => 'Whether Mangopay SCA enrollment has been completed or is in progress. You can override this to allow users to re-enroll',
                'label' => 'Mangopay SCA status',
            ])
            ->add('scaEnrolledAt', DateTimeType::class, [
                'disabled' => true,
                'help' => 'When the user last attempted Mangopay SCA enrollment',
                'label' => 'Mangopay Last Enrolled At',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('bankAccountsSyncedAt', DateTimeType::class, [
                'disabled' => true,
                'help' => 'When the user bank accounts were last fully synced',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('submit', SubmitType::class, ['disabled' => $readOnly]);

        $builder->get('managedBy')->addModelTransformer($this->userTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'read_only' => false,
        ]);
    }
}
