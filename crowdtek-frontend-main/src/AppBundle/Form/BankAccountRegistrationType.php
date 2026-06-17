<?php

namespace AppBundle\Form;

use AppBundle\Entity\BankAccount;
use AppBundle\Entity\Enum\BankAccountFormatType;
use AppBundle\Entity\Enum\BankAccountHolderType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Constraints\Regex;

class BankAccountRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accountHolderType', EnumType::class, [
                'class' => BankAccountHolderType::class,
                'required' => true,
            ])
            ->add('accountNumber', TextType::class, [
                'attr' => $options['accountType'] == BankAccountFormatType::GB
                    ? ['pattern' => "^\d{8}$"]
                    : ['minlength' => "15", 'maxlength' => "34"],
                'constraints' => $options['accountType'] == BankAccountFormatType::GB
                    ? [new Regex(pattern: '/^\d{8}$/', message: "Account number must be 8 digits")]
                    : [new Iban()],
                'help' => $options['accountType'] == BankAccountFormatType::GB
                    ? '8-digit account number'
                    : 'IBANs are typically between 15 and 34 characters long',
                'label' => $options['accountType'] == BankAccountFormatType::GB
                    ? 'Account Number'
                    : 'IBAN',
                'required' => true,
            ])
            ->add('bic', TextType::class, [
                'attr' => $options['accountType'] == BankAccountFormatType::GB
                    ? ['pattern' => "^\d{6}$"]
                    : ['minlength' => "8", 'maxlength' => "11"],
                'constraints' => $options['accountType'] == BankAccountFormatType::GB
                    ? [new Regex(pattern: '/^\d{6}$/', message: "Sort code must be 6 digits")]
                    : [new Bic()],
                'help' => $options['accountType'] == BankAccountFormatType::GB
                    ? '6-digit sort code. Do not include any dashes.'
                    : 'BIC can either be 8 characters long, or 11 if including optional branch code',
                'label' => $options['accountType'] == BankAccountFormatType::GB
                    ? 'Sort Code'
                    : 'BIC',
                'required' => true,
            ])
            ->add('bankStatement', FileType::class, [
                'help' => 'Must be dated within the last 3 months. Accepted file formats: PNG, PDF, JPG, JPEG',
                'mapped' => false,
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
            'accountType' => BankAccountFormatType::GB,
        ]);
    }
}
