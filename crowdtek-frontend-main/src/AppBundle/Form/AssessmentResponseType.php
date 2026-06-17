<?php

namespace AppBundle\Form;

use AppBundle\Entity\AssessmentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssessmentResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * @var ?AssessmentResponse $assessmentResponse
         */
        $assessmentResponse = $builder->getData();
        $question = $assessmentResponse->question;
        if (!is_null($question)) {
            $builder
                ->add('choice', ChoiceType::class, [
                    'choice_label' => function ($choice, string $key, mixed $value): string {
                        return $choice->content;
                    },
                    'choices' => $question->choices,
                    'expanded' => true,
                    'label' => $question->content,
                    'required' => true,
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssessmentResponse::class,
        ]);
    }
}
