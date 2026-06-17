<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Repository\AddressRepository;
use App\Test\FixtureTestCase;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;

class AddressRepositoryTest extends FixtureTestCase
{
    private AddressRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(Address::class);
    }

    public function testCanCreateIfValid(): void
    {
        $current_count = count($this->repository->findAll());
        $this->assertNotNull($this->repository->find(1));
        $testAddress = $this->getValidAddress();
        $address1 = $testAddress->getAddress1();
        $this->repository->save($testAddress, true);

        $this->assertNotNull($testAddress->getId());
        $this->assertNotNull($testAddress->getCreatedAt());
        $this->assertNotNull($testAddress->getUpdatedAt());
        $this->assertEquals($address1, $testAddress->getAddress1());
        $this->assertEquals($current_count + 1, count($this->repository->findAll()));
    }

    public function testCanNotCreateIfInvalid(): void
    {
        $this->assertNotNull($this->repository->find(1));
        $testAddress = $this->getInvalidAddress();
        $this->expectException(NotNullConstraintViolationException::class);
        $this->repository->save($testAddress, true);
    }

    public function testCanEdit(): void
    {
        $testAddress = $this->getValidAddress();
        $newAddress1 = md5(uniqid());
        $this->repository->save($testAddress, true);
        $testAddress->setAddress1($newAddress1);
        $this->repository->save($testAddress, true);
        $this->assertEquals($newAddress1, $testAddress->getAddress1());
    }

    public function testCanDelete(): void
    {
        $testAddress = $this->getValidAddress();
        $this->repository->save($testAddress, true);
        $current_count = count($this->repository->findAll());
        $this->repository->remove($testAddress, true);
        $this->assertEquals($current_count - 1, count($this->repository->findAll()));
    }

    protected function getValidAddress(): Address
    {
        $address = new Address();
        $address1 = 'test address 1';
        $city = 'test city';
        $region = 'test region';
        $postCode = 'test post code';
        $country = 'test country';
        $address
            ->setAddress1($address1)
            ->setCity($city)
            ->setRegion($region)
            ->setPostCode($postCode)
            ->setCountry($country);
        return $address;
    }

    protected function getInvalidAddress(): Address
    {
        $address = new Address();
        $address->setRegion('test');
        return $address;
    }
}
