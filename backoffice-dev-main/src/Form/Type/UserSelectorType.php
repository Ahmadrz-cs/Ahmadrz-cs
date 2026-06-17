<?php

namespace App\Form\Type;

use App\Form\DataTransformer\UserToNumberTransformer;
use App\Repository\UserRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSelectorType extends AbstractType
{
    private array $choices = [];

    public function __construct(
        private UserToNumberTransformer $transformer,
        private UserRepository $repository,
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
            'invalid_message' => 'The selected user does not exist',
            'choices' => $this->choices,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
