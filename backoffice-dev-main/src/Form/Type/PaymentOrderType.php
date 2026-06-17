<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\PaymentOrder;
use App\Service\PaymentService;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Workflow\WorkflowInterface;

class PaymentOrderType extends AbstractType
{
    public function __construct(
        private WorkflowInterface $paymentOrderStateMachine,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['restricted'] = !$this->paymentOrderStateMachine->can(
            $builder->getData(),
            'approve',
        );
        $options['assetRestricted'] =
            $options['restricted'] || count($builder->getData()->getPayments());
        $builder->add('description', TextareaType::class, [
            'attr' => [
                'placeholder' => 'e.g. Dividend retrospective top-up for month 2020-05',
                'style' => 'min-height:3rem;',
            ],
            'help' => 'A description is recommended for orders where you intend to manually edit payments',
            'label' => 'Description (optional)',
            'required' => false,
        ])->add('scheduledFor', DateType::class, [
            'disabled' => $options['restricted'],
            'help' => 'The due date to set for payments',
            'widget' => 'single_text',
        ])->add('paymentType', ChoiceType::class, [
            'choices' => [
                PaymentService::TYPE_DIVIDEND => PaymentService::TYPE_DIVIDEND,
                PaymentService::TYPE_REPAYMENT . ' (liquidation)' =>
                    PaymentService::TYPE_REPAYMENT,
                PaymentService::TYPE_DIVESTMENT . ' (liquidation)' =>
                    PaymentService::TYPE_DIVESTMENT,
                PaymentService::TYPE_INVESTMENT_EXIT . ' (liquidation)' =>
                    PaymentService::TYPE_INVESTMENT_EXIT,
            ],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($key);
            },
            'disabled' => $options['restricted'],
            'expanded' => true,
            'help' => 'The payment type will appear in the Mangopay transfer tag. Note that while dividends do not change the shareholding, all other types are forms of liquidation and will reduce the shareholding.',
            'label' => 'Payment Type',
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
            'disabled' => $options['assetRestricted'],
            'help' => 'Cannot be changed if there are payments in the order',
            'query_builder' => function (EntityRepository $er) use ($options) {
                $qb = $er->createQueryBuilder('a');
                $qb->andWhere($qb->expr()->in('a.id', ':assetIds'))->setParameter(
                    'assetIds',
                    $options['assetIds'],
                );
                return $qb;
            },
        ])->add('debitWallet', ChoiceType::class, [
            'choices' => [
                'main',
                'distribution',
            ],
            'choice_label' => function ($choice, $key, $value) {
                return ucfirst($value);
            },
            'disabled' => $options['restricted'],
            'help' => 'Select the wallet that contains the funds to pay shareholders',
            'label' => 'Pay From Wallet',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentOrder::class,
            'restricted' => false,
            'assetIds' => [],
        ]);
    }
}
