<?php

namespace App\Form\Type;

use App\Entity\Asset;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetRelationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add('asset', EntityType::class, [
            //     'choice_label' => function ($asset) {
            //         return '#' . $asset->getId()
            //             . ' // ' . $asset->getCompanyNumber()
            //             . ' // ' . $asset->getName();
            //     },
            //     'class' => Asset::class,
            //     'help' => "Which asset is this order for?",
            //     // 'query_builder' => function (EntityRepository $er) use ($options) {
            //     //     $qb = $er->createQueryBuilder('a');
            //     //     $qb->andWhere($qb->expr()->in('a.id', ':assetIds'))
            //     //         ->setParameter('assetIds', $options['assetIds']);
            //     //     return $qb;
            //     // },
            // ])
            ->add('asset', AssetSelectorType::class, [
                'help' => 'Which asset is this order for?',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => TransferOrder::class,
        ]);
    }
}
