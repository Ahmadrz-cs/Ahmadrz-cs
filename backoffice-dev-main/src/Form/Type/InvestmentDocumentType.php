<?php

namespace App\Form\Type;

use App\Entity\InvestmentDocuments;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InvestmentDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['action'] == 'edit') {
            $builder->add('investment', InvestmentSelectorType::class, [
                'disabled' => true,
            ])->add('document', DocumentType::class, [
                'action' => 'edit',
            ])->add('submit', SubmitType::class);
        } else {
            $builder->add('investment', InvestmentSelectorType::class, [
                'required' => true,
                // 'disabled' => true
            ])->add('document', CollectionType::class, [
                'entry_type' => DocumentType::class,
                'entry_options' => ['action' => 'add'],
            ])->add('submit', SubmitType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InvestmentDocuments::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'investmentdocument';
    }
}
