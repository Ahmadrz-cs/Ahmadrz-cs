<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;

#[JMS\ExclusionPolicy('all')]
final class AddressDTO
{
    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $address1;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $address2;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $address3;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $region;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $city;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $postCode;

    #[JMS\Expose]
    #[JMS\Groups(['standard'])]
    #[JMS\Type('string')]
    private $country;

    public function __construct(
        string $address1,
        ?string $address2 = null,
        ?string $address3 = null,
        ?string $region = null,
        string $city,
        string $postCode,
        string $country,
    ) {
        $this->address1 = $address1;
        $this->address2 = $address2;
        $this->address3 = $address3;
        $this->region = $region;
        $this->city = $city;
        $this->postCode = $postCode;
        $this->country = $country;
    }

    public function getAddress1()
    {
        return $this->address1;
    }

    public function getAddress2()
    {
        return $this->address2;
    }

    public function getAddress3()
    {
        return $this->address3;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getPostCode()
    {
        return $this->postCode;
    }

    public function getCountry()
    {
        return $this->country;
    }
}
