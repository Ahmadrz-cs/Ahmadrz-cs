<?php

namespace App\Form\Type;

use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountType as EnumBankAccountType;
use App\Entity\User;
use App\Form\Type\UserAddressType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Workflow\WorkflowInterface;

class BankAccountType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $bankAccountStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->bankAccountStateMachine->can(
            $builder->getData(),
            'approve',
        );
        $builder
            ->add('user', UserSelectorType::class, [
                'required' => true,
                'disabled' => $options['restricted'],
            ])
            ->add('accountType', EnumType::class, [
                'class' => EnumBankAccountType::class,
                'help' => 'GB or International',
                'required' => true,
                'disabled' => $options['restricted'],
            ])
            ->add('accountHolderType', EnumType::class, [
                'class' => BankAccountHolderType::class,
                'help' => 'Personal or business account',
                'required' => true,
                'disabled' => $options['restricted'],
            ])
            ->add('country', CountryType::class, [
                'disabled' => $options['restricted'],
                'help' => 'What country the bank account is from',
                'preferred_choices' => ['GB'],
                'required' => true,
            ])
            ->add('currency', CurrencyType::class, [
                'choice_label' => function (
                    $choice,
                    string $key,
                    mixed $value,
                ): string {
                    return $value;
                },
                'disabled' => $options['restricted'],
                'help' => 'What currency the bank account is expected to use. Must be a supported currency by mangopay. Should be GBP for all manually added accounts as we only support GBP payouts.',
                'preferred_choices' => ['GBP'],
                'required' => true,
            ])
            ->add('accountNumber', TextType::class, [
                'attr' => [
                    'placeholder' => 'Account number',
                ],
                'help' => 'Do not include dashes or spaces.',
                'required' => true,
                'disabled' => $options['restricted'],
            ])
            ->add('bankIdentifierCode', TextType::class, [
                'attr' => [
                    'placeholder' => 'BIC or sort code',
                ],
                'help' => 'Either a BIC for international accounts or a 6-digit GB sort code. Do not include dashes or spaces.',
                'required' => true,
                'disabled' => $options['restricted'],
            ])
            ->add('description', TextType::class, [
                'attr' => [
                    'placeholder' => 'This is optional',
                ],
                'help' => 'Additional information about what account is used for. Will be included in the Mangopay tag.',
                'required' => false,
            ])
            ->add('metadata', JsonType::class, [
                'attr' => [
                    'placeholder' => '{"key": "value"}',
                    'style' => 'min-height:5rem;',
                    'class' => 'font-monospace',
                ],
                'help' => 'This must be valid JSON. You should not generally need to manually edit this.',
                'required' => false,
            ])
            ->add('providerId', TextType::class, [
                'attr' => [
                    'placeholder' => 'Mangopay BankAccount or Recipient Id',
                ],
                'help' => 'Mangopay Recipient Id. You should not normally edit this field',
                'required' => false,
            ])
            ->add('displayName', TextType::class, [
                'attr' => [
                    'placeholder' => 'GBP GB _1234',
                ],
                'constraints' => [
                    new Length([
                        'max' => 80,
                    ]),
                ],
                'help' => 'Max 80 characters. What shows up to the user to help identify the account. This is automatically set if there is a valid fingerprint and the fingerprint has changed.',
                'required' => false,
            ])
            ->add('accountHolderName', TextType::class, [
                'attr' => [
                    'placeholder' => 'This is optional. Leave this empty if the owner is the same as the user. For individual/personal accounts, only include the first name.',
                ],
                'disabled' => $options['restricted'],
                'help' => 'Leave this empty if the owner is the same as the user.',
                'required' => false,
            ])
            ->add('accountHolderLastName', TextType::class, [
                'attr' => [
                    'placeholder' => 'This is optional. Leave this empty if the owner is the same as the user. For individual/personal accounts, only put the last name here.',
                ],
                'disabled' => $options['restricted'],
                'help' => 'Leave this empty if the owner is the same as the user. Not required for business accounts.',
                'required' => false,
            ])
            ->add('accountHolderAddress', UserAddressType::class, [
                'disabled' => $options['restricted'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
        ]);
    }
}
