<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Repository\AddressRepository;
use App\Service\Util\Helper;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Address
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'addresses')]
#[ORM\Entity(repositoryClass: AddressRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class Address extends BaseEntity implements \JsonSerializable
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    //    use TimestampableEntity;
    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\NotBlank]
    private $address1;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $address2;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $address3;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\NotBlank]
    private $city;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $region;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\NotBlank]
    private $postCode;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['user'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\NotBlank]
    private $country;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'addresses')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * Set address1
     *
     * @param string $address1
     *
     * @return Address
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get address1
     *
     * @return string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set address2
     *
     * @param string $address2
     *
     * @return Address
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get address2
     *
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set address3
     *
     * @param string $address3
     *
     * @return Address
     */
    public function setAddress3($address3)
    {
        $this->address3 = $address3;

        return $this;
    }

    /**
     * Get address3
     *
     * @return string
     */
    public function getAddress3()
    {
        return $this->address3;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Address
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set region
     *
     * @param string $region
     *
     * @return Address
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set postCode
     *
     * @param string $postCode
     *
     * @return Address
     */
    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;

        return $this;
    }

    /**
     * Get postCode
     *
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Address
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Address
     */
    public function setUser(?\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'building' => $this->address1,
            'street_address' => $this->address2,
            'address_locality' => $this->address3,
            'city' => $this->city,
            'region' => $this->region,
            'country' => Helper::getCountryCode($this->country),
            'postal_code' => $this->postCode,
        ];
    }

    public function asString()
    {
        return (
            $this->address1
            . ':'
            . $this->address2
            . ':'
            . $this->address3
            . ':'
            . $this->city
            . ':'
            . $this->region
            . ':'
            . $this->country
            . ':'
            . $this->postCode
        );
    }

    public function formatWithLinebreak(): string
    {
        $countryName = $this->getCountry();
        if (!empty($this->getCountry()) && Countries::exists($this->getCountry())) {
            $countryName = Countries::getName($this->getCountry());
        }
        return "{$this->getAddress1()}
                {$this->getAddress2()}
                {$this->getAddress3()}
                {$this->getCity()}
                {$this->getRegion()}
                {$this->getPostCode()}
                {$countryName}";
    }

    public function __tostring(): string
    {
        return json_encode($this);
    }
}
