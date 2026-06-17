<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 27/07/18
 * Time: 15:01
 */

namespace AppBundle\Entity;

use AppBundle\Validator\Constraints\UniqueEmail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class UserEntity
{
    private $email;

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('email', new UniqueEmail([
            'groups' => ['registration'],
        ]));
    }

    /**
     * @var string
     */
    private $firstname;

    /**
     * @var string
     */
    private $middlename;

    /**
     * @var string
     */
    private $lastname;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $gender;

    /**
     * @var string
     */
    private $honorificPrefix;

    /**
     * @var string
     */
    private $honorificSuffix;

    /**
     * @var string
     */
    private $nationality;

    /**
     * @var string
     */
    private $mobile;

    /**
     * @var string
     */
    private $phone1;

    /**
     * @var string
     */
    private $phone2;

    /**
     * @var string
     */
    private $birthCountry;

    /**
     * @var string
     */
    private $birthDate;

    /**
     * @var string
     */
    private $birthPlace;

    /**
     * @var string
     */
    private $term_service_accepted;

    /**
     * @var int
     */
    private $ob_step;

    /**
     * @var string
     */
    private $url;

    /**
     * @var $additionalType
     */
    private $additionalType;

    /**
     * @var string $additionalName
     */
    private $additionalName;

    /**
     * @var string $affiliateCode
     */
    private $affiliateCode;

    /**
     * @var string $biography
     */
    private $biography;


    /**
     * @var int $externalReferenceId
     */
    private $externalReferenceId;


    /**
     * @var string $referralCode
     */
    private $referralCode;


    /**
     * @var string $sector
     */
    private $sector;

    /**
     * @var string $tagline
     */
    private $tagline;

    /**
     * @var int
     */
    private $gdpr_accepted = 0;

    /**
     * @var string $website
     */
    private $website;

    /**
     * @var int $visibility
     */
    private $visibility = 0;

    /**
     * @var int $taxId
     */
    private $taxId;

    /**
     * @var string $jobTitle
     */
    private $jobTitle;

    /**
     * @var string $location
     */
    private $location;

    /**
     * @var string $incomeRange
     */
    private $incomeRange;

    /**
     * @var string $passportNumber
     */
    private $passportNumber;

    /**
     * @var string $passportCountry
     */
    private $passportCountry;

    /**
     * @var Date $passportExpiry
     */
    private $passportExpiry;

    private $info;

    private $address;

    /** @var  UploadedFile */
    private $document;

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getMiddlename()
    {
        return $this->middlename;
    }

    /**
     * @param string $middlename
     */
    public function setMiddlename($middlename)
    {
        $this->middlename = $middlename;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string
     */
    public function getHonorificPrefix()
    {
        return $this->honorificPrefix;
    }

    /**
     * @param string $HonorificPrefix
     */
    public function setHonorificPrefix($honorificPrefix)
    {
        $this->honorificPrefix = $honorificPrefix;
    }

    /**
     * @return string
     */
    public function getHonorificSuffix()
    {
        return $this->honorificSuffix;
    }

    /**
     * @param string $honorificSuffix
     */
    public function setHonorificSuffix($honorificSuffix)
    {
        $this->honorificSuffix = $honorificSuffix;
    }

    /**
     * @return string
     */
    public function getNationality()
    {
        return $this->nationality;
    }

    /**
     * @param string $nationality
     */
    public function setNationality($nationality)
    {
        $this->nationality = $nationality;
    }

    /**
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;
    }

    /**
     * @return string
     */
    public function getPhone1()
    {
        return $this->phone1;
    }

    /**
     * @param string $phone1
     */
    public function setPhone1($phone1)
    {
        $this->phone1 = $phone1;
    }

    /**
     * @return string
     */
    public function getPhone2()
    {
        return $this->phone2;
    }

    /**
     * @param string $phone2
     */
    public function setPhone2($phone2)
    {
        $this->phone2 = $phone2;
    }

    /**
     * @return string
     */
    public function getBirthCountry()
    {
        return $this->birthCountry;
    }

    /**
     * @param string $birthCountry
     */
    public function setBirthCountry($birthCountry)
    {
        $this->birthCountry = $birthCountry;
    }

    /**
     * @return string
     */
    public function getBirthDate()
    {
        return $this->birthDate;
    }

    /**
     * @param string $birthDate
     */
    public function setBirthDate($birthDate)
    {
        $this->birthDate = $birthDate;
    }

    /**
     * @return string
     */
    public function getBirthPlace()
    {
        return $this->birthPlace;
    }

    /**
     * @param string $birthPlace
     */
    public function setBirthPlace($birthPlace)
    {
        $this->birthPlace = $birthPlace;
    }

    /**
     * @return string
     */
    public function getTermServiceAccepted()
    {
        return $this->term_service_accepted;
    }

    /**
     * @param string $term_service_accepted
     */
    public function setTermServiceAccepted($term_service_accepted)
    {
        $this->term_service_accepted = $term_service_accepted;
    }

    /**
     * @return int
     */
    public function getObStep()
    {
        return $this->ob_step;
    }

    /**
     * @param int $ob_step
     */
    public function setObStep($ob_step)
    {
        $this->ob_step = $ob_step;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getAdditionalType()
    {
        return $this->additionalType;
    }

    /**
     * @param mixed $additionalType
     */
    public function setAdditionalType($additionalType)
    {
        $this->additionalType = $additionalType;
    }

    /**
     * @return string
     */
    public function getAdditionalName()
    {
        return $this->additionalName;
    }

    /**
     * @param string $additionalName
     */
    public function setAdditionalName($additionalName)
    {
        $this->additionalName = $additionalName;
    }

    /**
     * @return string
     */
    public function getAffiliateCode()
    {
        return $this->affiliateCode;
    }

    /**
     * @param string $affiliateCode
     */
    public function setAffiliateCode($affiliateCode)
    {
        $this->affiliateCode = $affiliateCode;
    }

    /**
     * @return string
     */
    public function getBiography()
    {
        return $this->biography;
    }

    /**
     * @param string $biography
     */
    public function setBiography($biography)
    {
        $this->biography = $biography;
    }

    /**
     * @return int
     */
    public function getExternalReferenceId()
    {
        return $this->externalReferenceId;
    }

    /**
     * @param int $externalReferenceId
     */
    public function setExternalReferenceId($externalReferenceId)
    {
        $this->externalReferenceId = $externalReferenceId;
    }

    /**
     * @return string
     */
    public function getReferralCode()
    {
        return $this->referralCode;
    }

    /**
     * @param string $referralCode
     */
    public function setReferralCode($referralCode)
    {
        $this->referralCode = $referralCode;
    }

    /**
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * @param string $sector
     */
    public function setSector($sector)
    {
        $this->sector = $sector;
    }

    /**
     * @return string
     */
    public function getTagline()
    {
        return $this->tagline;
    }

    /**
     * @param string $tagline
     */
    public function setTagline($tagline)
    {
        $this->tagline = $tagline;
    }

    /**
     * @return int
     */
    public function getGdprAccepted()
    {
        return $this->gdpr_accepted;
    }

    /**
     * @param int $gdpr_accepted
     */
    public function setGdprAccepted($gdpr_accepted)
    {
        $this->gdpr_accepted = $gdpr_accepted;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    /**
     * @return int
     */
    public function getTaxId()
    {
        return $this->taxId;
    }

    /**
     * @param int $taxId
     */
    public function setTaxId($taxId)
    {
        $this->taxId = $taxId;
    }

    /**
     * @return string
     */
    public function getJobTitle()
    {
        return $this->jobTitle;
    }

    /**
     * @param string $jobTitle
     */
    public function setJobTitle($jobTitle)
    {
        $this->jobTitle = $jobTitle;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getIncomeRange()
    {
        return $this->incomeRange;
    }

    /**
     * @param string $incomeRange
     */
    public function setIncomeRange($incomeRange)
    {
        $this->incomeRange = $incomeRange;
    }

    /**
     * @return string
     */
    public function getPassportNumber()
    {
        return $this->passportNumber;
    }

    /**
     * @param string $passportNumber
     */
    public function setPassportNumber($passportNumber)
    {
        $this->passportNumber = $passportNumber;
    }

    /**
     * @return string
     */
    public function getPassportCountry()
    {
        return $this->passportCountry;
    }

    /**
     * @param string $passportCountry
     */
    public function setPassportCountry($passportCountry)
    {
        $this->passportCountry = $passportCountry;
    }

    /**
     * @return Date
     */
    public function getPassportExpiry()
    {
        return $this->passportExpiry;
    }

    /**
     * @param Date $passportExpiry
     */
    public function setPassportExpiry($passportExpiry)
    {
        $this->passportExpiry = $passportExpiry;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param mixed $info
     */
    public function setInfo($info)
    {
        $this->info = $info;
    }

    /**
     * @return mixed
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return UploadedFile
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param UploadedFile $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    public function getPhotoDataBase64Encoded()
    {
        return base64_encode(file_get_contents($this->document->getPathname()));
    }


    /**
     * @return array
     */
    public function getData()
    {
        $array_items = [
            'additional_name' => $this->additionalName,
            'additional_type' => $this->additionalType,
            'affiliate_code' => $this->affiliateCode,
            'biography' => $this->biography,
            'birth_country' => $this->birthCountry,
            'birth_date' => $this->birthDate,
            'birth_place' => $this->birthPlace,
            'email' => $this->email,
            'external_reference_id' => $this->externalReferenceId,
            'family_name' => $this->lastname,
            'gender' => $this->gender,
            'given_name' => $this->firstname,
            'honorific_prefix' => $this->honorificPrefix,
            'honorific_suffix' => $this->honorificSuffix,
            'income_range' => $this->incomeRange,
            'job_title' => $this->jobTitle,
            'location' => $this->location,
            'nationality' => $this->nationality,
            // 'passport_country' => $this->passportCountry,
            // 'passport_expiry' => $this->passportExpiry,
            // 'passport_number' => $this->passportNumber,
            'phone_1' => $this->phone1,
            'phone_2' => $this->phone2,
            'mobile' => $this->mobile,
            'referral_code' => $this->referralCode,
            'sector' => $this->sector,
            'tagline' => $this->tagline,
            'tax_id' => $this->taxId,
            'visibility' => $this->visibility,
            'web_site' => $this->website,
            'term_service_accepted' => $this->term_service_accepted,
            'gdpr_accepted' => $this->gdpr_accepted,
            'ob_step' => $this->ob_step,
            'url' => $this->url,
            'password' => $this->password,
        ];

        return $array_items;
    }

    public function getCustomFields()
    {
        return $result = [

            'corporate_investor' => $this->info['corporate_investor'],
            'company_name' => $this->info['company_name'],
            'company_registration_country' => $this->info['company_registration_country'],
            'company_registered_number' => $this->info['company_registered_number'],
            'company_nature_of_business' => $this->info['company_nature_of_business'],
            'company_telephone' => $this->info['company_telephone'],
            'company_website' => $this->info['company_website'],
            'company_registered_address_1' => $this->info['company_registered_address_1'],
            'company_registered_address_2' => $this->info['company_registered_address_2'],
            'company_registered_address_3' => $this->info['company_registered_address_3'],
            'company_postcode' => $this->info['company_postcode'],
            'operating_address' => $this->info['operating_address'],
            'operating_postcode' => $this->info['operating_postcode'],
            'company_beneficial_owners' => json_encode($this->info['company_beneficial_owners']),
            'company_directors' => json_encode($this->info['company_directors']),
            'referral' => $this->info['referral'],
        ];
    }

    /**
     * @return array
     */
    public function getComplianceData()
    {
        $array_items = [
            'birth_date' => $this->birthDate,
            'family_name' => $this->lastname,
            'given_name' => $this->firstname,
            'gender' => $this->gender,
            'honorific_prefix' => $this->honorificPrefix,
            'honorific_suffix' => $this->honorificSuffix,
            'nationality' => $this->nationality,
            'phone_1' => $this->phone1,
            'phone_2' => $this->phone2,
            'mobile' => $this->mobile,
            'ob_step' => $this->ob_step,
            'info' => $this->getCustomFields(),
            'address' => $this->address,
        ];

        return $array_items;
    }
}
