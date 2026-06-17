<?php

namespace App\Form\Type;

use App\Entity\PaymentRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrefunderShareTransferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('shareholding', IntegerType::class, [
            'attr' => [
                'placeholder' => 'e.g. 283',
            ],
            'help' => 'Number of shares that have been repaid for this prefunder',
            'label' => 'Shares to Transfer',
            'required' => $builder->getData()->getId() ? true : false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentRequest::class,
        ]);
    }
}
