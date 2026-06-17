<?php

namespace App\Dto;

use App\Dto\AddressDTO;
use App\Dto\Marketing;
use App\Validator as CommonAssert;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class UserPostDTO extends UserDTO
{
    #[JMS\Type('string')]
    #[CommonAssert\UniqueEmail]
    #[Assert\NotBlank]
    #[Assert\Email]
    protected ?string $email = null;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    protected ?string $firstName = null;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    protected ?string $lastName = null;

    public function __construct(
        string $email,
        ?string $password,
        ?string $title,
        string $firstName,
        string $lastName,
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

        parent::__construct(
            $this->email,
            $this->password,
            $this->title,
            $this->firstName,
            $this->lastName,
            $this->dateOfBirth,
            $this->nationality,
            $this->address,
            $this->countryOfResidence,
            $this->phone,
            $this->mobilePhone,
            $this->marketingPreference,
            $this->marketing,
            $this->typeOfInvestor,
            $this->gender,
            $this->referralCode,
        );
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
}
