<?php

namespace App\Form\Type;

use App\Entity\Offering;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductRulesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('minCommitUser', NumberType::class, [
            'attr' => [
                'placeholder' => 'e.g. 100',
            ],
            'help' => 'This will be rounded UP to the next multiple of the share price.',
            'label' => 'Minimum Single Commit/Investment (£)',
            'required' => false,
            'scale' => 2,
        ])->add('maxCommitUser', NumberType::class, [
            'help' => 'This cannot be lower than the minimum (unless empty or 0) and will be rounded DOWN to the previous multiple of the share price.',
            'label' => 'Maximum Single Commit/Investment (£)',
            'required' => false,
            'scale' => 2,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Offering::class,
            'data_class' => null,
        ]);
    }
}
