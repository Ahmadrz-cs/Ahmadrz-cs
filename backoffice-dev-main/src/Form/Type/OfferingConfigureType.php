<?php

namespace App\Form\Type;

use App\Entity\Asset;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingConfigureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('asset', EntityType::class, [
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
            ])
            ->add('createdById', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 123',
                ],
                'disabled' => true,
                'help' => 'The user selling the shares. Defaults to superadmin',
                'label' => 'Seller (User ID)',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'That new offering',
                ],
                'help' => 'Maximum of 50 characters. Defaults to the asset name',
                'required' => false,
            ])
            ->add('offeringType', ChoiceType::class, [
                'choices' => [
                    'Prefunding' => 'prefunding',
                    'Retail' => 'retail',
                ],
                'help' => 'Defaults to prefunding',
                'label' => 'Offering Type',
                'required' => true,
            ])
            ->add('pricePerShare', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 1.23',
                ],
                'help' => 'Finest granularity is a whole pence. Any fractional pence will be rounded',
                'label' => 'Share Price (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('noOfShares', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 100000',
                ],
                'help' => 'Defaults to the asset if not set',
                'label' => 'Number of shares',
                'required' => false,
            ])
            ->add('offeringTerm', IntegerType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 5',
                ],
                'help' => 'Defaults to 5 years. Yes, this is different to asset which uses months!😒',
                'label' => 'Investment term length (years)',
                'required' => false,
            ])
            ->add('fundingGoal', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 123000',
                ],
                'disabled' => true,
                'label' => 'Valuation (£)',
                'help' => 'The funding goal derived from share price and number of shares. Will autogenerate once both are set',
                'required' => false,
            ])
            ->add('netRentProjected', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 4.96',
                ],
                'help' => 'Rental dividends expected per year',
                'label' => 'Annual Expected Return (%)',
                'required' => false,
            ])
            ->add('grossProjectReturn', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 28.46',
                ],
                'help' => 'Cumulative rental dividends plus any capital gains expected',
                'label' => 'Total Expected Return (%)',
                'required' => false,
            ])
            ->add('minCommitUser', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. 100',
                ],
                'help' => 'This will be rounded UP to the next multiple of the share price. Defaults to just over 100',
                'label' => 'Minimum Single Commit/Investment (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('maxCommitUser', NumberType::class, [
                'help' => 'This cannot be lower than the minimum (unless empty or 0) and will be rounded DOWN to the previous multiple of the share price.',
                'label' => 'Maximum Single Commit/Investment (£)',
                'required' => false,
                'scale' => 2,
            ])
            ->add('isFeatured', CheckboxType::class, [
                'help' => 'Featured properties are promoted on the homepage',
                'required' => false,
            ])
            ->add('visibility', VisibilityType::class, [
                'help' => 'Manual override for who should be able to see this offering on frontent. Defaults to Auto',
                'required' => true,
            ])
            ->add('lifecycleStatus', ChoiceType::class, [
                'choices' => [
                    OfferingLifecycle::STATE_DRAFT,
                    OfferingLifecycle::STATE_SUBMITTED,
                    OfferingLifecycle::STATE_APPROVED,
                    OfferingLifecycle::STATE_PUBLISHED,
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return ucfirst($value);
                },
                'disabled' => true,
                'help' => 'Create offering in a particular state. Defaults to Draft. ',
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Changes',
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
