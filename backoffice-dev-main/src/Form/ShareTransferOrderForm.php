<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\Enum\OrderStatus;
use App\Entity\ShareTransferOrder;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShareTransferOrderForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = $builder->getData()->getStatus() != OrderStatus::Draft;
        $builder
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'e.g. Quarterly share transfers',
                    'style' => 'min-height:3rem;',
                ],
                'help' => 'Optional description for the share transfers being filed. Recommended if the search period is greater than 1 month.',
                'label' => 'Description (Optional)',
                'required' => false,
            ])
            ->add('scheduledFor', DateType::class, [
                'disabled' => $options['restricted'],
                'help' => 'When you are filing the share transfers. This should be the current monthend period (start of current month) or the exact day you are filing them.',
                'label' => 'Filing Date/Period',
                'widget' => 'single_text',
            ])
            ->add('periodStart', DateType::class, [
                'disabled' => $options['restricted'],
                'help' => 'Start of search range for investments (createdAt date)',
                'widget' => 'single_text',
            ])
            ->add('periodEnd', DateType::class, [
                'disabled' => $options['restricted'],
                'help' => 'End of search range for investments (createdAt date)',
                'widget' => 'single_text',
            ])
            ->add('repaymentStart', DateType::class, [
                'disabled' => $options['restricted'],
                'help' => 'Start of search range for repayments (createdAt date).',
                'widget' => 'single_text',
            ])
            ->add('repaymentEnd', DateType::class, [
                'disabled' => $options['restricted'],
                'help' => 'End of search range for repayments (createdAt date)',
                'widget' => 'single_text',
            ])
            ->add('asset', EntityType::class, [
                'choice_label' => function ($asset) {
                    return '#'
                    . $asset->getId()
                    . ' // '
                    . $asset->getCompanyNumber()
                    . ' // '
                    . $asset->getName();
                },
                'class' => Asset::class,
                'disabled' => $options['restricted'],
                'help' => 'The asset you are filing share transfers for',
                'label' => 'Asset',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('a');
                    $qb->andWhere($qb->expr()->in('a.id', ':assetIds'))->setParameter(
                        'assetIds',
                        $options['assetIds'],
                    );
                    return $qb;
                },
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShareTransferOrder::class,
            'assetIds' => [],
        ]);
    }
}
