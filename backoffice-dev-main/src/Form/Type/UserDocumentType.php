<?php

namespace App\Form\Type;

use App\Entity\User;
use App\Entity\UserDocument;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['action'] == 'edit') {
            $builder->add('user', UserSelectorType::class, [
                'disabled' => true,
            ])->add('document', DocumentType::class, [
                'action' => 'edit',
            ])->add('submit', SubmitType::class);
        } else {
            $builder->add('user', UserSelectorType::class, [
                'required' => true,
            ])->add('document', CollectionType::class, [
                'entry_type' => DocumentType::class,
                'entry_options' => ['action' => 'add'],
            ])->add('submit', SubmitType::class);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDocument::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'userdocument';
    }
}
