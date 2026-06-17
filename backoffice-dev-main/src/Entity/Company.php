<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Company
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'companies')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class Company extends BaseEntity
{
    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $name;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $position;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $regAddress1;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $regAddress2;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $regAddress3;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $beneficialOwners;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $directors;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $regCountry;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $businessNature;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $telephone;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $postCode;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $buildingName;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $registrationNumber;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $otherName;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $companyWebsite;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $operatingAddress;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $operatingPostCode;

    /**
     * @var User
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\User', mappedBy: 'company')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Company
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set regAddress1
     *
     * @param string $regAddress1
     *
     * @return Company
     */
    public function setRegAddress1($regAddress1)
    {
        $this->regAddress1 = $regAddress1;

        return $this;
    }

    /**
     * Get regAddress1
     *
     * @return string
     */
    public function getRegAddress1()
    {
        return $this->regAddress1;
    }

    /**
     * Set regAddress2
     *
     * @param string $regAddress2
     *
     * @return Company
     */
    public function setRegAddress2($regAddress2)
    {
        $this->regAddress2 = $regAddress2;

        return $this;
    }

    /**
     * Get regAddress2
     *
     * @return string
     */
    public function getRegAddress2()
    {
        return $this->regAddress2;
    }

    /**
     * Set regAddress3
     *
     * @param string $regAddress3
     *
     * @return Company
     */
    public function setRegAddress3($regAddress3)
    {
        $this->regAddress3 = $regAddress3;

        return $this;
    }

    /**
     * Get regAddress3
     *
     * @return string
     */
    public function getRegAddress3()
    {
        return $this->regAddress3;
    }

    /**
     * Set beneficialOwners
     *
     * @param string $beneficialOwners
     *
     * @return Company
     */
    public function setBeneficialOwners($beneficialOwners)
    {
        $this->beneficialOwners = $beneficialOwners;

        return $this;
    }

    /**
     * Get beneficialOwners
     *
     * @return string
     */
    public function getBeneficialOwners()
    {
        return $this->beneficialOwners;
    }

    /**
     * Set directors
     *
     * @param string $directors
     *
     * @return Company
     */
    public function setDirectors($directors)
    {
        $this->directors = $directors;

        return $this;
    }

    /**
     * Get directors
     *
     * @return string
     */
    public function getDirectors()
    {
        return $this->directors;
    }

    /**
     * Set regCountry
     *
     * @param string $regCountry
     *
     * @return Company
     */
    public function setRegCountry($regCountry)
    {
        $this->regCountry = $regCountry;

        return $this;
    }

    /**
     * Get regCountry
     *
     * @return string
     */
    public function getRegCountry()
    {
        return $this->regCountry;
    }

    /**
     * Set businessNature
     *
     * @param string $businessNature
     *
     * @return Company
     */
    public function setBusinessNature($businessNature)
    {
        $this->businessNature = $businessNature;

        return $this;
    }

    /**
     * Get businessNature
     *
     * @return string
     */
    public function getBusinessNature()
    {
        return $this->businessNature;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return Company
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set postCode
     *
     * @param string $postCode
     *
     * @return Company
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
     * Set buildingName
     *
     * @param string $buildingName
     *
     * @return Company
     */
    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;

        return $this;
    }

    /**
     * Get buildingName
     *
     * @return string
     */
    public function getBuildingName()
    {
        return $this->buildingName;
    }

    /**
     * Set registrationNumber
     *
     * @param string $registrationNumber
     *
     * @return Company
     */
    public function setRegistrationNumber($registrationNumber)
    {
        $this->registrationNumber = $registrationNumber;

        return $this;
    }

    /**
     * Get registrationNumber
     *
     * @return string
     */
    public function getRegistrationNumber()
    {
        return $this->registrationNumber;
    }

    /**
     * Set otherName
     *
     * @param string $otherName
     *
     * @return Company
     */
    public function setOtherName($otherName)
    {
        $this->otherName = $otherName;

        return $this;
    }

    /**
     * Get otherName
     *
     * @return string
     */
    public function getOtherName()
    {
        return $this->otherName;
    }

    /**
     * Set companyWebsite
     *
     * @param string $companyWebsite
     *
     * @return Company
     */
    public function setCompanyWebsite($companyWebsite)
    {
        $this->companyWebsite = $companyWebsite;

        return $this;
    }

    /**
     * Get companyWebsite
     *
     * @return string
     */
    public function getCompanyWebsite()
    {
        return $this->companyWebsite;
    }

    /**
     * Set operatingAddress
     *
     * @param string $operatingAddress
     *
     * @return Company
     */
    public function setOperatingAddress($operatingAddress)
    {
        $this->operatingAddress = $operatingAddress;

        return $this;
    }

    /**
     * Get operatingAddress
     *
     * @return string
     */
    public function getOperatingAddress()
    {
        return $this->operatingAddress;
    }

    /**
     * Set operatingAddress
     *
     * @param string $operatingPostCode
     *
     * @return Company
     */
    public function setOperatingPostCode($operatingPostCode)
    {
        $this->operatingPostCode = $operatingPostCode;

        return $this;
    }

    /**
     * Get operatingPostCode
     *
     * @return string
     */
    public function getOperatingPostCode()
    {
        return $this->operatingPostCode;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return Company
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

    /**
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param string $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getRegisteredAddressAsString(): string
    {
        return (
            $this->regAddress1
            . ':'
            . $this->regAddress2
            . ':'
            . $this->regAddress3
            . ':'
            . $this->regCountry
            . ':'
            . $this->postCode
        );
    }

    public function formatAddressWithLinebreak(): string
    {
        $countryName = $this->regCountry;
        if (!empty($this->regCountry) && Countries::exists($this->regCountry)) {
            $countryName = Countries::getName($this->regCountry);
        }
        return "{$this->regAddress1}
                {$this->regAddress2}
                {$this->regAddress3}
                {$this->postCode}
                {$countryName}";
    }
}
