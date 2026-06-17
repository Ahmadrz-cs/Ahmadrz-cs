<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 23/02/17
 * Time: 22:33
 */

namespace App\Form\Type;

use App\Entity\Investor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserInvestorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cxbWorthInvestor', CheckboxType::class, ['required' => false])
            ->add('cxbSophisticatedInvestor', CheckboxType::class, [
                'required' => false,
            ])
            ->add('cxbRestrictedUser', CheckboxType::class, ['required' => false])
            ->add('cxbLtdCompInvestor', CheckboxType::class, ['required' => false])
            ->add('alwaysGoUp', TextType::class, ['required' => false])
            ->add('incomeEveryMonth', TextType::class, ['required' => false])
            ->add('neverExit', TextType::class, ['required' => false])
            ->add('poiFileId', TextType::class, ['required' => false])
            ->add('wordsOfOwn', TextType::class, ['required' => false])
            ->add('corporateInvestor', CheckboxType::class, ['required' => false]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Investor::class,
        ]);
    }
}
