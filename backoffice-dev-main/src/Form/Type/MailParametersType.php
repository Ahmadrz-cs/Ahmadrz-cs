<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailParametersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('params', TextareaType::class, [
            'attr' => [
                'style' => 'min-height:200px;',
                'class' => 'font-monospace',
            ],
            'help' => 'Key-value map of parameters to provide the template. This is in JSON format.',
            'label' => 'Email Template Parameters',
            'required' => false,
        ])->add('submit', SubmitType::class, [
            'label' => 'Send Test Email',
        ])->add('submitAsync', SubmitType::class, [
            'label' => 'Send Test Async Email',
        ]);

        $builder->get('params')->addModelTransformer(
            new CallbackTransformer(
                fn(?array $paramsAsArray): string => json_encode($paramsAsArray ?? []),
                fn(string $paramsAsJsonString): array => json_decode(
                    $paramsAsJsonString,
                    true,
                ),
            ),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
