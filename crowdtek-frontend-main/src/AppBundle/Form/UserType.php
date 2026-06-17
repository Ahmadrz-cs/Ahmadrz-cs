<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    private $user;

    private function getAllCountries()
    {
        \Locale::setDefault('en');
        $countries = Countries::getNames();
        $arr = [];
        if (is_array($countries)) {
            $arr = array_combine($countries, $countries);
        } else {
            $arr = ['United Kingdom' => 'United Kingdom']; //return common countries
        }
        return $arr;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->user = $options['user'];

        $builder
            ->add('given_name', TextType::class, [
                'required' => false, 'data' => isset($this->user['given_name']) ? $this->user['given_name'] : ''
            ])
            ->add('family_name', TextType::class, [
                'required' => false, 'data' => isset($this->user['family_name']) ? $this->user['family_name'] : ''
            ])
            ->add('phone_1', TextType::class, [
                'required' => false, 'data' => isset($this->user['phone_1']) ? $this->user['phone_1'] : ''
            ])
            ->add('phone_2', TextType::class, [
                'required' => false, 'data' => isset($this->user['phone_2']) ? $this->user['phone_2'] : ''
            ])
            ->add('confirm_gdpr_kyc', CheckboxType::class, [
                'label' => 'I provide consent for Yielders to send me details regarding its products and services, marketing information, news and feedback',
                'required' => true
            ])
            ->add('nationality', ChoiceType::class, [
                'choices' => $this->getAllCountries(), 'preferred_choices' => ['United Kingdom'],
                'required' => false,
                'data' => isset($this->user['nationality']) ? $this->user['nationality'] : ''
            ])
            ->add('additional_name', TextType::class, [
                'required' => false,
                'data' => isset($this->user['additional_name']) ? $this->user['additional_name'] : ''
            ])
            ->add('job_title', TextType::class, [
                'required' => false,
                'data' => isset($this->user['job_title']) ? $this->user['job_title'] : ''
            ])
            ->add(
                'gender',
                ChoiceType::class,
                [
                    'choices' => ['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'],
                    'data' => $this->user['gender']
                ]
            )
            ->add('POIFile1', FileType::class, ['label' => 'Identity1'])
            ->add('POIFile2', FileType::class, ['label' => 'Identity2'])
            ->add('POIFile3', FileType::class, ['label' => 'Identity3'])
            ->add('tab', HiddenType::class)
            ->add('birth_date', HiddenType::class)
            ->add('info', CustomInfoType::class, ['user' => $this->user])
            ->add('address', UserAddressType::class, ['user' => $this->user])
            ->add('file', UserFileType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'user' => null,
        ]);
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getBlockPrefix(): string
    {
        return 'user_form_type';
    }
}
