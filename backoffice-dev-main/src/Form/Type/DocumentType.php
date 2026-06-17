<?php

namespace App\Form\Type;

use App\Entity\Document;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['action'] === 'add') {
            //new file
            $builder->add('file', FileType::class)->add('type', TextType::class, [
                'disabled' => 'true',
                'label' => 'Mime type',
            ])->add('filename', TextType::class, [
                'disabled' => 'true',
            ])->add('description', TextType::class, [
                'required' => false,
            ])->add('tag', TextType::class, ['required' => false]);
        } else {
            //edit file
            $builder->add('filename', TextType::class, [
                'disabled' => 'true',
            ])->add('type', TextType::class, [
                'disabled' => 'true',
                'label' => 'Mime type',
            ])->add('description', TextType::class, [
                'required' => false,
            ])->add('tag', TextType::class, [
                'required' => false,
            ])->add('documentUrl', TextType::class, ['required' => false]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'action' => null,
            'data_class' => Document::class,
        ]);
    }

    public function getName(): string
    {
        return 'document';
    }
}
