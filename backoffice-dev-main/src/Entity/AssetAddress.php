<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Service\Util\Helper;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'asset_addresses')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class AssetAddress extends BaseEntity implements \JsonSerializable
{
    /**
     * @var string $address1
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $address1;

    /**
     * @var string $address2
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $address2;

    /**
     * @var string $address3
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $address3;

    /**
     * @var string $city
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $city;

    /**
     * @var string $region
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $region;

    /**
     * @var string $postCode
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $postCode;

    /**
     * @var string $country
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $country;

    /**
     * @var string $latitude
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $latitude;

    /**
     * @var string $latitude
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $longitude;

    /**
     * @var $asset
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'addresses')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $asset;

    public function jsonSerialize(): mixed
    {
        return [
            'building' => $this->address1,
            'street_address' => $this->address2,
            'address_locality' => $this->address3,
            'city' => $this->city,
            'region' => $this->region,
            'postal_code' => $this->postCode,
            'country' => Helper::getCountryCode($this->country),
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
        ];
    }

    /**
     * Set address1
     *
     * @param string $address1
     *
     * @return AssetAddress
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
     * @return AssetAddress
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
     * @return AssetAddress
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
     * @return AssetAddress
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
     * @return AssetAddress
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
     * @return AssetAddress
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
     * @return AssetAddress
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
     * Set longitude
     *
     * @param string $longitude
     *
     * @return AssetAddress
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * Get longitude
     *
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Set latitude
     *
     * @param string $latitude
     *
     * @return AssetAddress
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * Get latitude
     *
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set asset
     *
     * @param \App\Entity\Asset $asset
     *
     * @return AssetAddress
     */
    public function setAsset(?\App\Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \App\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getAddress1()
        . ', '
        . $this->getAddress2()
        . ', '
        . "\r\n"
        . $this->getAddress3()
        . ', '
        . $this->getCity()
        . ', '
        . $this->getRegion()
        . ", \r\n"
        . 'Post Code -- '
        . $this->getPostCode()
        . ', '
        . $this->getCountry();
    }

    public function formatWithLinebreak(): string
    {
        return "{$this->getAddress1()}
                {$this->getAddress2()}
                {$this->getAddress3()}
                {$this->getCity()}
                {$this->getPostCode()}
                {$this->getCountry()}";
    }

    public function formatCoordinates(): string
    {
        return "{$this->getLatitude()}, {$this->getLongitude()}";
    }
}
