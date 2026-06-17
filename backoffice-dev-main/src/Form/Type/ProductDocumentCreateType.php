<?php

namespace App\Form\Type;

use App\Entity\Enum\ProductDocumentType;
use App\Form\Type\DocumentFileType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductDocumentCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('type', EnumType::class, [
            'choice_label' => function ($choice, $key, $value) {
                return ucwords(str_replace('_', ' ', $value));
            },
            'class' => ProductDocumentType::class,
            'disabled' => true,
            'help' => 'The document will be automatically configured base on the type',
            'required' => true,
        ])->add('document', DocumentFileType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
