<?php

namespace App\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryMangopayEventsType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('filters', MangopayFilterEventType::class)->add(
            'perPage',
            PaginationLimitType::class,
        )->add('page', HiddenType::class);
    }
}
