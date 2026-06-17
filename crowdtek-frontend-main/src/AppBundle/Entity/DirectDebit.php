<?php

namespace AppBundle\Entity;

use Symfony\Component\Validator\Constraints\Range;

class DirectDebit
{
    /**
     * @var string
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $address1;

    /**
     * @var string
     */
    private $address2;

    /**
     * @var string
     */
    private $address3;

    /**
     * @var string
     */
    private $townCity;

    /**
     * @var string
     */
    private $postcode;

    /**
     * @var string
     */
    private $country;

    /**
     * @var bool
     */
    private $addressCheck;

    /**
     * @var string
     */
    private $bankAccountType;


    private $bankAccountId;


    /**
     * @var string
     */
    private $accountIban;

    /**
     * @var string
     */
    private $sortBic;

    /**
     * @var string
     */
    private $amount;

    /**
     * Get the value of bankAccountType
     *
     * @return  string
     */
    public function getBankAccountType()
    {
        return $this->bankAccountType;
    }

    /**
     * Set the value of bankAccountType
     *
     * @param  string  $bankAccountType
     *
     * @return  self
     */
    public function setBankAccountType(string $bankAccountType)
    {
        $this->bankAccountType = $bankAccountType;

        return $this;
    }

    /**
     * Get the value of accountIban
     *
     * @return  string
     */
    public function getAccountIban()
    {
        return $this->accountIban;
    }

    /**
     * Set the value of accountIban
     *
     * @param  string  $accountIban
     *
     * @return  self
     */
    public function setAccountIban(string $accountIban)
    {
        $this->accountIban = $accountIban;

        return $this;
    }

    /**
     * Get the value of sortBic
     *
     * @return  string
     */
    public function getSortBic()
    {
        return $this->sortBic;
    }

    /**
     * Set the value of sortBic
     *
     * @param  string  $sortBic
     *
     * @return  self
     */
    public function setSortBic(string $sortBic)
    {
        $this->sortBic = $sortBic;

        return $this;
    }

    /**
     * Get the value of amount
     *
     * @return  string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set the value of amount
     *
     * @param  string  $amount
     *
     * @return  self
     */
    public function setAmount(string $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get the value of firstName
     *
     * @return  string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstName
     *
     * @param  string  $firstName
     *
     * @return  self
     */
    public function setFirstName(string $firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of lastName
     *
     * @return  string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the value of lastName
     *
     * @param  string  $lastName
     *
     * @return  self
     */
    public function setLastName(string $lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the value of address1
     *
     * @return  string
     */
    public function getAddress1()
    {
        return $this->address1;
    }

    /**
     * Set the value of address1
     *
     * @param  string  $address1
     *
     * @return  self
     */
    public function setAddress1(string $address1)
    {
        $this->address1 = $address1;

        return $this;
    }

    /**
     * Get the value of address2
     *
     * @return  string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * Set the value of address2
     *
     * @param  string  $address2
     *
     * @return  self
     */
    public function setAddress2(string $address2)
    {
        $this->address2 = $address2;

        return $this;
    }

    /**
     * Get the value of address3
     *
     * @return  string
     */
    public function getAddress3()
    {
        return $this->address3;
    }

    /**
     * Set the value of address3
     *
     * @param  string  $address3
     *
     * @return  self
     */
    public function setAddress3(string $address3)
    {
        $this->address3 = $address3;

        return $this;
    }


    /**
     * Get the value of postcode
     *
     * @return  string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * Set the value of postcode
     *
     * @param  string  $postcode
     *
     * @return  self
     */
    public function setPostcode(string $postcode)
    {
        $this->postcode = $postcode;

        return $this;
    }

    /**
     * Get the value of country
     *
     * @return  string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @param  string  $country
     *
     * @return  self
     */
    public function setCountry(string $country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get the value of townCity
     *
     * @return  string
     */
    public function getTownCity()
    {
        return $this->townCity;
    }

    /**
     * Set the value of townCity
     *
     * @param  string  $townCity
     *
     * @return  self
     */
    public function setTownCity(string $townCity)
    {
        $this->townCity = $townCity;

        return $this;
    }

    /**
     * Get the value of addressCheck
     *
     * @return  bool
     */
    public function getAddressCheck()
    {
        return $this->addressCheck;
    }

    /**
     * Set the value of addressCheck
     *
     * @param  bool  $addressCheck
     *
     * @return  self
     */
    public function setAddressCheck(bool $addressCheck)
    {
        $this->addressCheck = $addressCheck;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBankAccountId()
    {
        return $this->bankAccountId;
    }

    /**
     * @param mixed $bankAccountId
     */
    public function setBankAccountId($bankAccountId): void
    {
        $this->bankAccountId = $bankAccountId;
    }
}
