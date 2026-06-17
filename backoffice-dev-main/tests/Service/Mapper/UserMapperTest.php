<?php

namespace App\Tests\Service\Mapper;

use App\Dto\User\UserRequestDto;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Entity\UserStatusLog;
use App\Service\Mapper\UserMapper;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserMapperTest extends KernelTestCase
{
    private UserMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(UserMapper::class);
    }

    public function testMapToDto(): void
    {
        $input = EntityIdTestUtil::setEntityId(new User(), 85);
        $input->setUsername('sampler.auto@yielderverse.co.uk');
        $input->setEmail('contact.auto@yielderverse.co.uk');
        $input->setFirstname('Sampler');
        $input->setLastname('Testingplatz');
        $input->setMiddlename('Example Heytch');
        $input->addStatusLog(new UserStatusLog(status: UserStatus::Active));
        $input->setCreatedAt(new \DateTime('2024-02-12'));
        $input->setUpdatedAt(new \DateTime('2024-02-14'));

        $actual = $this->service->mapToDto($input);

        $this->assertEquals($actual->id, $input->getId());
        $this->assertEquals($actual->username, $input->getUserIdentifier());
        $this->assertEquals($actual->contactEmail, $input->getEmail());
        $this->assertEquals($actual->firstName, $input->getFirstname());
        $this->assertEquals($actual->lastName, $input->getLastname());
        $this->assertEquals($actual->middleNames, $input->getMiddlename());
        $this->assertEquals($actual->status, $input->getCurrentStatus());
        $this->assertEquals($actual->createdAt, $input->getCreatedAt());
        $this->assertEquals($actual->updatedAt, $input->getUpdatedAt());
    }

    public function testMapToEntityNew(): void
    {
        $dto = new UserRequestDto(username: 'sampler.auto@yielderverse.co.uk');

        $actual = $this->service->mapToEntity($dto);

        $this->assertEquals($dto->username, $actual->getUserIdentifier());
    }

    public function testMapToEntityExisting(): void
    {
        $dto = new UserRequestDto(contactEmail: 'changed.auto@yielderverse.co.uk');

        $actual = $this->service->mapToEntity($dto);
        $existing = EntityIdTestUtil::setEntityId(new User(), 51);
        $existing->setEmail('original.auto@yielderverse.co.uk');

        $actual = $this->service->mapToEntity($dto, $existing);

        $this->assertEquals($dto->contactEmail, $actual->getEmail());
    }
}
