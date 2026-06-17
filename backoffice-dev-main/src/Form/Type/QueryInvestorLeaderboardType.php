<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryInvestorLeaderboardType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('metric', ChoiceType::class, [
            'choices' => [
                'Times Bought' => 'buys',
                'Times Sold' => 'sells',
                'Assets Invested' => 'positions',
                'Times Divested' => 'exits',
            ],
            'help' => 'Which leaderboard to show',
            'required' => true,
        ])->add('month', DateType::class, [
            'help' => 'Which month to search. The day of the month does not matter.',
            'widget' => 'single_text',
        ]);
    }
}
