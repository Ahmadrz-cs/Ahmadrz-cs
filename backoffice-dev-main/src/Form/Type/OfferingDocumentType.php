<?php

namespace App\Form\Type;

use App\Entity\OfferingDocuments;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['action'] == 'edit') {
            $builder->add('offering', OfferingSelectorType::class, [
                'disabled' => true,
            ])->add('document', DocumentType::class, [
                'action' => 'edit',
            ])->add('submit', SubmitType::class);
        } else {
            $builder->add('offering', OfferingSelectorType::class, [
                'required' => true,
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
            'data_class' => OfferingDocuments::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'offeringdocument';
    }
}
