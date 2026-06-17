<?php

/**
 * Created by PhpStorm.
 * User: ASKCO\alibhatti
 * Date: 27/07/18
 * Time: 15:52
 */

namespace AppBundle\Entity;

class UserCompanyEntity
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $position;

    /**
     * @var string
     */
    private $regAddress1;

    /**
     * @var string
     */
    private $regAddress2;

    /**
     * @var string
     */
    private $regAddress3;

    /**
     * @var string
     */
    private $beneficialOwners;

    /**
     * @var string
     */
    private $directors;

    /**
     * @var string
     */
    private $regCountry;

    /**
     * @var string
     */
    private $businessNature;

    /**
     * @var string
     */
    private $telephone;

    /**
     * @var string
     */
    private $postCode;

    /**
     * @var string
     */
    private $buildingName;

    /**
     * @var string
     */
    private $registrationNumber;

    /**
     * @var string
     */
    private $otherName;

    /**
     * @var string
     */
    private $companyWebsite;

    /**
     * @var string
     */
    private $operatingAddress;

    /**
     * @var string
     */
    private $operatingPostCode;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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

    /**
     * @return string
     */
    public function getRegAddress1()
    {
        return $this->regAddress1;
    }

    /**
     * @param string $regAddress1
     */
    public function setRegAddress1($regAddress1)
    {
        $this->regAddress1 = $regAddress1;
    }

    /**
     * @return string
     */
    public function getRegAddress2()
    {
        return $this->regAddress2;
    }

    /**
     * @param string $regAddress2
     */
    public function setRegAddress2($regAddress2)
    {
        $this->regAddress2 = $regAddress2;
    }

    /**
     * @return string
     */
    public function getRegAddress3()
    {
        return $this->regAddress3;
    }

    /**
     * @param string $regAddress3
     */
    public function setRegAddress3($regAddress3)
    {
        $this->regAddress3 = $regAddress3;
    }

    /**
     * @return string
     */
    public function getBeneficialOwners()
    {
        return $this->beneficialOwners;
    }

    /**
     * @param string $beneficialOwners
     */
    public function setBeneficialOwners($beneficialOwners)
    {
        $this->beneficialOwners = $beneficialOwners;
    }

    /**
     * @return string
     */
    public function getDirectors()
    {
        return $this->directors;
    }

    /**
     * @param string $directors
     */
    public function setDirectors($directors)
    {
        $this->directors = $directors;
    }

    /**
     * @return string
     */
    public function getRegCountry()
    {
        return $this->regCountry;
    }

    /**
     * @param string $regCountry
     */
    public function setRegCountry($regCountry)
    {
        $this->regCountry = $regCountry;
    }

    /**
     * @return string
     */
    public function getBusinessNature()
    {
        return $this->businessNature;
    }

    /**
     * @param string $businessNature
     */
    public function setBusinessNature($businessNature)
    {
        $this->businessNature = $businessNature;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getPostCode()
    {
        return $this->postCode;
    }

    /**
     * @param string $postCode
     */
    public function setPostCode($postCode)
    {
        $this->postCode = $postCode;
    }

    /**
     * @return string
     */
    public function getBuildingName()
    {
        return $this->buildingName;
    }

    /**
     * @param string $buildingName
     */
    public function setBuildingName($buildingName)
    {
        $this->buildingName = $buildingName;
    }

    /**
     * @return string
     */
    public function getRegistrationNumber()
    {
        return $this->registrationNumber;
    }

    /**
     * @param string $registrationNumber
     */
    public function setRegistrationNumber($registrationNumber)
    {
        $this->registrationNumber = $registrationNumber;
    }

    /**
     * @return string
     */
    public function getOtherName()
    {
        return $this->otherName;
    }

    /**
     * @param string $otherName
     */
    public function setOtherName($otherName)
    {
        $this->otherName = $otherName;
    }

    /**
     * @return string
     */
    public function getCompanyWebsite()
    {
        return $this->companyWebsite;
    }

    /**
     * @param string $companyWebsite
     */
    public function setCompanyWebsite($companyWebsite)
    {
        $this->companyWebsite = $companyWebsite;
    }

    /**
     * @return string
     */
    public function getOperatingAddress()
    {
        return $this->operatingAddress;
    }

    /**
     * @param string $operatingAddress
     */
    public function setOperatingAddress($operatingAddress)
    {
        $this->operatingAddress = $operatingAddress;
    }

    /**
     * @return string
     */
    public function getOperatingPostCode()
    {
        return $this->operatingPostCode;
    }

    /**
     * @param string $operatingPostCode
     */
    public function setOperatingPostCode($operatingPostCode)
    {
        $this->operatingPostCode = $operatingPostCode;
    }
}
