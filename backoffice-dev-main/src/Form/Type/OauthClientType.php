<?php

namespace App\Form\Type;

use App\Form\DataMapper\ClientDataMapper;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;

class OauthClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $scopes = $options['oauth_scopes'];
        $grants = [
            'Client Credentials' => 'client_credentials',
            'Password (ROPC)' => 'password',
            'Authorization Code' => 'authorization_code',
            'Refresh Token' => 'refresh_token',
        ];

        $builder
            ->add('identifier', TextType::class, [
                'disabled' => true,
                'label' => 'Client Identifier',
            ])
            ->add('redirectUris', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'text-monospace',
                    'placeholder' => 'e.g. https://example.com/authcallback',
                    'style' => 'min-height: 8rem',
                ],
                // 'entry_type' => TextType::class
            ])
            ->add('grants', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $grants,
                'constraints' => [
                    new Choice([
                        'choices' => array_values($grants),
                        'min' => 1,
                        'multiple' => true,
                    ]),
                ],
            ])
            ->add('scopes', ChoiceType::class, [
                'multiple' => true,
                'expanded' => true,
                'choices' => $scopes,
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
                'constraints' => [
                    new Choice([
                        'choices' => $scopes,
                        'min' => 1,
                        'multiple' => true,
                    ]),
                ],
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
            ])
            ->setDataMapper(new ClientDataMapper());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            'oauth_scopes' => [],
        ]);
    }
}
