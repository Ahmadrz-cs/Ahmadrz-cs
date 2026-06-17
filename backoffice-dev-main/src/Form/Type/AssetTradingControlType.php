<?php

namespace App\Form\Type;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetTradingControlType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('buyRestricted', CheckboxType::class, [
            'help' => 'Prevent any user investing (buying shares) in this asset/product',
            'required' => false,
        ])->add('sellRestricted', CheckboxType::class, [
            'help' => 'Prevent shareholders selling shares in this asset/product. Enabling this will close the secondary market for this asset.',
            'required' => false,
        ])->add('featured', IntegerType::class, [
            'help' => 'Whether to feature this asset/product with a weighting. 0 (zero) means not featured. Any positive number means featured, ranked by largest weighting first.',
            'required' => false,
        ])->add('visibility', VisibilityType::class, [
            'help' => 'Configure which user groups are able to see this asset/product',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
