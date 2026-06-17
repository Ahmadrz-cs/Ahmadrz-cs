<?php

namespace App\Form\Type;

use App\Form\Type\AbstractQueryType;
use Gedmo\Loggable\LogEntryInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class QueryActivityLogType extends AbstractQueryType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'label' => 'Log Id',
                'required' => false,
            ])
            ->add('action', ChoiceType::class, [
                'choices' => [
                    'Create' => LogEntryInterface::ACTION_CREATE,
                    'Update' => LogEntryInterface::ACTION_UPDATE,
                    'Remove' => LogEntryInterface::ACTION_REMOVE,
                ],
                'placeholder' => 'Any',
                'expanded' => true,
                'multiple' => true,
                'help' => 'Defaults to any. All empty is equivalent to "any"',
                'required' => false,
            ])
            ->add('objectId', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 24',
                ],
                'help' => 'Id of the object that was affected',
                'required' => false,
            ])
            ->add('objectClass', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. User',
                ],
                'help' => 'PHP Class of the object that was affected. Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('username', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. admin@yielderverse.co.uk',
                ],
                'help' => 'Does a rough/fuzzy string match (slow)',
                'required' => false,
            ])
            ->add('loggedAt_gte', DateType::class, [
                'help' => 'This date is inclusive (>= this date)',
                'label' => 'CreatedAt Start',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('loggedAt_lt', DateType::class, [
                'help' => 'This date is exclusive (< this date)',
                'label' => 'CreatedAt End',
                'placeholder' => 'Any',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('perPage', PaginationLimitType::class)
            ->add('orderBy', PaginationOrderByType::class)
            ->add('orderDirection', PaginationOrderDirectionType::class)
            ->add('page', HiddenType::class) // required for pagination to be valid on submission
            ->getForm();
    }
}
