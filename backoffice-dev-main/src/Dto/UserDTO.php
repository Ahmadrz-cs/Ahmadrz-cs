<?php

namespace App\Dto;

use App\Dto\AddressDTO;
use App\Dto\Marketing;
use App\Validator as CommonAssert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

abstract class UserDTO
{
    #[JMS\Type('string')]
    #[Assert\Email]
    protected ?string $email = null;

    #[JMS\Type('string')]
    #[CommonAssert\PasswordRules]
    protected ?string $password = null;

    #[JMS\Type('string')]
    protected ?string $title = null;

    #[JMS\Type('string')]
    protected ?string $firstName = null;

    #[JMS\Type('string')]
    protected ?string $lastName = null;

    #[JMS\Type("DateTime<'d-m-Y'>")]
    protected ?\DateTime $dateOfBirth = null;

    #[JMS\Type('string')]
    #[Assert\Country]
    protected ?string $nationality = null;

    #[JMS\Type("App\Dto\AddressDTO")]
    protected ?AddressDTO $address = null;

    #[JMS\Type('string')]
    #[Assert\Country]
    protected ?string $countryOfResidence = null;

    #[JMS\Type('string')]
    protected ?string $phone = null;

    #[JMS\Type('string')]
    protected ?string $mobilePhone = null;

    #[JMS\Type('string')]
    #[CommonAssert\MarketingPreference]
    protected ?string $marketingPreference = null;

    #[JMS\Type("App\Dto\Marketing")]
    protected ?Marketing $marketing = null;

    #[JMS\Type('string')]
    #[CommonAssert\InvestorType]
    protected ?string $typeOfInvestor = null;

    #[JMS\Type('string')]
    protected ?string $gender = null;

    #[JMS\Type('string')]
    #[Assert\Regex('/^\w+/')]
    protected ?string $referralCode = null;

    public function __construct(
        ?string $email,
        ?string $password,
        ?string $title,
        ?string $firstName,
        ?string $lastName,
        ?\DateTime $dateOfBirth,
        ?string $nationality,
        ?AddressDTO $address,
        ?string $countryOfResidence,
        ?string $phone,
        ?string $mobilePhone,
        ?string $marketingPreference,
        ?Marketing $marketing = null,
        ?string $typeOfInvestor = null,
        ?string $gender = null,
        ?string $referralCode = null,
    ) {
        $this->email = $email;
        $this->password = $password;
        $this->title = $title;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->dateOfBirth = $dateOfBirth;
        $this->nationality = $nationality;
        $this->address = $address;
        $this->countryOfResidence = $countryOfResidence;
        $this->phone = $phone;
        $this->mobilePhone = $mobilePhone;
        $this->marketingPreference = $marketingPreference;
        $this->marketing = $marketing;
        $this->typeOfInvestor = $typeOfInvestor;
        $this->gender = $gender;
        $this->referralCode = $referralCode;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getDateOfBirth(): ?\DateTime
    {
        return $this->dateOfBirth;
    }

    public function getNationality(): ?string
    {
        return $this->nationality;
    }

    public function getAddress(): ?AddressDTO
    {
        return $this->address;
    }

    public function getCountryOfResidence(): ?string
    {
        return $this->countryOfResidence;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function getMarketingPreference(): ?string
    {
        return $this->marketingPreference;
    }

    public function getMarketing(): ?Marketing
    {
        return $this->marketing;
    }

    public function getTypeOfInvestor(): ?string
    {
        return $this->typeOfInvestor;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getReferralCode(): ?string
    {
        return $this->referralCode;
    }
}
