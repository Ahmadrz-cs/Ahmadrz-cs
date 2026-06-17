<?php

namespace App\Form\Type;

use App\Entity\AssessmentResponse;
use App\Entity\QuestionChoice;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssessmentResponseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!is_null($options['question'])) {
            /**
             * @var Collection<int, QuestionChoice>
             */
            $questionChoices = $options['question']?->getChoices();
            if ($questionChoices) {
                $questionChoices->filter(fn($qc) => $qc->isActive());
            }
            $questionChoices = $questionChoices->toArray();
            shuffle($questionChoices);
            $builder->add('choice', EntityType::class, [
                'choice_label' => 'content',
                'choices' => $questionChoices,
                'class' => QuestionChoice::class,
                'expanded' => true,
                // 'help' => 'Multiple choice options for the question',
                'required' => true,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssessmentResponse::class,
            'question' => null,
        ]);
    }
}
