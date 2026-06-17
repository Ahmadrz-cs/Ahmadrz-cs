<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 2:15 PM
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\FormBuilderInterface;

class FileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('images_front', HiddenType::class)
            ->add('floor_plan', HiddenType::class)
            ->add('documents', HiddenType::class)
            ->add('logo', HiddenType::class)
            ->add('media', HiddenType::class)
            ->add('spv', HiddenType::class)
            ->add('dynamic_image', HiddenType::class);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'file_type';
    }
}
