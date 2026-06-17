<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryMangopayKycDocType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('filters', MangopayFilterKycDocumentType::class)->add(
            'perPage',
            PaginationLimitType::class,
        )->add('page', HiddenType::class);
    }
}
