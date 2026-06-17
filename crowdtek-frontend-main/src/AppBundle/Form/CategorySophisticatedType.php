<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class CategorySophisticatedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('experience', ChoiceType::class, [
                'choices' => [
                    'Worked in private equity or in the provision of finance for small and medium enterprises' => 'sme private equity',
                    'Been the directory of a company with an annual turnover of at least £1 million' => 'director',
                    'Made two or more investments in an unlisted company' => 'unlisted investments xp',
                    'Been a member of a network or synicate of business angels for more than dix months' => 'network or syndicate',
                ],
                'expanded' => true,
                'label' => 'Experience in last 2 years',
                'required' => true,
            ])
            ->add('info', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Acme Ltd',
                ],
                'constraints' => [new GreaterThanOrEqual(0)],
                'label' => 'Specify the name/business/company/organisation you worked for or were a director of, or number of investments as applicable.',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
