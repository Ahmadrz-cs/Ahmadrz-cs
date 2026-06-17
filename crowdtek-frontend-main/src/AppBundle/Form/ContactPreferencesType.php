<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class ContactPreferencesType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('gdpr_accepted', CheckboxType::class, [
                'label' => 'contact_preference',
                'required' => false,
            ])
            ->add('Submit', SubmitType::class, ['attr' => ['class' => 'btn btn-primary btn-popup rounded-pill px-2']]);
    }

    public function getBlockPrefix(): string
    {
        return 'gdpr_accepted';
    }
}
