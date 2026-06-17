<?php

namespace App\Tests\Service\Mapper;

use App\Dto\BankAccount\BankAccountRequestDto;
use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Service\Mapper\BankAccountMapper;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BankAccountMapperTest extends KernelTestCase
{
    private BankAccountMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(BankAccountMapper::class);
    }

    public function testMapToDto(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 441);
        $input = EntityIdTestUtil::setEntityId(new BankAccount(), 85);
        $currentTime = new \DateTime();
        $input->setUser($user);
        $input->setCountry('GB');
        $input->setAccountHolderType(BankAccountHolderType::Personal);
        $input->setAccountNumber('55779913');
        $input->setBankIdentifierCode('200000');
        $input->setStatus(BankAccountStatus::Approved);
        $input->setAccountType(BankAccountType::GB);
        $input->setDescription('Mapping test account');
        $input->setDisplayName('Mapping test account name');
        $input->setProviderId('recipient_m_' . bin2hex(random_bytes(8)));
        $input->setCreatedAt($currentTime);
        $input->setUpdatedAt($currentTime);

        $actual = $this->service->mapToDto($input);

        $this->assertEquals($input->getId(), $actual->id);
        $this->assertEquals($input->getUuid(), $actual->uuid);
        $this->assertEquals($input->getUser()->getId(), $actual->userId);
        $this->assertEquals($input->getCountry(), $actual->country);
        $this->assertEquals($input->getAccountHolderType(), $actual->accountHolderType);
        $this->assertEquals($input->getAccountNumber(), $actual->accountNumber);
        $this->assertEquals($input->getBankIdentifierCode(), $actual->bic);
        $this->assertEquals($input->getStatus(), $actual->status);
        $this->assertEquals('local_bank_transfer', $actual->method);
        $this->assertEquals($input->getProviderId(), $actual->providerId);
        $this->assertEquals($input->getDisplayName(), $actual->displayName);
        $this->assertEquals($input->getDescription(), $actual->description);
        $this->assertEquals($input->getCreatedAt(), $actual->createdAt);
        $this->assertEquals($input->getUpdatedAt(), $actual->updatedAt);
    }

    public function testMapToEntityNew(): void
    {
        $dto = new BankAccountRequestDto(
            country: 'US',
            accountNumber: '1002003004',
            bic: 'CHASUS33XXX',
            description: 'Map from DTO test',
        );

        $actual = $this->service->mapToEntity($dto);

        $this->assertEquals($dto->country, $actual->getCountry());
        $this->assertEquals($dto->accountNumber, $actual->getAccountNumber());
        $this->assertEquals($dto->bic, $actual->getBankIdentifierCode());
        $this->assertEquals($dto->description, $actual->getDescription());
        $this->assertEquals(
            BankAccountHolderType::Personal,
            $actual->getAccountHolderType(),
        );
        $this->assertEquals(BankAccountType::International, $actual->getAccountType());
        $this->assertEquals(BankAccountStatus::Pending, $actual->getStatus());
        $this->assertEquals('GBP US _ 3004', $actual->getDisplayName());
    }

    public function testMapToEntityExisting(): void
    {
        $dto = new BankAccountRequestDto(
            country: 'US',
            accountNumber: '1002003004',
            bic: 'CHASUS33XXX',
            accountHolderType: BankAccountHolderType::Business,
            description: 'Map from DTO test',
        );
        $existing = EntityIdTestUtil::setEntityId(new BankAccount(), 51);
        $existing->setCountry('CA');
        $existing->setAccountNumber('12345678');
        $existing->setBankIdentifierCode('ROYCCAT2XXX');
        $existing->setDescription('Existing entity overwrite test');
        $existing->setStatus(BankAccountStatus::Approved);

        $actual = $this->service->mapToEntity($dto, $existing);

        $this->assertEquals($dto->country, $actual->getCountry());
        $this->assertEquals($dto->accountNumber, $actual->getAccountNumber());
        $this->assertEquals($dto->bic, $actual->getBankIdentifierCode());
        $this->assertEquals($dto->description, $actual->getDescription());
        $this->assertEquals($dto->accountHolderType, $actual->getAccountHolderType());
        $this->assertEquals(BankAccountType::International, $actual->getAccountType());
        $this->assertEquals(BankAccountStatus::Approved, $actual->getStatus());
        $this->assertEquals('GBP US _ 3004', $actual->getDisplayName());
    }
}
