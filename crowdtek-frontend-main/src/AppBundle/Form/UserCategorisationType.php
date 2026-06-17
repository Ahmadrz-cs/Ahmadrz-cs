<?php

namespace AppBundle\Form;

use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\UserCategorisation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserCategorisationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Restricted' => UserCategory::Restricted,
                    'Sophisticated' => UserCategory::Sophisticated,
                    'High Net Worth' => UserCategory::HighNetWorth,
                ],
                'expanded' => true,
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserCategorisation::class,
        ]);
    }
}
