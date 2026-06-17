<?php

namespace App\Tests\Service\Mapper;

use App\Dto\Offering\OfferingRequestDto;
use App\Entity\Asset;
use App\Entity\Enum\ProductMode;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Repository\AssetRepository;
use App\Repository\InvestmentRepository;
use App\Service\Mapper\OfferingMapper;
use App\Test\Util\EntityIdTestUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OfferingMapperTest extends KernelTestCase
{
    private OfferingMapper $service;
    private AssetRepository|MockObject $assetRepositoryMock;
    private InvestmentRepository|MockObject $investmentRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Setup mock service dependencies that we'll configure in the individual tests
        // Repositories - mocking database
        $this->assetRepositoryMock = $this->createMock(AssetRepository::class);
        static::getContainer()->set(AssetRepository::class, $this->assetRepositoryMock);
        $this->investmentRepositoryMock = $this->createMock(InvestmentRepository::class);
        static::getContainer()->set(
            InvestmentRepository::class,
            $this->investmentRepositoryMock,
        );

        // You cannot use container->set() anymore after the first container->get() call
        $this->service = static::getContainer()->get(OfferingMapper::class);
    }

    public function testMapToDto(): void
    {
        $assetToLink = EntityIdTestUtil::setEntityId(new Asset(), 5);
        $input = EntityIdTestUtil::setEntityId(new Offering(), 85);
        $input->setAsset($assetToLink);
        $input->setName('Sampler Place - Testingstadt');
        $input->setOfferingType(ProductMode::Retail->value);
        $input->setPricePerShare('7.41');
        $input->setNoOfShares(56820);
        $input->setIsFeatured(true);
        $input->setLifecycleStatus(OfferingLifecycle::STATE_APPROVED);
        $input->setCreatedAt(new \DateTime('2024-02-12'));
        $input->setUpdatedAt(new \DateTime('2024-02-14'));

        $actual = $this->service->mapToDto($input);

        $this->assertEquals($actual->id, $input->getId());
        $this->assertEquals($actual->assetId, $input->getAsset()->getId());
        $this->assertEquals($actual->name, $input->getName());
        $this->assertEquals($actual->type, $input->getOfferingType());
        $this->assertEquals($actual->pricePerShare, $input->getPricePerShare());
        $this->assertEquals($actual->numberOfShares, $input->getNoOfShares());
        $this->assertEquals($actual->numberOfSharesSold, $input->getSharesSold());
        $this->assertEquals($actual->featured, $input->getIsFeatured());
        $this->assertEquals($actual->status, $input->getLifecycleStatus());
        $this->assertEquals($actual->createdAt, $input->getCreatedAt());
        $this->assertEquals($actual->updatedAt, $input->getUpdatedAt());
    }

    public function testMapToEntityNew(): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 5);
        $asset->setName('Sampler Plaza - Testlington');
        $dto = new OfferingRequestDto(
            name: 'Sampler Plaza - Testlington',
            assetId: (string) $asset->getId(),
        );
        $this->assetRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($asset->getId(), null, null)
            ->willReturn($asset);

        $actual = $this->service->mapToEntity($dto);

        $this->assertEquals($dto->name, $actual->getName());
        $this->assertEquals($dto->assetId, $actual->getAsset()->getId());
    }

    public function testMapToEntityExisting(): void
    {
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 5);
        $asset->setName('Sampler Plaza - Testlington');
        $dto = new OfferingRequestDto(
            name: 'Sampler Plaza - Testlington',
            assetId: (string) $asset->getId(),
        );
        $this->assetRepositoryMock
            ->expects(self::atLeastOnce())
            ->method('find')
            ->with($asset->getId(), null, null)
            ->willReturn($asset);
        $existing = EntityIdTestUtil::setEntityId(new Offering(), 51);
        $existing->setName('1 Cedar House - Oldham');

        $actual = $this->service->mapToEntity($dto, $existing);

        $this->assertEquals($dto->name, $actual->getName());
        $this->assertEquals($existing->getId(), $actual->getId());
    }
}
