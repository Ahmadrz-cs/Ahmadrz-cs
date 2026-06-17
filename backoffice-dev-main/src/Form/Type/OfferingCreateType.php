<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingCreateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('asset', EntityType::class, [
            'choice_label' => function ($asset) {
                return '#' . $asset->getId() . ' // ' . $asset->getName();
            },
            'class' => Asset::class,
            'help' => 'Only published assets are shown',
            'query_builder' => function (EntityRepository $er) {
                return $er
                    ->createQueryBuilder('a')
                    ->leftJoin('a.assetStatus', 'assetStatus')
                    ->andWhere('assetStatus.lifecycleStatus = :status')
                    ->setParameter('status', 'published');
            },
            'required' => true,
        ])->add('createdById', IntegerType::class, [
            'attr' => [
                'placeholder' => 'e.g. 123',
            ],
            'help' => 'The user selling the shares. Defaults to superadmin',
            'label' => 'Seller (User ID)',
            'required' => true,
        ])->add('lifecycleStatus', ChoiceType::class, [
            'choices' => [
                OfferingLifecycle::STATE_DRAFT,
                OfferingLifecycle::STATE_SUBMITTED,
                OfferingLifecycle::STATE_APPROVED,
                OfferingLifecycle::STATE_PUBLISHED,
            ],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'help' => 'Create offering in a particular state. Defaults to Draft. ',
            'required' => true,
        ])->add('submit', SubmitType::class, [
            'label' => 'Create New Offering',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Offering::class,
        ]);
    }
}
