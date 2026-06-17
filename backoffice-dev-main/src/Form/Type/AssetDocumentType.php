<?php

namespace App\Form\Type;

use App\Entity\AssetDocuments;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['action'] == 'edit') {
            $builder->add('asset', AssetSelectorType::class, [
                'disabled' => true,
            ])->add('document', DocumentType::class, [
                'action' => 'edit',
            ])->add('submit', SubmitType::class);
        } else {
            $builder->add('asset', AssetSelectorType::class, [
                'required' => true,
            ])->add('document', DocumentType::class, [
                'action' => 'add',
            ])->add('submit', SubmitType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetDocuments::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'assetdocument';
    }
}
