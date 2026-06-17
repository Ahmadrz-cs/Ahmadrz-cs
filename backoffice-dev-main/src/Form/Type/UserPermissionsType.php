<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPermissionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $canCreateSuperAdmin = $options['super_admin'];

        $builder->add('roles', ChoiceType::class, [
            'choices' => $this->getExistingRoles($options),
            'choice_attr' => function ($choice, $key, $value) use (
                $canCreateSuperAdmin,
            ) {
                if ($choice == 'ROLE_SUPER_ADMIN' && $canCreateSuperAdmin) {
                    return ['disabled' => false];
                } elseif ($choice == 'ROLE_SUPER_ADMIN' && !$canCreateSuperAdmin) {
                    return ['disabled' => true];
                }
                return [];
            },
            'label' => 'User Role',
            'expanded' => true,
            'mapped' => true,
            'multiple' => false,
            'data' => $options['user_role'],
        ])->add('submit', SubmitType::class, [
            'label' => 'Save Changes',
        ]);

        $builder->get('roles')->addModelTransformer(
            new CallbackTransformer(
                function ($rolesAsArray) {
                    // transform the array to a string
                    return $rolesAsArray;
                },
                function ($rolesAsString) {
                    // transform the string back to an array
                    return explode(',', $rolesAsString);
                },
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => User::class,
            'super_admin' => false,
            'role_types' => null,
            'user_role' => 'ROLE_USER',
        ]);
    }

    public function getExistingRoles($options): array
    {
        // $roleHierarchy = array_diff(array_keys($options['role_types']), ['ROLE_USER']);
        $roleHierarchy = array_keys($options['role_types']);
        if (!$options['super_admin']) {
            $roleHierarchy = array_diff(
                array_keys($options['role_types']),
                ['ROLE_SUPER_ADMIN'],
            );
        }
        $roleChoices = [];

        foreach ($roleHierarchy as $role) {
            $roleName = ucwords(str_replace(
                '_',
                ' ',
                strtolower(str_replace('ROLE_', '', $role)),
            ));
            $roleChoices[$roleName] = $role;
        }
        return $roleChoices;
    }
}
