<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 31/08/18
 * Time: 15:04
 */

namespace AppBundle\Entity;

class UserCustomInfo
{
    private $contact_via_email;

    private $contact_via_tele;

    private $contact_via_sms;

    private $investor_type = 0;

    private $fatca = false;

    private $agree_terms_of_marketing = 0;

    private $cxb_restricted_investor = 0;

    private $cxb_worth_investor = 0;

    private $cxb_sophisticated_investor = 0;

    private $cxb_ltd_company_investor;

    private $agree_terms_of_investor = 0;

    private $question1 = 0;

    private $question2 = 0;

    private $question3 = 0;

    private $question4 = 0;

    private $question5 = 0;

    private $question6 = 0;

    private $question7 = 0;

    private $question8 = 0;

    private $question9 = 0;

    private $question10 = 0;

    private $questionnaire_passed = 0;

    private $questionnaire_attempts = 0;

    private $salesforce_id = 0;

    private $referral_link = '';

    /**
     * @return mixed
     */
    public function getContactViaEmail()
    {
        return $this->contact_via_email;
    }

    /**
     * @param mixed $contact_via_email
     */
    public function setContactViaEmail($contact_via_email)
    {
        $this->contact_via_email = $contact_via_email;
    }

    /**
     * @return mixed
     */
    public function getContactViaTele()
    {
        return $this->contact_via_tele;
    }

    /**
     * @param mixed $contact_via_tele
     */
    public function setContactViaTele($contact_via_tele)
    {
        $this->contact_via_tele = $contact_via_tele;
    }

    /**
     * @return mixed
     */
    public function getContactViaSms()
    {
        return $this->contact_via_sms;
    }

    /**
     * @param mixed $contact_via_sms
     */
    public function setContactViaSms($contact_via_sms)
    {
        $this->contact_via_sms = $contact_via_sms;
    }

    /**
     * @return mixed
     */
    public function getInvestorType()
    {
        return $this->investor_type;
    }

    /**
     * @param mixed $investor_type
     */
    public function setInvestorType($investor_type)
    {
        $this->investor_type = $investor_type;
    }

    /**
     * @param boolean $fatca
     */
    public function setFatca($fatca)
    {
        $this->fatca = $fatca;
    }

    /**
     * @return boolean
     */
    public function isFatca()
    {
        return $this->fatca;
    }


    /**
     * @return mixed
     */
    public function getAgreeTermsOfMarketing()
    {
        return $this->agree_terms_of_marketing;
    }

    /**
     * @param mixed $agree_terms_of_marketing
     */
    public function setAgreeTermsOfMarketing($agree_terms_of_marketing)
    {
        $this->agree_terms_of_marketing = $agree_terms_of_marketing;
    }

    /**
     * @return mixed
     */
    public function getCxbRestrictedInvestor()
    {
        return $this->cxb_restricted_investor;
    }

    /**
     * @param mixed $cxb_restricted_investor
     */
    public function setCxbRestrictedInvestor($cxb_restricted_investor)
    {
        $this->cxb_restricted_investor = $cxb_restricted_investor;
    }

    /**
     * @return mixed
     */
    public function getCxbWorthInvestor()
    {
        return $this->cxb_worth_investor;
    }

    /**
     * @param mixed $cxb_worth_investor
     */
    public function setCxbWorthInvestor($cxb_worth_investor)
    {
        $this->cxb_worth_investor = $cxb_worth_investor;
    }

    /**
     * @return mixed
     */
    public function getCxbSophisticatedInvestor()
    {
        return $this->cxb_sophisticated_investor;
    }

    /**
     * @param mixed $cxb_sophisticated_investor
     */
    public function setCxbSophisticatedInvestor($cxb_sophisticated_investor)
    {
        $this->cxb_sophisticated_investor = $cxb_sophisticated_investor;
    }

    /**
     * @return mixed
     */
    public function getCxbLtdCompanyInvestor()
    {
        return $this->cxb_ltd_company_investor;
    }

    /**
     * @param mixed $cxb_ltd_company_investor
     */
    public function setCxbLtdCompanyInvestor($cxb_ltd_company_investor)
    {
        $this->cxb_ltd_company_investor = $cxb_ltd_company_investor;
    }

    /**
     * @return mixed
     */
    public function getAgreeTermsOfInvestor()
    {
        return $this->agree_terms_of_investor;
    }

    /**
     * @param mixed $agree_terms_of_investor
     */
    public function setAgreeTermsOfInvestor($agree_terms_of_investor)
    {
        $this->agree_terms_of_investor = $agree_terms_of_investor;
    }

    /**
     * @return mixed
     */
    public function getQuestion1()
    {
        return $this->question1;
    }

    /**
     * @param mixed $question1
     */
    public function setQuestion1($question1)
    {
        $this->question1 = $question1;
    }

    /**
     * @return mixed
     */
    public function getQuestion2()
    {
        return $this->question2;
    }

    /**
     * @param mixed $question2
     */
    public function setQuestion2($question2)
    {
        $this->question2 = $question2;
    }

    /**
     * @return mixed
     */
    public function getQuestion3()
    {
        return $this->question3;
    }

    /**
     * @param mixed $question3
     */
    public function setQuestion3($question3)
    {
        $this->question3 = $question3;
    }

    /**
     * @return mixed
     */
    public function getQuestion4()
    {
        return $this->question4;
    }

    /**
     * @param mixed $question4
     */
    public function setQuestion4($question4)
    {
        $this->question4 = $question4;
    }

    /**
     * @return mixed
     */
    public function getQuestion5()
    {
        return $this->question5;
    }

    /**
     * @param mixed $question5
     */
    public function setQuestion5($question5)
    {
        $this->question5 = $question5;
    }

    /**
     * @return mixed
     */
    public function getQuestion6()
    {
        return $this->question6;
    }

    /**
     * @param mixed $question6
     */
    public function setQuestion6($question6)
    {
        $this->question6 = $question6;
    }

    /**
     * @return mixed
     */
    public function getQuestion7()
    {
        return $this->question7;
    }

    /**
     * @param mixed $question7
     */
    public function setQuestion7($question7)
    {
        $this->question7 = $question7;
    }

    /**
     * @return mixed
     */
    public function getQuestion8()
    {
        return $this->question8;
    }

    /**
     * @param mixed $question8
     */
    public function setQuestion8($question8)
    {
        $this->question8 = $question8;
    }

    /**
     * @return mixed
     */
    public function getQuestion9()
    {
        return $this->question9;
    }

    /**
     * @param mixed $question9
     */
    public function setQuestion9($question9)
    {
        $this->question9 = $question9;
    }

    /**
     * @return mixed
     */
    public function getQuestion10()
    {
        return $this->question10;
    }

    /**
     * @param mixed $question10
     */
    public function setQuestion10($question10)
    {
        $this->question10 = $question10;
    }

    /**
     * @return int
     */
    public function getQuestionnairePassed()
    {
        return $this->questionnaire_passed;
    }

    /**
     * @param int $questionnaire_passed
     */
    public function setQuestionnairePassed($questionnaire_passed)
    {
        $this->questionnaire_passed = $questionnaire_passed;
    }

    /**
     * @return int
     */
    public function getQuestionnaireAttempts()
    {
        return $this->questionnaire_attempts;
    }

    /**
     * @param int $questionnaire_attempts
     */
    public function setQuestionnaireAttempts($questionnaire_attempts)
    {
        $this->questionnaire_attempts = $questionnaire_attempts;
    }


    /**
     * @return int
     */
    public function getSalesforceId()
    {
        return $this->salesforce_id;
    }

    /**
     * @param int $salesforce_id
     */
    public function setSalesforceId($salesforce_id)
    {
        $this->salesforce_id = $salesforce_id;
    }

    /**
     * @return string
     */
    public function getReferralLink()
    {
        return $this->referral_link;
    }

    /**
     * @param $referral_link
     */
    public function setReferralLink($referral_link)
    {
        $this->referral_link = $referral_link;
    }



    /**
     * @return array
     */
    public function getPreferenceData()
    {
        if ($this->contact_via_email == null) {
            $this->setContactViaEmail(0);
        }
        if ($this->contact_via_sms == null) {
            $this->setContactViaSms(0);
        }
        if ($this->contact_via_tele == null) {
            $this->setContactViaTele(0);
        }

        if ($this->fatca) {
            $this->setFatca(true);
        }

        $array_items = [

            'contact_via_email' => $this->contact_via_email,
            'contact_via_tele' => $this->contact_via_tele,
            'contact_via_sms' => $this->contact_via_sms,
            // 'cxb_restricted_investor' => $this->cxb_restricted_investor,
            // 'cxb_worth_investor' => $this->cxb_worth_investor,
            // 'cxb_sophisticated_investor' => $this->cxb_sophisticated_investor,
        ];

        if ($this->fatca) {
            $array_items['fatca'] = $this->fatca;
        }

        return $array_items;
    }

    /**
     * @return array
     */
    public function getQuestionnaireData()
    {
        $array_items = [

            // 'question1' => $this->question1,
            // 'question2' => $this->question2,
            // 'question3' => $this->question3,
            // 'question4' => $this->question4,
            // 'question5' => $this->question5,
            // 'question6' => $this->question6,
            // 'questionnaire_passed' => $this->questionnaire_passed,

        ];

        return $array_items;
    }

    /**
     * @return array
     */
    public function getQuestionnaireAttemptsData()
    {
        $array_items = [
            'questionnaire_attempts' => $this->questionnaire_attempts,

        ];

        return $array_items;
    }

    /**
     * @return array
     */
    public function getCrmData()
    {
        $array_items = [
            'salesforce_id' => $this->salesforce_id,
            'referral_link' => $this->referral_link,

        ];
        return $array_items;
    }
}
