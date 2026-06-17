<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryUserDocumentType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentTag', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. document_tag',
                ],
                'help' => 'Must be an exact match',
                'required' => false,
            ])
            ->add('userId', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'help' => 'The user the document corresponds to',
                'required' => false,
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class, [
                'choices' => ['id', 'createdAt'],
            ])
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
