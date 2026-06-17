<?php

namespace App\Form;

use App\Entity\Enum\UserStatus;
use App\Entity\UserStatusLog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserStatusLogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('status', EnumType::class, [
            'class' => UserStatus::class,
            'disabled' => $builder->getData()->getId() ? true : false,
            // 'expanded' => true,
            // 'help' => 'What status the user is in',
            'label' => 'Status',
            'placeholder' => 'Choose a status',
        ])->add('occuredAt', DateTimeType::class, [
            'help' => 'The date and time the status change was applied. Leave empty to use current time. Customise for retrospective actions.',
            'widget' => 'single_text',
            'with_seconds' => true,
            'required' => false,
            'empty_data' => new \DateTime()->format('Y-m-d H:i:s'),
        ])->add('notes', TextType::class, [
            'attr' => [
                'placeholder' => 'State a reason for the state change',
            ],
            'help' => 'Optional but for manual state changes, it is recommended to provide a reason.',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserStatusLog::class,
        ]);
    }
}
