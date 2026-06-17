<?php

namespace App\Form\Type;

use App\Entity\Mail;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, [
                'attr' => [
                    'list' => 'slug-suggestions',
                    'placeholder' => 'Use one of the suggestions',
                ],
                'help' => 'Technical name for the email template. Referred to in code. Only slugs in the suggested list are used by the code.',
                'required' => true,
            ])
            ->add('name', TextType::class, [
                'help' => 'Describe what the email is for.',
                'required' => true,
            ])
            ->add('subject', TextType::class, [
                'help' => 'Subject line of the email.',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'attr' => ['style' => 'min-height:100px;'],
                // 'attr' => ['class' => 'd-none'],
                'help' => 'Main email template body. This is in html-twig format. You can use the WYSIWYG editor below to help edit the email body.',
                'required' => true,
                'sanitize_html' => true,
            ])
            ->add('params', TextareaType::class, [
                'attr' => ['style' => 'min-height:100px;'],
                'help' => 'Key-value map of required fields and their type. This is in JSON format.',
                'required' => false,
            ])
            ->add('sendAdmin', CheckboxType::class, [
                'help' => 'Whether a copy of the email should be sent to the admin mailbox.',
                'required' => false,
            ])
            ->add('sendUser', CheckboxType::class, [
                'help' => 'Whether a copy of the email should be sent to the recipient user.',
                'required' => false,
            ])
            ->add('confirmation', CheckboxType::class, [
                'help' => 'I confirm that I have the technical understanding required to edit email templates.',
                'mapped' => false,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Changes',
            ]);

        $builder->get('params')->addModelTransformer(
            new CallbackTransformer(
                fn(?array $paramsAsArray): string => json_encode($paramsAsArray ?? []),
                fn(string $paramsAsJsonString): array => json_decode(
                    $paramsAsJsonString,
                    true,
                ),
            ),
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mail::class,
        ]);
    }
}
