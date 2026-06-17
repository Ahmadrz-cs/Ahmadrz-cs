<?php

namespace App\Form\Type;

use App\Form\DataTransformer\OfferingToNumberTransformer;
use App\Repository\OfferingRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OfferingSelectorType extends AbstractType
{
    private array $choices = [];

    public function __construct(
        private OfferingToNumberTransformer $transformer,
        private OfferingRepository $repository,
    ) {
        foreach ($this->repository->getAsIdAndIdentifier() as $entityIdAndName) {
            $label = "#{$entityIdAndName['id']} // {$entityIdAndName['identifier']}";
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
            'invalid_message' => 'The selected investment does not exist',
            'choices' => $this->choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
