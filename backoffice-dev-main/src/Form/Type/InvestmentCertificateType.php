<?php

namespace App\Form\Type;

use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvestmentCertificateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('investment', EntityType::class, [
            'choices' => $options['investments'],
            'choice_label' => function ($investment) {
                return '#'
                . $investment->getId()
                . ' // '
                . $investment->getOffering()->getAsset()->getCompanyNumber()
                . ' // '
                . $investment->getUser()->getFullName();
            },
            'class' => Investment::class,
            'placeholder' => 'Choose an investment',
        ])->add('document', DocumentFileType::class)->add('submit', SubmitType::class, [
            'label' => 'Upload Share Certificate',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvestmentDocuments::class,
            'investments' => [],
        ]);
    }
}
