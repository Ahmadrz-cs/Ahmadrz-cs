<?php

namespace App\Form\Type;

use App\Entity\KycReport;
use App\Entity\User;
use App\Service\ContegoKycService;
use App\Service\MangopayKycService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class KycReportFormType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', UserSelectorType::class, [
                'help' => 'Who was the KYC check performed on?',
                'required' => true,
            ])
            ->add('providerName', ChoiceType::class, [
                'choices' => [
                    'Mangopay' => MangopayKycService::PROVIDER_NAME,
                    'Contego' => ContegoKycService::PROVIDER_NAME,
                ],
                'help' => 'Which provider performed the KYC check?',
                'placeholder' => 'Please choose a KYC provider',
                'required' => true,
            ])
            ->add('providerReferenceId', TextType::class, [
                'attr' => [
                    'placeholder' => 'The identifier for the KYC check',
                ],
                'help' => 'The reference number or id of the KYC check. This varies by the provider.',
                'required' => true,
            ])
            ->add('checkType', TextType::class, [
                'attr' => [
                    'placeholder' => 'e.g. individual, business, document, etc',
                ],
                'help' => 'Describe what type of KYC check was performed.',
                'required' => true,
            ])
            ->add('verified', CheckboxType::class, [
                'help' => 'Whether the result constitutes a pass KYC check. Leave unchecked if failed.',
                'label' => 'Is user KYC Verified?',
                'required' => false,
            ])
            ->add('result', ChoiceType::class, [
                'choices' => [
                    'LIGHT',
                    'REGULAR',
                    'RED',
                    'AMBER',
                    'GREEN',
                ],
                'choice_label' => function ($choice, $key, $value) {
                    return $value;
                },
                'help' => 'What was the result for the KYC check.
                    For Mangopay, this is either LIGHT or REGULAR.
                    For Contego, this is either RED, AMBER, or GREEN.',
                'required' => true,
            ])
            ->add('score', NumberType::class, [
                'attr' => [
                    'placeholder' => 'e.g. individual, business, document, etc',
                ],
                'help' => 'Any (numerical) score associated with the KYC check.
                    For Mangopay, use 1 for REGULAR/pass or 0 for LIGHT/fail.
                    For Contego, this is a numerical score.',
                'required' => true,
            ])
            ->add('checkedAt', DateTimeType::class, [
                'date_widget' => 'single_text',
                'help' => 'When was the KYC check performed?',
                'required' => true,
                'time_widget' => 'single_text',
            ])
            ->add('note', TextType::class, [
                'attr' => [
                    'placeholder' => 'This is optional',
                ],
                'help' => 'Any additional information related to this KYC check',
                'required' => false,
            ])
            ->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KycReport::class,
            'empty_data' => null,
        ]);
    }

    /**
     * @param KycReport|null $viewData
     */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        // there is no data yet, so nothing to prepopulate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof KycReport) {
            throw new UnexpectedTypeException($viewData, KycReport::class);
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // initialize form field values
        $forms['user']->setData($viewData->subject);
        $forms['providerName']->setData($viewData->providerName);
        $forms['providerReferenceId']->setData($viewData->providerReferenceId);
        $forms['checkType']->setData($viewData->checkType);
        $forms['result']->setData($viewData->result);
        $forms['score']->setData($viewData->score);
        $forms['verified']->setData($viewData->verified);
        $forms['checkedAt']->setData($viewData->checkedAt);
        $forms['note']->setData($viewData->note);
    }

    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        // as data is passed by reference, overriding it will change it in
        // the form object as well
        $viewData = new KycReport(
            $forms['user']->getData(),
            $forms['providerName']->getData(),
            $forms['providerReferenceId']->getData(),
            $forms['checkType']->getData(),
            $forms['result']->getData(),
            $forms['score']->getData(),
            $forms['verified']->getData(),
            $forms['checkedAt']->getData(),
            $forms['note']->getData(),
        );
    }
}
