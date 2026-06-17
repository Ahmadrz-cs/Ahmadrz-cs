<?php

namespace App\Form;

use App\Entity\KycProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycRestrictionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buyRestricted', CheckboxType::class, [
            'help' => 'Prevent verified users from buying (invest)',
            // 'label' => 'Override Buy Restriction',
            'required' => false,
        ])->add('sellRestricted', CheckboxType::class, [
            'help' => 'Prevent verified users from selling (relist)',
            // 'label' => 'Override Sell Restriction',
            'required' => false,
        ])->add('depositRestricted', CheckboxType::class, [
            'help' => 'Prevent verified users from depositing into their wallet (pay-in)',
            // 'label' => 'Override Deposit Restriction',
            'required' => false,
        ])->add('withdrawRestricted', CheckboxType::class, [
            'help' => 'Prevent verified users from withdrawing from their wallet (pay-out)',
            // 'label' => 'Override Withdraw Restriction',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KycProfile::class,
        ]);
    }
}
