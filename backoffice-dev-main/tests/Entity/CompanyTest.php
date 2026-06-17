<?php

namespace App\Tests\Entity;

use App\Entity\Company;
use App\Entity\User;

class CompanyTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $company = new Company();

        $this->assertNotNull($company);

        $this->assertNull($company->getId());
    }

    /*
     * testSetName
     */
    public function testSetName(): void
    {
        $company = new Company();
        $test_value = 'Sayak';
        $this->assertNotNull($test_value);

        $company->setName($test_value);
        $this->assertEquals($test_value, $company->getName());
    }

    /*
     * testSetRegAddress1
     */
    public function testSetRegAddress1(): void
    {
        $company = new Company();
        $test_value = 'Address1';
        $this->assertNotNull($test_value);

        $company->setRegAddress1($test_value);
        $this->assertEquals($test_value, $company->getRegAddress1());
    }

    /*
     * testSetRegAddress2
     */
    public function testSetRegAddress2(): void
    {
        $company = new Company();
        $test_value = 'Address2';
        $this->assertNotNull($test_value);

        $company->setRegAddress2($test_value);
        $this->assertEquals($test_value, $company->getRegAddress2());
    }

    /*
     * testSetRegAddress3
     */
    public function testSetRegAddress3(): void
    {
        $company = new Company();
        $test_value = 'Address3';
        $this->assertNotNull($test_value);

        $company->setRegAddress3($test_value);
        $this->assertEquals($test_value, $company->getRegAddress3());
    }

    /*
     * testSetBeneficialOwners
     */
    public function testSetBeneficialOwners(): void
    {
        $company = new Company();
        $test_value = 'Owner 1';
        $this->assertNotNull($test_value);

        $company->setBeneficialOwners($test_value);
        $this->assertEquals($test_value, $company->getBeneficialOwners());
    }

    /*
     * testSetDirectors
     */
    public function testSetDirectors(): void
    {
        $company = new Company();
        $test_value = 'Director 1';
        $this->assertNotNull($test_value);

        $company->setDirectors($test_value);
        $this->assertEquals($test_value, $company->getDirectors());
    }

    /*
     * testSetRegCountry
     */
    public function testSetRegCountry(): void
    {
        $company = new Company();
        $test_value = 'London';
        $this->assertNotNull($test_value);

        $company->setRegCountry($test_value);
        $this->assertEquals($test_value, $company->getRegCountry());
    }

    /*
     * testSetBusinessNature
     */
    public function testSetBusinessNature(): void
    {
        $company = new Company();
        $test_value = 'Private';
        $this->assertNotNull($test_value);

        $company->setBusinessNature($test_value);
        $this->assertEquals($test_value, $company->getBusinessNature());
    }

    /*
     * testSetTelephone
     */
    public function testSetTelephone(): void
    {
        $company = new Company();
        $test_value = '+636541654131';
        $this->assertNotNull($test_value);

        $company->setTelephone($test_value);
        $this->assertEquals($test_value, $company->getTelephone());
    }

    /*
     * testSetPostCode
     */
    public function testSetPostCode(): void
    {
        $company = new Company();
        $test_value = '6GT 4TE';
        $this->assertNotNull($test_value);

        $company->setPostCode($test_value);
        $this->assertEquals($test_value, $company->getPostCode());
    }

    /*
     * testSetBuildingName
     */
    public function testSetBuildingName(): void
    {
        $company = new Company();
        $test_value = 'Gateway';
        $this->assertNotNull($test_value);

        $company->setBuildingName($test_value);
        $this->assertEquals($test_value, $company->getBuildingName());
    }

    /*
     * testSetRegistrationNumber
     */
    public function testSetRegistrationNumber(): void
    {
        $company = new Company();
        $test_value = 'REG 578976897';
        $this->assertNotNull($test_value);

        $company->setRegistrationNumber($test_value);
        $this->assertEquals($test_value, $company->getRegistrationNumber());
    }

    /*
     * testSetOtherName
     */
    public function testSetOtherName(): void
    {
        $company = new Company();
        $test_value = 'Mukherjee';
        $this->assertNotNull($test_value);

        $company->setOtherName($test_value);
        $this->assertEquals($test_value, $company->getOtherName());
    }

    /*
     * testSetCreatedAt
     */
    public function testSetCreatedAt(): void
    {
        $company = new Company();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $company->setCreatedAt($test_value);
        $this->assertEquals($test_value, $company->getCreatedAt());
        $this->assertInstanceOf('DateTime', $company->getCreatedAt());
    }

    public function testSetUser(): void
    {
        $company = new Company();
        $testUser = new User();

        $this->assertNull($company->getUser());
        $company->setUser($testUser);

        $this->assertEquals($testUser, $company->getUser());
    }

    public function testSetCreatedBy(): void
    {
        $company = new Company();
        $user = new User();

        $this->assertNull($company->getCreatedBy());
        $company->setCreatedBy($user);

        $this->assertEquals($user, $company->getCreatedBy());
    }

    /*
     * testCompanyWebsite
     */
    public function testCompanyWebsite(): void
    {
        $company = new Company();
        $test_value = 'www.qtsin.com';
        $this->assertNotNull($test_value);

        $company->setCompanyWebsite($test_value);
        $this->assertEquals($test_value, $company->getCompanyWebsite());
    }

    /*
     * testOperatingAddress
     */
    public function testOperatingAddress(): void
    {
        $company = new Company();
        $test_value = 'Lane No 1, RD Avenue';
        $this->assertNotNull($test_value);

        $company->setOperatingAddress($test_value);
        $this->assertEquals($test_value, $company->getOperatingAddress());
    }

    /*
     * testOperatingPostCode
     */
    public function testOperatingPostCode(): void
    {
        $company = new Company();
        $test_value = 'Lane No 1, RD Avenue';
        $this->assertNotNull($test_value);

        $company->setOperatingPostCode($test_value);
        $this->assertEquals($test_value, $company->getOperatingPostCode());
    }

    public function testGetRegisteredAddressAsString(): void
    {
        $company = new Company();

        $this->assertEquals('::::', $company->getRegisteredAddressAsString());
        $company->setRegAddress1('Clover Ltd');
        $company->setRegAddress2('Unit 5');
        $company->setRegAddress3('Anyone Field');
        $company->setRegCountry('United Kingdom');
        $company->setPostCode('AB12 9YZ');
        $this->assertEquals(
            'Clover Ltd:Unit 5:Anyone Field:United Kingdom:AB12 9YZ',
            $company->getRegisteredAddressAsString(),
        );
    }
}
