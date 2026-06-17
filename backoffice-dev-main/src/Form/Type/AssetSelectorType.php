<?php

namespace App\Form\Type;

use App\Form\DataTransformer\AssetToNumberTransformer;
use App\Repository\AssetRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetSelectorType extends AbstractType
{
    private array $choices = [];

    public function __construct(
        private AssetToNumberTransformer $transformer,
        private AssetRepository $repository,
    ) {
        foreach ($this->repository->getAsIdAndIdentifier() as $entityIdAndName) {
            $label = "#{$entityIdAndName['id']} // {$entityIdAndName['companyNumber']} // {$entityIdAndName['identifier']}";
            $this->choices[$label] = $entityIdAndName['id'];
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'invalid_message' => 'The selected asset does not exist',
            'choices' => $this->choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
