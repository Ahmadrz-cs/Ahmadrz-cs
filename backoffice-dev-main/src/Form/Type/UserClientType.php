<?php

namespace App\Form\Type;

use App\Entity\UserClient;
use App\Form\DataTransformer\UserToNumberTransformer;
use App\Form\Type\OauthClientType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserClientType extends AbstractType
{
    public function __construct(
        private UserToNumberTransformer $userTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readOnly = $options['read_only'];
        $scopes = $options['oauth_scopes'];

        $builder->add('user', NumberType::class, [
            'attr' => [
                'placeholder' => 'Associate the client with a user by their ID',
            ],
            'disabled' => $readOnly,
            'label' => 'User Id',
            'required' => true,
        ])->add('alias', TextType::class, [
            'attr' => [
                'placeholder' => 'Short human readable name for the client or application, e.g. yielderverse',
            ],
            'disabled' => $readOnly,
            'required' => false,
        ])->add('description', TextType::class, [
            'attr' => [
                'placeholder' => 'Description of the application that the client is for',
            ],
            'disabled' => $readOnly,
            'required' => false,
        ])->add('client', OauthClientType::class, [
            'disabled' => $readOnly,
            'oauth_scopes' => $scopes,
        ])->add('submit', SubmitType::class);

        $builder->get('user')->addModelTransformer($this->userTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserClient::class,
            'read_only' => true,
            'oauth_scopes' => [],
        ]);
    }
}
