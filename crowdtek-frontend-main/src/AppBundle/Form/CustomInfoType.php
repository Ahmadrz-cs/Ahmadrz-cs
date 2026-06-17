<?php

/**
 * Created by PhpStorm.
 * User: khoa.nguyen
 * Date: 9/8/2015
 * Time: 2:15 PM
 */

namespace AppBundle\Form;

use AppBundle\Util\Util;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class CustomInfoType extends AbstractType
{
    private $user;

    private function getAllCountries()
    {
        \Locale::setDefault('en');
        $countries = Countries::getNames();
        //        die(print_r($countries,1));

        $arr = [];
        if (is_array($countries)) {
            $arr = $countries; //array_combine($countries, $countries);
        } else {
            $arr = ['GB' => 'United Kingdom']; //return common countries
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
            //                ->add('building_number', TextType::class, array(
            //                    'required' => false,
            //                    'data' => Util::getInfo($this->user, 'building_number', '')
            //                ))
            ->add('building_name', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'building_name', '')
            ])->add('income_range', ChoiceType::class, [
                'choices' => [
                    '1' => '<£20,000',
                    '2' => '£20,000-£40,000',
                    '3' => '£40,000-£60,000',
                    '4' => '£60,000-£100,000',
                    '5' => '£100,000+'
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'income_range', '1')
            ])->add('cxb_worth_investor', CheckboxType::class, [
                'label' => 'I hereby sign the above statement to declare that as of today I am a High Net Worth Investor as described above.',
                'required' => false, 'data' => (bool) Util::getInfo($this->user, 'cxb_worth_investor', false)
            ])->add('cxb_sophisticated_investor', CheckboxType::class, [
                'label' => 'I hereby sign the above statement to declare that as of today I am a Sophisticated Investor as described above.',
                'required' => false, 'data' => (bool) Util::getInfo($this->user, 'cxb_sophisticated_investor', false)
            ])->add('cxb_restricted_investor', CheckboxType::class, [
                'label' => 'I hereby sign the above statement to declare that as of today I am a Restricted Investor as described above.',
                'required' => false, 'data' => (bool) Util::getInfo($this->user, 'cxb_restricted_investor', false)
            ])->add('cxb_ltd_company_investor', CheckboxType::class, [
                'label' => 'I hereby sign the above statement to declare that as of today I am a Limited Company Investor as described above.',
                'required' => false, 'data' => (bool) Util::getInfo($this->user, 'cxb_ltd_company_investor', false)
            ])->add('always_go_up', ChoiceType::class, [
                'choices' => [
                    'Yes' => 'Yes, property prices always go up',
                    'No' => 'No, property prices can go down as well as up',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'always_go_up', 'No')
            ])->add('income_every_month', ChoiceType::class, [
                'choices' => [
                    'Yes' => 'Yes that will always be the case',
                    'No' => 'No, if costs exceed income I may not receive anything that month',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'income_every_month', 'No')
            ])->add('sell_my_investment', ChoiceType::class, [
                'choices' => [
                    '1' => 'I can do so through the secondary market whenever I like, and there will always be a buyer at the price I choose',
                    '2' => 'I can do so through the secondary market whenever I like, but there is no guarantee that anyone will be willing to buy my investment. In this instance I may have to wait until the property is sold at the end of the investment term to receive the value of my investment.',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'sell_my_investment', 'No')
            ])->add('never_exit', ChoiceType::class, [
                'choices' => [
                    'Yes' => 'Yes I will never be required to exit my investment',
                    'No' => 'No I may be required to exit my investment in certain instances, for example where over 75% of shareholders vote to sell a property before the expiry of an investment term.',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'never_exit', 'No')
            ])->add('my_total_return', ChoiceType::class, [
                'choices' => [
                    '1' => 'Movement in the property value only',
                    '2' => 'Dividends generated from rental of the property and movements in the property\'s value, less costs associated with both.',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'my_total_return', 0)
            ])->add('best_practice_involve', ChoiceType::class, [
                'choices' => [
                    '1' => 'Investing all of my money in one property',
                    '2' => 'Spreading my investment across multiple properties',
                ],
                'placeholder' => false,
                'required' => false,
                'expanded' => true,
                'multiple' => false,
                'data' => Util::getInfo($this->user, 'best_practice_involve', 0)
            ])->add('corporate_investor', CheckboxType::class, [ // Investing through a limited company
                'label' => 'Investing through a limited company.',
                'required' => false, 'data' => (bool) Util::getInfo($this->user, 'corporate_investor', false)
            ])->add('company_name', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_name', '')
            ])->add('company_registration_country', ChoiceType::class, [
                'required' => false,
                'choices' => $this->getAllCountries(), 'preferred_choices' => ['GB'],
                'data' => Util::getInfo($this->user, 'company_registration_country', '')
            ])->add('company_registered_number', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_registered_number', '')
            ])->add('company_nature_of_business', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_nature_of_business', '')
            ])->add('company_telephone', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_telephone', '')
            ])->add('company_other_name', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_other_name', '')
            ])->add('company_website', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_website', '')
            ])
            ->add('company_registered_address_1', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_registered_address_1', '')
            ])
            ->add('company_registered_address_2', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_registered_address_2', '')
            ])
            ->add('company_registered_address_3', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_registered_address_3', '')
            ])
            ->add('company_postcode', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_postcode', '')
            ])
            ->add('company_operating_address', TextType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'company_operating_address', '')
            ])
            ->add('company_beneficial_owners', HiddenType::class)
            ->add('company_directors', HiddenType::class)
            ->add('words_of_your_own', TextareaType::class, [
                'required' => false,
                'data' => Util::getInfo($this->user, 'words_of_your_own', ''),
                'constraints' => [new Length(['max' => 800])]
            ]);
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
        return 'custom_info_type';
    }
}
