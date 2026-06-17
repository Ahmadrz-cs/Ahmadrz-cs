<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractQueryType extends AbstractType
{
    /**
     * Use this abstract form type if you're using them
     * for query parameter generation and capture
     * - Always GET method
     * - CSRF is disabled as we're using simple GET requests
     * - Action defaults to the "current page"
     * - No form block prefix, to reduce query parameter clutter
     *
     * You can still override the defaults back to POST action
     * However, this removes the ability to provide links for
     * filter presets
     */

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'user_filters' => true,
            'asset_filters' => true,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }
}
