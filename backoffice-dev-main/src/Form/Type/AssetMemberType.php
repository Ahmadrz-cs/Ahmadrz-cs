<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 07/02/17
 * Time: 10:15
 */

namespace App\Form\Type;

use App\Entity\AssetMember;
use App\Form\DataTransformer\UserToIdentifierTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetMemberType extends AbstractType
{
    public function __construct(
        private UserToIdentifierTransformer $userTransformer,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('user', TextType::class, [
            'label' => 'Member username',
            'required' => true,
        ])->add('membertype', TextType::class, ['required' => true]);

        $builder->get('user')->addModelTransformer($this->userTransformer);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssetMember::class,
        ]);
    }
}
