<?php

namespace App\Form\Type;

use App\Entity\Enum\ActionRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('actionRequests', EnumType::class, [
            'class' => ActionRequest::class,
            'choices' => $options['choices'],
            'choice_label' => function ($choice, $key, $value) {
                return ucwords(str_replace('_', ' ', $value));
            },
            'choice_name' => function ($choice, $key, $value) {
                return $value;
            },
            'expanded' => true,
            'multiple' => true,
            'required' => true,
        ])->add('notifyUser', CheckboxType::class, [
            'label' => 'Send action request email notification to user',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'choices' => ActionRequest::cases(),
        ]);
    }
}
