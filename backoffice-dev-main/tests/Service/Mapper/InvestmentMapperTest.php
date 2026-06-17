<?php

namespace App\Tests\Service\Mapper;

use App\Dto\Investment\InvestmentRequestDto;
use App\Entity\Asset;
use App\Entity\Enum\ProductMode;
use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\User;
use App\Repository\OfferingRepository;
use App\Repository\UserRepository;
use App\Service\Mapper\InvestmentMapper;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvestmentMapperTest extends KernelTestCase
{
    private InvestmentMapper $service;
    private OfferingRepository|MockObject $offeringRepositoryMock;
    private UserRepository|MockObject $userRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Setup mock service dependencies that we'll configure in the individual tests
        // Repositories - mocking database
        $this->offeringRepositoryMock = $this->createMock(OfferingRepository::class);
        static::getContainer()->set(
            OfferingRepository::class,
            $this->offeringRepositoryMock,
        );
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        static::getContainer()->set(UserRepository::class, $this->userRepositoryMock);

        // You cannot use container->set() anymore after the first container->get() call
        $this->service = static::getContainer()->get(InvestmentMapper::class);
    }

    public function testMapToDto(): void
    {
        $offeringToLink = EntityIdTestUtil::setEntityId(new Offering(), 5);
        $offeringToLink->setPricePerShare('4.85');
        $userToLink = EntityIdTestUtil::setEntityId(new User(), 6782);
        $input = EntityIdTestUtil::setEntityId(new Investment(), 85);
        $input->setOffering($offeringToLink);
        $input->setUser($userToLink);
        $input->setName('Sampler Place - Testingstadt');
        $input->setOrgPricePerShare('7.41');
        $input->setPricePerShare($input->getOrgPricePerShare());
        $input->setShareAmount(1051);
        $input->setNumberOfShares($input->getShareAmount());
        $input->setLifecycleStatus(InvestmentLifecycle::STATE_APPROVED);
        $input->setCreatedAt(new \DateTime('2024-02-12'));
        $input->setUpdatedAt(new \DateTime('2024-02-14'));

        $actual = $this->service->mapToDto($input);

        $this->assertEquals($actual->id, $input->getId());
        $this->assertEquals($actual->offeringId, $input->getOffering()->getId());
        $this->assertEquals($actual->userId, $input->getUser()->getId());
        $this->assertEquals($actual->pricePerShare, $input->getPricePerShare());
        $this->assertEquals($actual->numberOfShares, $input->getShareAmount());
        $this->assertEquals($actual->status, $input->getLifecycleStatus());
        $this->assertEquals($actual->createdAt, $input->getCreatedAt());
        $this->assertEquals($actual->updatedAt, $input->getUpdatedAt());
    }

    public function testMapToEntityNew(): void
    {
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 5);
        $offering->setPricePerShare('4.85');
        $dto = new InvestmentRequestDto(
            offeringId: (string) $offering->getId(),
            numberOfShares: 43,
        );
        $this->offeringRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($offering->getId(), null, null)
            ->willReturn($offering);
        $actual = $this->service->mapToEntity($dto);
        $expectedInvestmentValue = '208.55'; // 43 shares * 4.85 each

        $this->assertEquals($dto->offeringId, $actual->getOffering()->getId());
        $this->assertEquals($dto->numberOfShares, $actual->getShareAmount());
        $this->assertEquals($dto->numberOfShares, $actual->getNumberOfShares());
        $this->assertEquals('normal', $actual->getType());
        $this->assertEquals(
            $offering->getPricePerShare(),
            $actual->getOrgPricePerShare(),
        );
        $this->assertEquals($offering->getPricePerShare(), $actual->getPricePerShare());
        $this->assertEquals($expectedInvestmentValue, $actual->getInvestmentValue());
    }

    public function testMapToEntityExisting(): void
    {
        $offering = EntityIdTestUtil::setEntityId(new Offering(), 5);
        $offering->setPricePerShare('4.85');
        $dto = new InvestmentRequestDto(
            offeringId: (string) $offering->getId(),
            numberOfShares: 43,
            transactionId: 'xfer_mangopaystyleexample1200',
            status: InvestmentLifecycle::STATE_APPROVED,
            type: 'prefunding',
        );
        $this->offeringRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($offering->getId(), null, null)
            ->willReturn($offering);
        $existing = EntityIdTestUtil::setEntityId(new Investment(), 51);
        $existing->setName('1 Cedar House - Oldham');
        $existing->setShareAmount(36);
        $existing->setNumberOfShares($existing->getShareAmount());

        $actual = $this->service->mapToEntity($dto, $existing);

        $this->assertEquals($dto->offeringId, $actual->getOffering()->getId());
        $this->assertEquals($dto->numberOfShares, $actual->getShareAmount());
        $this->assertEquals($dto->numberOfShares, $actual->getNumberOfShares());
        $this->assertEquals($dto->transactionId, $actual->getTransactionId());
        $this->assertEquals($dto->status, $actual->getLifecycleStatus());
        $this->assertEquals($dto->type, $actual->getType());
        $this->assertEquals(
            $offering->getPricePerShare(),
            $actual->getOrgPricePerShare(),
        );
        $this->assertEquals($offering->getPricePerShare(), $actual->getPricePerShare());
    }
}
