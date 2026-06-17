<?php

namespace App\Form\Type;

use App\Form\DataTransformer\JsonToTextTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class JsonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new JsonToTextTransformer());
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }
}
