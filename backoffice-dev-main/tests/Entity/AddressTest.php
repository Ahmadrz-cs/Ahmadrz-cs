<?php

namespace App\Tests\Entity;

use App\Entity\Address;
use App\Entity\User;

class AddressTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $address = new Address();

        $this->assertNotNull($address);

        $this->assertNull($address->getId());
    }

    public function testAddress1(): void
    {
        $address = new Address();

        $testAddress = 'Test';

        $this->assertNull($address->getAddress1());

        $address->setAddress1($testAddress);

        $this->assertEquals($testAddress, $address->getAddress1());
    }

    public function testAddress2(): void
    {
        $address = new Address();

        $testAddress = 'Test';

        $this->assertNull($address->getAddress2());

        $address->setAddress2($testAddress);

        $this->assertEquals($testAddress, $address->getAddress2());
    }

    public function testAddress3(): void
    {
        $address = new Address();

        $testAddress = 'Test';

        $this->assertNull($address->getAddress3());

        $address->setAddress3($testAddress);

        $this->assertEquals($testAddress, $address->getAddress3());
    }

    public function testCity(): void
    {
        $address = new Address();

        $testCity = 'Test';

        $this->assertNull($address->getCity());

        $address->setCity($testCity);

        $this->assertEquals($testCity, $address->getCity());
    }

    public function testRegion(): void
    {
        $address = new Address();

        $testRegion = 'Test';

        $this->assertNull($address->getRegion());

        $address->setRegion($testRegion);

        $this->assertEquals($testRegion, $address->getRegion());
    }

    public function testPostCode(): void
    {
        $address = new Address();

        $testPostCode = 'Test';

        $this->assertNull($address->getPostCode());

        $address->setPostCode($testPostCode);

        $this->assertEquals($testPostCode, $address->getPostCode());
    }

    public function testCountry(): void
    {
        $address = new Address();

        $testCountry = 'Test';

        $this->assertNull($address->getCountry());

        $address->setCountry($testCountry);

        $this->assertEquals($testCountry, $address->getCountry());
    }

    public function testUser(): void
    {
        $address = new Address();

        $testUser = new User();

        $this->assertNull($address->getUser());

        $address->setUser($testUser);

        $this->assertEquals($testUser, $address->getUser());
    }

    public function testCreatedAt(): void
    {
        $address = new Address();

        $testDatetime = new \DateTime();

        $this->assertNull($address->getCreatedAt());

        $address->setCreatedAt($testDatetime);

        $this->assertEquals($testDatetime, $address->getCreatedAt());

        $this->assertInstanceOf('DateTime', $address->getCreatedAt());
    }

    public function testCreatedBy(): void
    {
        $address = new Address();

        $testUser = new User();

        $this->assertNull($address->getCreatedBy());

        $address->setCreatedBy($testUser);

        $this->assertEquals($testUser, $address->getCreatedBy());
    }
}
