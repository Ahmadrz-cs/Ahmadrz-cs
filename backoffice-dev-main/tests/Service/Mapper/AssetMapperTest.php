<?php

namespace App\Tests\Service\Mapper;

use App\Dto\Asset\AssetAddressResponseDto;
use App\Dto\Asset\AssetRequestDto;
use App\Dto\Asset\AssetResponseDto;
use App\Dto\Document\DocumentResponseDto;
use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetDocuments;
use App\Entity\AssetStatusLog;
use App\Entity\Document;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\Visibility;
use App\Service\Mapper\AssetMapper;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AssetMapperTest extends KernelTestCase
{
    private AssetMapper $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssetMapper::class);
    }

    public function testMapToDto(): void
    {
        $input = EntityIdTestUtil::setEntityId(new Asset(), 85);
        $input->setName('Sampler Place - Testingstadt');
        $input->setBriefDescription('Some strange testing land with no grass');
        $input->setPricePerShare('7.41');
        $input->setAmountOfShares(56820);
        $input->setSharesAvailable(25141);
        $input->setMinimumInvestment('52.54');
        $input->setAssetType('residential');
        $input->setTermStart(new \DateTime('2024-02-12 00:00:00'));
        $input->setInvestmentTerm(24);
        $input->setFeatured(105);
        $input->setNetProjectedIncome('17270');
        $input->setNetProjectedYield('0.0410');
        $input->setVisibility(2);
        // $input->setBuyRestricted(true);
        $input->setSellRestricted(true);
        $input->setCreatedAt(new \DateTime('2024-05-18 17:15:56'));
        $input->setUpdatedAt(new \DateTime('2024-06-02 12:55:12'));

        $address = new AssetAddress();
        $address->setAddress1('56 Testing Lane');
        $address->setAddress2('Bulwark Plains');
        $address->setCity('Houselington');
        $address->setPostCode('HL5 6BP');
        $address->setCountry('GB');
        $address->setLatitude('51.430352');
        $address->setLongitude('-2.60029');
        $input->addAddress($address);

        $document = EntityIdTestUtil::setEntityId(new Document(), 4185);
        $document->setFilename('floor_plan_tests.jpeg');
        $document->setType('image/jpeg');
        $document->setDescription('Floor plans for the asset');
        $document->setTag('property_photos');
        $document->setDocumentUrl('fixtures/test.jpeg');
        $document->setCreatedAt(new \DateTime('2024-05-18 17:15:56'));
        $document->setUpdatedAt(new \DateTime('2024-06-02 12:55:12'));
        $relationalDocument = EntityIdTestUtil::setEntityId(new AssetDocuments(), 2185);
        $relationalDocument->setDocument($document);
        $input->addDocument($relationalDocument);

        $statusLog = new AssetStatusLog(
            status: AssetStatus::Archived,
            occuredAt: new \DateTime('2024-06-02 12:55:12'),
        );
        $input->addStatusLog($statusLog);

        $expected = new AssetResponseDto(
            id: '85',
            name: 'Sampler Place - Testingstadt',
            description: 'Some strange testing land with no grass',
            pricePerShare: '7.41',
            numberOfShares: 56820,
            sharesAvailable: 25141,
            minimumInvestment: '52.54',
            type: 'residential',
            status: AssetStatus::Archived,
            statusOccuredAt: new \DateTime('2024-06-02 12:55:12'),
            termStart: new \DateTime('2024-02-12 00:00:00'),
            termEnd: new \DateTime('2026-02-12 00:00:00'),
            termRemaining: 0,
            termLength: 24,
            netProjectedIncome: '17270',
            netProjectedYield: '0.0410',
            featured: 105,
            buyRestricted: false,
            sellRestricted: true,
            visibility: Visibility::Vip,
            documents: [new DocumentResponseDto(
                id: '4185',
                relationLinkId: '2185',
                relationId: '85',
                filename: 'floor_plan_tests.jpeg',
                description: 'Floor plans for the asset',
                type: 'image/jpeg',
                tag: 'property_photos',
                path: 'fixtures/test.jpeg',
                url: 'https://d2yvvobu8dk8og.cloudfront.net/fixtures/test.jpeg',
                createdAt: $document->getCreatedAt(),
                updatedAt: $document->getUpdatedAt(),
            )],
            address: new AssetAddressResponseDto(
                assetId: '85',
                address1: '56 Testing Lane',
                address2: 'Bulwark Plains',
                address3: null,
                city: 'Houselington',
                postCode: 'HL5 6BP',
                country: 'GB',
                latitude: '51.430352',
                longitude: '-2.60029',
            ),
            fees: $input->getFeesGrouped(),
            createdAt: $input->getCreatedAt(),
            updatedAt: $input->getUpdatedAt(),
        );

        $actual = $this->service->mapToDto($input);
        $this->assertEquals($expected, $actual);
        // Do a string comparison check to check decimal places are correct
        $this->assertSame(json_encode($expected), json_encode($actual));
    }

    public function testMapToEntityNew(): void
    {
        $dto = new AssetRequestDto(name: 'Sampler Plaza - Testlington');

        $actual = $this->service->mapToEntity($dto);

        $this->assertEquals($dto->name, $actual->getName());
    }

    public function testMapToEntityExisting(): void
    {
        $dto = new AssetRequestDto(name: 'Sampler Plaza - Testlington');
        $existing = EntityIdTestUtil::setEntityId(new Asset(), 51);
        $existing->setName('1 Cedar House - Oldham');

        $actual = $this->service->mapToEntity($dto, $existing);

        $this->assertEquals($dto->name, $actual->getName());
        $this->assertEquals($existing->getId(), $actual->getId());
    }
}
