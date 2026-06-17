<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Enum\TransferType;
use App\Entity\TransferOrder;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class TransferOrderType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $transferOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->transferOrderStateMachine->can(
            $builder->getData(),
            'approve',
        );
        $builder->add('transferType', EnumType::class, [
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'class' => TransferType::class,
            'help' => 'Defaults to custom. Leave this as the default if you are not sure',
            'disabled' => $options['restricted'],
        ])->add('description', TextareaType::class, [
            'attr' => [
                'placeholder' => 'e.g. SPVT0012 - Month-end gross income processing',
                'style' => 'min-height:3rem;',
            ],
            'help' => 'Describe what activity/purpose the transfers are for',
            'label' => 'Description',
        ])->add('scheduledFor', DateType::class, [
            'disabled' => $options['restricted'],
            'help' => 'When you expect these transfers to be made',
            'widget' => 'single_text',
        ])->add('asset', EntityType::class, [
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
            'help' => 'If applicable, which asset are the transfers associated with?',
            'label' => 'Associated Asset (Optional)',
            'placeholder' => 'None',
            'query_builder' => function (EntityRepository $er) use ($options) {
                $qb = $er->createQueryBuilder('a');
                $qb->andWhere($qb->expr()->in('a.id', ':assetIds'))->setParameter(
                    'assetIds',
                    $options['assetIds'],
                );
                return $qb;
            },
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TransferOrder::class,
            'assetIds' => [],
        ]);
    }
}
