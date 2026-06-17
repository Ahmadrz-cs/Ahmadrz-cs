<?php

namespace App\Form;

use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Form\Type\AbstractQueryType;
use App\Form\Type\PaginationLimitType;
use App\Form\Type\PaginationOrderByType;
use App\Form\Type\PaginationOrderDirectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryBankAccountType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Bank Account Registration Id',
                'required' => false,
            ])
            ->add('uuid', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 019915c4-37fc-7864-1234-abcdef123456',
                ],
                'label' => 'Bank Account Registration UUID',
                'required' => false,
            ])
            ->add('userId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. ben',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('providerId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. bankacc_m_12345',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('fingerprint', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 32-character-fingerprint',
                ],
                'help' => 'Does an exact match, should be 32 characters long.',
                'required' => false,
            ])
            ->add('displayName', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. GBP GB _1234',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => BankAccountStatus::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ])
            ->add('accountType', EnumType::class, [
                'class' => BankAccountType::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'multiple' => true,
                'required' => false,
            ])
            ->add('accountHolderType', EnumType::class, [
                'class' => BankAccountHolderType::class,
                'expanded' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
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
