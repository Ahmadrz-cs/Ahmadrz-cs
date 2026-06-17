<?php

namespace App\Form;

use App\Entity\UserCategorisation;
use App\Form\Type\JsonType;
use App\Form\Type\NullableBoolChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCategorisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $edit_mode = !is_null($builder->getData()?->getId());
        $builder->add('category', EnumType::class, [
            'class' => \App\Entity\Enum\UserCategory::class,
            'disabled' => $edit_mode,
            'help' => 'This cannot be edited after creation. You must add a new categorisation to change the user category.',
            'required' => true,
        ])->add('details', JsonType::class, [
            'attr' => [
                'placeholder' => '{"key": "value"}',
                'style' => 'min-height:5rem;',
                'class' => 'font-monospace',
            ],
            'help' => 'This must be valid JSON. Any additional information provided by the user to support the categorisation claim',
            'label' => 'Supporting Information',
            'required' => false,
        ])->add('verified', NullableBoolChoiceType::class, [
            'help' => 'Whether the categorisation has been manually verified by a member of staff',
            'label' => 'Is Staff Verified?',
            'required' => false,
        ])->add('notes', TextareaType::class, [
            'attr' => [
                'placeholder' => 'Staff notes relating to this categorisation',
                'style' => 'min-height:5rem;',
            ],
            'help' => '240 character limit. Any staff notes relating to this categorisation',
            'label' => 'Staff Notes',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserCategorisation::class,
        ]);
    }
}
