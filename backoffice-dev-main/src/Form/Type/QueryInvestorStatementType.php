<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryInvestorStatementType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('month', DateType::class, [
            'help' => 'Which month to search. The day of the month does not matter.',
            'widget' => 'single_text',
        ]);
    }
}
