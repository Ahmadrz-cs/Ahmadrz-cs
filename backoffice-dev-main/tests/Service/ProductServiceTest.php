<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetDocuments;
use App\Entity\AssetStatusLog;
use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\ProductDocumentType;
use App\Entity\Enum\ProductMode;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Service\AssetService;
use App\Service\Manager\UserManagerV2;
use App\Service\ProductService;
use App\Test\Util\EntityIdTestUtil;
use BcMath\Number;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductServiceTest extends KernelTestCase
{
    private ProductService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ProductService::class);
    }

    public function testSetCommonFields(): void
    {
        $asset = new Asset();
        $asset->setName('Test common field name');
        $asset->setBriefDescription('Test common field description');
        $asset->setInvestmentTerm(48);
        $asset->setAmountOfShares(65912);
        $asset->setPricePerShare(5.82);

        $actual = $this->service->setCommonFields($asset);

        $this->assertEquals($asset->getName(), $actual->getDisplayName());
        $this->assertEquals($asset->getName(), $actual->getName());
        $this->assertEquals($asset->getBriefDescription(), $actual->getDetailedDesc());

        $this->assertEquals(383607.84, $actual->getFundingGoal());

        $this->assertEmpty($actual->getNetProjectedIncome());
        $this->assertEmpty($actual->getNetProjectedYield());

        // Check yield fields are updated if income is not empty
        // Any offering net rent projected will also be overriden
        $asset->setNetProjectedIncome(17952.85);
        $actual = $this->service->setCommonFields($asset);
        $this->assertEqualsWithDelta(
            17952.85 / (5.82 * 65912),
            $actual->getNetProjectedYield(),
            0.00001,
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('productStatusProvider')]
    public function testFillDefaults(
        ?string $assetStartStatus,
        ?string $asetEndStatus,
    ): void {
        /** @var User $superAdminUser */
        $superAdminUser = EntityIdTestUtil::setEntityId(new User(), 44);
        $superAdminUser->setUsername('testSuperAdmin@example.com');

        /** @var UserManagerV2|\PHPUnit\Framework\MockObject\MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->once())
            ->method('getSuperAdmin')
            ->willReturn($superAdminUser);
        $this->service = new ProductService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userManagerMock,
            static::getContainer()->get(AssetService::class),
        );

        // No need for asset-offering combinatorials, so just allow null for one we're not testing
        $assetStartStatus = $assetStartStatus ?? AssetLifecycle::STATE_PUBLISHED;
        $asetEndStatus = $asetEndStatus ?? AssetLifecycle::STATE_PUBLISHED;

        $asset = new Asset();
        $asset->setName('test asset to fill with defaults');
        $asset->setLifecycleStatus($assetStartStatus);

        $actual = $this->service->fillDefaults($asset);
        $this->assertEquals($asetEndStatus, $actual->getLifecycleStatus());
        $this->assertEquals('team@yielders.co.uk', $actual->getOrgEmail());
        $this->assertEquals('stampduty@yielders.co.uk', $actual->getStampDutyUser());
        $this->assertEquals($superAdminUser->getId(), $actual->getCreatedById());
        $this->assertEquals($superAdminUser->getId(), $actual->getCreatedById());
        $this->assertEquals(
            $superAdminUser->getId(),
            $actual->getContactPoint()->getId(),
        );
        // $this->assertTrue($actual->getIsSecondaryMrkt());
        $this->assertCount(1, $actual->getMembers());
        $this->assertEquals(
            $superAdminUser->getId(),
            $actual->getMembers()[0]->getUser()->getId(),
        );
        $this->assertCount(1, $actual->getAddresses());
    }

    public static function productStatusProvider(): \Generator
    {
        yield 'Asset Draft' => [
            AssetLifecycle::STATE_DRAFT,
            AssetLifecycle::STATE_PUBLISHED,
        ];
        yield 'Asset Submitted' => [
            AssetLifecycle::STATE_SUBMITTED,
            AssetLifecycle::STATE_PUBLISHED,
        ];
        yield 'Asset Approved' => [
            AssetLifecycle::STATE_APPROVED,
            AssetLifecycle::STATE_PUBLISHED,
        ];
        yield 'Asset Published' => [
            AssetLifecycle::STATE_PUBLISHED,
            AssetLifecycle::STATE_PUBLISHED,
        ];
        yield 'Asset Rejected' => [
            AssetLifecycle::STATE_REJECTED,
            AssetLifecycle::STATE_REJECTED,
        ];
        yield 'Asset Cancelled' => [
            AssetLifecycle::STATE_CANCELLED,
            AssetLifecycle::STATE_CANCELLED,
        ];
        yield 'Asset Archived' => [
            AssetLifecycle::STATE_ARCHIVED,
            AssetLifecycle::STATE_ARCHIVED,
        ];
    }

    // #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testIdentifyDataMissingForLaunch(): void
    {
        // Financial values should not be zero
        // Need to check string type as the properties are not yet typed
        // Update this test if types are introduced to the financial class properties like fundingGoal
        $asset = new Asset();
        $asset->setName('test asset not ready');
        $asset->setFundingGoal('0');
        $asset->setPricePerShare('0');
        $asset->setAmountOfShares('0');
        $asset->setInvestmentTerm('0');

        $actual = $this->service->identifyDataMissingForLaunch($asset);
        $expectedIssues = [
            'nameSet',
            'nameSynced',
            'spvSet',
            'descriptionSet',
            'addressSet',
            'coordinatesSet',
            'authorSet',
            'contactPointSet',
            'orgEmailSet',
            'stampDutyUserSet',
            'assetStatus',
            'termSet',
            'sharePriceSet',
            'shareAmountSet',
            'fundingGoalSet',
            'yieldSet',
            'investmentTermStartSet',
            // 'commitmentRulesSet',
            'walletsSet',
            'documentsSet',
        ];
        $this->assertEqualsCanonicalizing($expectedIssues, array_keys($actual));

        $asset = $this->createLaunchReadyProduct();
        $actual = $this->service->identifyDataMissingForLaunch($asset);
        $this->assertEmpty($actual);
    }

    public function testIsLaunchReady(): void
    {
        $asset = new Asset();
        $asset->setName('test asset not ready');
        $actual = $this->service->isLaunchReady($asset);
        $this->assertFalse($actual);

        $asset = $this->createLaunchReadyProduct();
        $actual = $this->service->isLaunchReady($asset);
        $this->assertTrue($actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('alreadyLaunchedOfferingProvider')]
    public function testIsAlreadyLaunched(Asset $sample, bool $expected): void
    {
        $actual = $this->service->isAlreadyLaunched($sample);
        $this->assertSame($expected, $actual);
    }

    public static function alreadyLaunchedOfferingProvider(): \Generator
    {
        $assetNotPublished = new Asset()->setLifecycleStatus(AssetLifecycle::STATE_DRAFT);
        $assetPublished = new Asset()->setLifecycleStatus(AssetLifecycle::STATE_PUBLISHED);
        $assetPublished->addStatusLog(new AssetStatusLog(status: AssetStatus::Active));

        yield 'Asset not published' => [$assetNotPublished, false];
        yield 'Published' => [$assetPublished, true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('launchModeProvider')]
    public function testLaunchProduct(ProductMode $launchMode, int $visibility): void
    {
        $asset = $this->createLaunchReadyProduct();
        $this->assertEquals(AssetStatus::Draft, $asset->getCurrentStatus());
        $this->assertCount(0, $asset->getStatusLogs());

        $actual = $this->service->launchProduct($asset, $launchMode);
        $this->assertEquals($visibility, $actual->getVisibility());
        // $this->assertEquals(BaseEntity::VISIBILITY_AUTO, $actual->getVisibility());
        $this->assertTrue($actual->isSellRestricted());
        $this->assertFalse($actual->isBuyRestricted());

        // New status fields update based on launch mode
        $newStatus = match ($launchMode) {
            ProductMode::Retail => AssetStatus::Active,
            ProductMode::Prefunding => AssetStatus::Acquiring,
        };
        $this->assertEquals($newStatus, $asset->getCurrentStatus());
        $this->assertCount(1, $asset->getStatusLogs());
        $this->assertEquals($newStatus, $asset->getStatusLogs()->first()->getStatus());
        $this->assertNull($asset->getStatusLogs()->first()->getTransitionedBy());
    }

    public static function launchModeProvider(): \Generator
    {
        yield 'prefunding mode' => [
            ProductMode::Prefunding,
            BaseEntity::VISIBILITY_VIP,
        ];
        yield 'retail mode' => [ProductMode::Retail, BaseEntity::VISIBILITY_AUTO];
    }

    public function testLaunchProductNotReady(): void
    {
        $this->expectExceptionMessage('Product not ready for launch');
        $asset = new Asset();
        $asset->setName('test launch when not ready');
        $this->service->launchProduct($asset, ProductMode::Retail);
    }

    public function testPrepareInitialTradeOrder(): void
    {
        $issuer = EntityIdTestUtil::setEntityId(new User(), 5);
        $asset = EntityIdTestUtil::setEntityId(new Asset(), 578);
        $asset->setContactPoint($issuer);
        $asset->setAmountOfShares(5020);
        $asset->setPricePerShare('1.67');
        $actual = $this->service->prepareLaunchTradeOrder(
            $asset,
            new Number('1.67'),
            5020,
            new Number('150'),
            new Number(10000),
        );

        $this->assertEquals(new Number('1.67'), $actual->getPricePerShare());
        $this->assertEquals(5020, $actual->getNumberOfShares());
        $this->assertEquals($asset, $actual->getAsset());
        $this->assertEquals($issuer, $actual->getUser());
        $this->assertEquals(90, $actual->getMinimumShares());
        $this->assertEquals(5020, $actual->getMaximumShares()); // capped at shares being listed
        $this->assertEquals(ProductMode::Retail->value, $actual->getNotes());
        $this->assertEquals(TradeOrderStatus::Active, $actual->getStatus());
        $this->assertEquals(TradeDirection::Sell, $actual->getDirection());
        $this->assertEquals(TradeOrderType::Initial, $actual->getType());
        $this->assertNull($actual->getId());

        $existing = EntityIdTestUtil::setEntityId(new TradeOrder(), 5781);
        $actual = $this->service->prepareLaunchTradeOrder(
            $asset,
            new Number('1.67'),
            5020,
            new Number('150'),
            new Number(2000),
            $existing,
            ProductMode::Prefunding,
        );
        $this->assertEquals($existing->getId(), $actual->getId());
        $this->assertEquals(1197, $actual->getMaximumShares());
        $this->assertEquals(ProductMode::Prefunding->value, $actual->getNotes());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('toggleVisibilityProvider')]
    public function testToggleVisibility(Asset $asset, int $expectedVisibility): void
    {
        $actual = $this->service->toggleVisibility($asset);
        $this->assertEquals($expectedVisibility, $actual->getVisibility());

        // $this->assertEquals(BaseEntity::VISIBILITY_AUTO, $asset->getVisibility());
    }

    public static function toggleVisibilityProvider(): \Generator
    {
        /**
         * Just testing toggling between auto and admin
         */
        $assetAdmin = new Asset();
        $assetAdmin->setVisibility(BaseEntity::VISIBILITY_ADMIN);
        $assetAuto = new Asset();

        yield 'Admin to auto' => [
            $assetAdmin,
            BaseEntity::VISIBILITY_AUTO,
        ];
        yield 'Auto to admin' => [$assetAuto, BaseEntity::VISIBILITY_ADMIN];
    }

    public function testSortDocuments(): void
    {
        // Empty state
        $asset = new Asset();
        $asset->setName('asset to switch to retail');
        $actual = $this->service->sortDocuments($asset);
        $expected = [
            'logo' => [],
            'articlesOfAssociation' => [],
            'informationMemorandum' => [],
            'financialSummary' => [],
            'propertyPhotos' => [],
            'others' => [],
        ];
        $this->assertEqualsCanonicalizing($expected, $actual);

        // Populated state
        $asset = $this->createLaunchReadyProduct();
        $actual = $this->service->sortDocuments($asset);
        $this->assertEqualsCanonicalizing(array_keys($expected), array_keys($actual));
        foreach ($actual as $docType => $docList) {
            $this->assertNotEmpty($docList);
            // Check that the sorting has correctly split the docs up
            /** @var AssetDocuments $doc */
            foreach ($docList as $doc) {
                if ('logo' === $docType) {
                    $this->assertEquals('logo', $doc->getDocument()->getTag());
                }
                if ('propertyPhotos' === $docType) {
                    $this->assertEquals(
                        'property_photos',
                        $doc->getDocument()->getTag(),
                    );
                }
                if ('articlesOfAssociation' === $docType) {
                    $this->assertEquals(
                        'read_to_activate',
                        $doc->getDocument()->getTag(),
                    );
                    $this->assertEquals(
                        'Articles of Association',
                        $doc->getDocument()->getDescription(),
                    );
                }
                if ('informationMemorandum' === $docType) {
                    $this->assertEquals(
                        'read_to_activate',
                        $doc->getDocument()->getTag(),
                    );
                    $this->assertEquals(
                        'Information Memorandum',
                        $doc->getDocument()->getDescription(),
                    );
                }
                if ('financialSummary' === $docType) {
                    $this->assertEquals('calculations', $doc->getDocument()->getTag());
                    $this->assertEquals(
                        'Financial Summary',
                        $doc->getDocument()->getDescription(),
                    );
                }
                // Just hard code the "other" doc for now based on what we know is in the demo test product
                if ('others' === $docType) {
                    $this->assertContains($doc->getDocument()->getTag(), [
                        'some_other_one',
                    ]);
                    $this->assertContains($doc->getDocument()->getDescription(), [
                        'This should be different',
                    ]);
                }
            }
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('relationalDocProvider')]
    public function testCreateRelationalDocument(
        ProductDocumentType $type,
        string $expectedClass,
        string $expectedTag,
        ?string $expectedDescription = null,
    ): void {
        $asset = new Asset();

        $actual = $this->service->createRelationalDocument(
            $type,
            $asset,
            new Document(),
        );
        // Check the document tags and description have been automatically set
        $this->assertEquals($expectedTag, $actual->getDocument()->getTag());
        $this->assertEquals(
            $expectedDescription,
            $actual->getDocument()->getDescription(),
        );
        // Check that the document is attached to the relevant relation
        $this->assertInstanceOf($expectedClass, $actual);
        $this->assertCount(1, $asset->getDocuments());
    }

    public static function relationalDocProvider(): \Generator
    {
        yield 'Logo' => [
            ProductDocumentType::Logo,
            AssetDocuments::class,
            'logo',
        ];
        yield 'Articles of Association' => [
            ProductDocumentType::ArticlesOfAssociation,
            AssetDocuments::class,
            'read_to_activate',
            'Articles of Association',
        ];
        yield 'Information Memorandum' => [
            ProductDocumentType::InformationMemorandum,
            AssetDocuments::class,
            'read_to_activate',
            'Information Memorandum',
        ];
        yield 'Financial Summary' => [
            ProductDocumentType::FinancialSummary,
            AssetDocuments::class,
            'calculations',
            'Financial Summary',
        ];
        yield 'Property Photos' => [
            ProductDocumentType::PropertyPhotos,
            AssetDocuments::class,
            'property_photos',
        ];
    }

    private function createLaunchReadyProduct(): Asset
    {
        /** @var User $superAdminUser */
        $superAdminUser = EntityIdTestUtil::setEntityId(new User(), 44);
        $superAdminUser->setUsername('testSuperAdmin@example.com');

        /** @var UserManagerV2|\PHPUnit\Framework\MockObject\MockObject $userManagerMock */
        $userManagerMock = $this->createMock(UserManagerV2::class);
        $userManagerMock
            ->expects($this->once())
            ->method('getSuperAdmin')
            ->willReturn($superAdminUser);
        $this->service = new ProductService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $userManagerMock,
            static::getContainer()->get(AssetService::class),
        );
        $walletFiller = bin2hex(random_bytes(6));

        $asset = new Asset();

        $assetAddress = new AssetAddress();
        $assetAddress->setAddress1('A House');
        // $assetAddress->setAddress2('On Test Lane');
        $assetAddress->setCity('In Test City');
        $assetAddress->setPostCode('TE5T P05T');
        $assetAddress->setCountry('GB');
        $assetAddress->setLatitude('50');
        $assetAddress->setLongitude('0');
        $asset->addAddress($assetAddress);

        $asset->setName('Test asset launch ready');
        $asset->setCompanyNumber('SPVTESTPRODUCT');
        $asset->setBriefDescription('This asset ought to be launch ready. Right?');
        $asset->setPricePerShare('2.88');
        $asset->setAmountOfShares('92415');
        $asset->setInvestmentTerm('36');
        $asset->setLifecycleStatus(AssetLifecycle::STATE_PUBLISHED);
        $asset->setNetProjectedIncome('8970');
        $asset->setTermStart(new \DateTime('-12 days'));

        $asset->setHoldWalletId($walletFiller);
        $asset->setMainWalletId($walletFiller);
        // $asset->setExpensesWalletId($walletFiller);
        // $asset->setTaxWalletId($walletFiller);
        // $asset->setTreasuryWalletId($walletFiller);

        $asset = $this->service->setCommonFields($asset);
        $asset = $this->service->fillDefaults($asset);
        $docsToMake = [
            ['entity' => 'asset', 'tag' => 'logo', 'description' => 'Test logo'],
            [
                'entity' => 'asset',
                'tag' => 'property_photos',
                'description' => 'The living room',
            ],
            [
                'entity' => 'asset',
                'tag' => 'property_photos',
                'description' => 'View of the garden',
            ],
            [
                'entity' => 'asset',
                'tag' => 'read_to_activate',
                'description' => 'Articles of Association',
            ],
            [
                'entity' => 'asset',
                'tag' => 'read_to_activate',
                'description' => 'Information Memorandum',
            ],
            [
                'entity' => 'asset',
                'tag' => 'calculations',
                'description' => 'Financial Summary',
            ],
            [
                'entity' => 'asset',
                'tag' => 'some_other_one',
                'description' => 'This should be different',
            ],
        ];
        foreach ($docsToMake as $docToMake) {
            $doc = new Document();
            $doc->setTag($docToMake['tag']);
            $doc->setDescription($docToMake['description']);
            if ('asset' === $docToMake['entity']) {
                $assetDoc = new AssetDocuments();
                $assetDoc->setDocument($doc);
                $asset->addDocument($assetDoc);
            } else {
                $this->fail(
                    'Something is not right when creating docs for the launch ready product',
                );
            }
        }
        return $asset;
    }
}
