<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetMember;
use App\Entity\BaseEntity;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class AssetRepositoryTest extends FixtureTestCase
{
    private AssetRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // $this->repository = static::getContainer()->get(AssetRepository::class);
        $this->repository = $this->entityManager->getRepository(Asset::class);
    }

    public function testCreateIfValid(): void
    {
        $testAsset = $this->getValidAsset();
        $assetName = $testAsset->getName();
        $this->repository->save($testAsset, true);
        $createdTestAsset = $this->getCreatedAsset($assetName);

        // Fetch from DB the Asset created and compare to Input details
        $this->compareAssetObjects($testAsset, $createdTestAsset);
    }

    public function testCanNotCreateIfInvalid(): void
    {
        $recCountBefore = $this->count($this->repository->findAll());
        $testAsset = $this->getInvalidAsset();
        parent::expectException(NotNullConstraintViolationException::class);
        $this->repository->save($testAsset, true);

        $recCountAfter = $this->count($this->repository->findAll());
        $this->assertEquals($recCountBefore, $recCountAfter);
    }

    public function testCanEdit(): void
    {
        // Cannot run Edit as asset is returning empty value. This was working before
        // but stopped working after code merge with stable.
        // Edit the alternate for the first record in the asset database
        $testAsset = $this->repository->find(8);
        $newAlternateName = 'alternative name ';

        // Pass Alternate name to the Name of Asset
        $testAsset->setName($newAlternateName);

        $this->repository->save($testAsset, true);

        // Fetch the saved Asset from database
        $testAsset = $this->repository->find(8);

        $this->assertEquals($newAlternateName, $testAsset->getName());
    }

    // Add Address to an asset while creating the asset
    public function testCreateAssetWithAdress(): void
    {
        $count = count($this->repository->findAll());

        // Get Valid Asset details
        $testAsset = $this->getValidAsset();

        //Get valid address details
        /** @var AssetAddress $assetAddress */
        $assetAddress = $this->getValidAssetAddressDetails();
        $testAsset->addAddress($assetAddress);
        $this->repository->save($testAsset, true);

        // Fetch the saved Asset & adress from database
        $fetchAsset = $this->repository->find($count + 1);
        /** @var AssetAddress $newAssetAddress */
        $newAssetAddress = $fetchAsset->getAddresses()->get(0);

        $this->assertEquals(
            $assetAddress->getAddress1(),
            $newAssetAddress->getAddress1(),
        );
        $this->assertEquals(
            $assetAddress->getAddress2(),
            $newAssetAddress->getAddress2(),
        );
        $this->assertEquals(
            $assetAddress->getAddress3(),
            $newAssetAddress->getAddress3(),
        );
        $this->assertEquals($assetAddress->getCity(), $newAssetAddress->getCity());
        $this->assertEquals($assetAddress->getRegion(), $newAssetAddress->getRegion());
        $this->assertEquals(
            $assetAddress->getPostCode(),
            $newAssetAddress->getPostCode(),
        );
        $this->assertEquals(
            $assetAddress->getCountry(),
            $newAssetAddress->getCountry(),
        );
        $this->assertEquals(
            $assetAddress->getLongitude(),
            $newAssetAddress->getLongitude(),
        );
        $this->assertEquals(
            $assetAddress->getLatitude(),
            $newAssetAddress->getLatitude(),
        );
    }

    // Add Address to an asset already saved
    public function testCreateAddressForSavedAsset(): void
    {
        $count = count($this->repository->findAll());

        // Get Valid Asset details
        $testAsset = $this->getValidAsset();
        $this->repository->save($testAsset, true);

        //Fetch saved asset to add address and save again
        $savedAsset = $this->repository->find($count + 1);

        //Get valid address details
        /** @var AssetAddress $assetAddress */
        $assetAddress = $this->getValidAssetAddressDetails();
        $savedAsset->addAddress($assetAddress);
        $this->repository->save($savedAsset, true);

        // Fetch the saved Asset & adress from database
        $fetchAsset = $this->repository->find($count + 1);
        /** @var AssetAddress $newAssetAddress */
        $newAssetAddress = $fetchAsset->getAddresses()->get(0);
        $this->assertEquals(
            $assetAddress->getAddress1(),
            $newAssetAddress->getAddress1(),
        );
        $this->assertEquals(
            $assetAddress->getAddress2(),
            $newAssetAddress->getAddress2(),
        );
        $this->assertEquals(
            $assetAddress->getAddress3(),
            $newAssetAddress->getAddress3(),
        );
        $this->assertEquals($assetAddress->getCity(), $newAssetAddress->getCity());
        $this->assertEquals($assetAddress->getRegion(), $newAssetAddress->getRegion());
        $this->assertEquals(
            $assetAddress->getPostCode(),
            $newAssetAddress->getPostCode(),
        );
        $this->assertEquals(
            $assetAddress->getCountry(),
            $newAssetAddress->getCountry(),
        );
        $this->assertEquals(
            $assetAddress->getLongitude(),
            $newAssetAddress->getLongitude(),
        );
        $this->assertEquals(
            $assetAddress->getLatitude(),
            $newAssetAddress->getLatitude(),
        );
    }

    // Add Address to an asset already saved
    public function testRemoveAddressForSavedAsset(): void
    {
        $count = count($this->repository->findAll());

        // Get Valid Asset details
        $testAsset = $this->getValidAsset();
        $this->repository->save($testAsset, true);

        //Fetch saved asset to add address and save again
        $savedAsset = $this->repository->find($count + 1);

        //Get valid First address details
        /** @var AssetAddress $assetAddress1 */
        $assetAddress1 = $this->getValidAssetAddressDetails();
        $savedAsset->addAddress($assetAddress1);
        $this->repository->save($savedAsset, true);

        //Get valid Second address details
        /** @var AssetAddress $assetAddress1 */
        $assetAddress1 = $this->getValidAssetAddressDetails();
        $savedAsset->addAddress($assetAddress1);
        $this->repository->save($savedAsset, true);

        //count number of address for the user entity
        $addressCount = count($this->repository->find($count + 1)->getAddresses());
        $this->assertEquals(2, $addressCount);

        //Fetch Address Collection
        $addressesCollection = $this->repository->find($count + 1)->getAddresses();

        //Get the First Address from the collection
        foreach ($addressesCollection as $assetAddress) {
            $assetAddressFetched = $assetAddress;
            break;
        }
        //Strip off the address and save User
        $savedAsset->removeAddress($assetAddressFetched);
        $this->repository->save($savedAsset, true);

        //count number of address for the user entity after stripp of one address
        $addressCount = count($this->repository->find($count + 1)->getAddresses());
        $this->assertEquals(1, $addressCount);
    }

    public function testCheckAuthor(): void
    {
        // Check the member relation is correctly hydrated
        /** @var Asset $asset */
        $asset = $this->repository->findOneBy(['name' => 'Neptunis Quays - Bristol']);
        $assetMembers = $asset->getMembers();
        $this->assertNotNull($assetMembers);
        $this->assertNotNull($assetMembers[0]);
        $this->assertEquals(
            AssetMember::MEMBER_TYPE_AUTHOR,
            $assetMembers[0]->getMembertype(),
        );
        $this->assertEquals($asset->getId(), $assetMembers[0]->getAsset()->getId());
    }

    protected function getInvalidAsset(): Asset
    {
        $asset = new Asset();
        $asset->setAssetType('test');
        return $asset;
    }

    private function getCreatedAsset(string $assetName): Asset
    {
        $asset = $this->repository->findOneBy(['name' => $assetName]);
        return $asset;
    }

    // Create random Asset
    private function getValidAsset(): Asset
    {
        $asset = new Asset();
        $randomnumber = rand(0, 25);
        $date1 = new \DateTime();
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);
        $asset
            ->setName('DanAsset' . $randomnumber)
            ->setAlternateName('alternateName' . $randomnumber)
            ->setBriefDescription('Created Asset' . bin2hex(random_bytes(180)))
            ->setCompanyNumber('companyNumber' . $randomnumber)
            ->setContactPoint($user)
            ->setDetailedDesc('detailedDesc' . bin2hex(random_bytes(180)))
            ->setDisplayName('displayName' . $randomnumber)
            ->setLegalName('legalName' . $randomnumber)
            //           ->setMembers('members'.$randomnumber)
            ->setOrgEmail('orgEmail' . $randomnumber)
            ->setSector('sector' . $randomnumber)
            ->setTaxId('taxId' . $randomnumber)
            ->setTelephone('telephone' . $randomnumber)
            ->setFundingGoal('100000' . $randomnumber)
            ->setAmountOfShares('1000' . $randomnumber)
            ->setSetupFee('1000' . $randomnumber)
            ->setAdminFee('1000' . $randomnumber)
            ->setManagementFee('1000' . $randomnumber)
            ->setProfitShare('20' . $randomnumber)
            ->setStampDutyUser('stampDutyUser' . $randomnumber)
            ->setAssetType('assetType' . $randomnumber)
            ->setInvestmentTerm('50' . $randomnumber)
            ->setGrossRentalReturnPA('15000' . $randomnumber)
            ->setNetRentalReturnPA('13000' . $randomnumber)
            ->setSellRestricted(true)
            ->setPricePerShare('100' . $randomnumber)
            ->setVisibility(0);
        return $asset;
    }

    private function getValidAssetAddressDetails(): AssetAddress
    {
        $randomnumber = rand(1, 10);
        $assetAddress = new AssetAddress();
        $assetAddress
            ->setAddress1('Address1' . $randomnumber)
            ->setAddress2('Address2' . $randomnumber)
            ->setAddress3('Address3' . $randomnumber)
            ->setCity('City' . $randomnumber)
            ->setRegion('Region' . $randomnumber)
            ->setPostCode('PostCode' . $randomnumber)
            ->setCountry('Country' . $randomnumber)
            ->setLongitude(51.5311716)
            ->setLatitude(-0.1458265);
        return $assetAddress;
    }

    // Function to comparare every field from Asset Database to the every field of Input
    private function compareAssetObjects(Asset $assetCreated, Asset $assetFetched): void
    {
        $this->assertEquals($assetCreated->getName(), $assetFetched->getName());
        $this->assertEquals($assetCreated->getName(), $assetFetched->getName());
        $this->assertEquals(
            $assetCreated->getAdditionalType(),
            $assetFetched->getAdditionalType(),
        );
        $this->assertEquals(
            $assetCreated->getAlternateName(),
            $assetFetched->getAlternateName(),
        );
        $this->assertEquals(
            $assetCreated->getBriefDescription(),
            $assetFetched->getBriefDescription(),
        );
        $this->assertEquals(
            $assetCreated->getCompanyNumber(),
            $assetFetched->getCompanyNumber(),
        );
        $this->assertEquals(
            $assetCreated->getContactPoint(),
            $assetFetched->getContactPoint(),
        );
        $this->assertEquals(
            $assetCreated->getDetailedDesc(),
            $assetFetched->getDetailedDesc(),
        );
        $this->assertEquals(
            $assetCreated->getDisplayName(),
            $assetFetched->getDisplayName(),
        );
        $this->assertEquals(
            $assetCreated->getLegalName(),
            $assetFetched->getLegalName(),
        );
        //     $this->assertEquals($assetCreated->getMembers(), $assetFetched->getMembers());
        $this->assertEquals($assetCreated->getOrgEmail(), $assetFetched->getOrgEmail());
        $this->assertEquals($assetCreated->getSector(), $assetFetched->getSector());
        $this->assertEquals($assetCreated->getTaxId(), $assetFetched->getTaxId());
        $this->assertEquals(
            $assetCreated->getTelephone(),
            $assetFetched->getTelephone(),
        );
        $this->assertEquals(
            $assetCreated->getFundingGoal(),
            $assetFetched->getFundingGoal(),
        );
        $this->assertEquals(
            $assetCreated->getAmountOfShares(),
            $assetFetched->getAmountOfShares(),
        );
        $this->assertEquals($assetCreated->getSetupFee(), $assetFetched->getSetupFee());
        $this->assertEquals($assetCreated->getAdminFee(), $assetFetched->getAdminFee());
        $this->assertEquals(
            $assetCreated->getManagementFee(),
            $assetFetched->getManagementFee(),
        );
        $this->assertEquals(
            $assetCreated->getProfitShare(),
            $assetFetched->getProfitShare(),
        );
        $this->assertEquals(
            $assetCreated->getStampDutyUser(),
            $assetFetched->getStampDutyUser(),
        );
        $this->assertEquals(
            $assetCreated->getAssetType(),
            $assetFetched->getAssetType(),
        );
        $this->assertEquals(
            $assetCreated->getInvestmentTerm(),
            $assetFetched->getInvestmentTerm(),
        );
        $this->assertEquals(
            $assetCreated->getGrossRentalReturnPA(),
            $assetFetched->getGrossRentalReturnPA(),
        );
        $this->assertEquals(
            $assetCreated->getNetRentalReturnPA(),
            $assetFetched->getNetRentalReturnPA(),
        );
        $this->assertEquals(
            $assetCreated->isBuyRestricted(),
            $assetFetched->isBuyRestricted(),
        );
        $this->assertEquals(
            $assetCreated->isSellRestricted(),
            $assetFetched->isSellRestricted(),
        );
        $this->assertEquals(
            $assetCreated->getPricePerShare(),
            $assetFetched->getPricePerShare(),
        );
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->repository->findByWithAssociations([], [], 6, 2);
        $this->assertEquals(2, $actual->getCurrentPage());
        $this->assertEquals(6, $actual->getMaxPerPage());
    }

    public function testFindByWithAssociationsOrdering(): void
    {
        // Check ordering by comparing actual with manually sorted
        // default ordering: id ascending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([], [
                'id' => 'DESC',
            ]));
        rsort($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->repository->findByWithAssociations([
            'assetType' => 'Residential',
        ])->getNbResults();
        $actual = $this->repository->findByWithAssociations([
            'assetType' => 'Residential',
            'abc' => 1,
            'page' => 23,
        ]);
        $this->assertCount($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaProvider')]
    public function testFindByWithAssociationsCriteria(array $filters): void
    {
        /**
         * Check all results match the criteria
         * Use Symfony component PropertyAccessor for non-relational properties
         */
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $results = $this->repository->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                $actual = $propertyAccessor->getValue($object, $key);
                if (in_array($key, ['name', 'companyNumber'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } elseif (is_iterable($expected)) {
                    $this->assertContains($actual, $expected);
                } else {
                    $this->assertEquals($expected, $actual);
                }
            }
        }
    }

    public static function findByCriteriaProvider(): \Generator
    {
        yield 'Basic equivalence field' => [['assetType' => 'Residential']];
        yield 'String match' => [['name' => 'cam']];
        yield 'Status relation' => [['lifecycleStatus' => 'published']];
        yield 'Status relation multi' => [['lifecycleStatus' => [
            'draft',
            'published',
        ]]];
        yield 'Combination 1' => [[
            'visibility' => BaseEntity::VISIBILITY_AUTO,
            'name' => 'cam',
        ]];
        yield 'Combination 2' => [[
            'assetType' => 'Residential',
            'companyNumber' => '3',
        ]];
        yield 'Combination 3' => [[
            'assetType' => 'Commercial',
            'lifecycleStatus' => 'published',
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaRangeProvider')]
    public function testFindByWithAssociationsCriteriaRanges(
        array $filters,
        array $fieldChecks,
    ): void {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $results = $this->repository->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($fieldChecks as $fieldName => $range) {
                if (!isset($range['start']) && !isset($range['end'])) {
                    $this->fail('No expected ranges set for field ' . $fieldName);
                }
                $actual = $propertyAccessor->getValue($object, $fieldName);
                if (isset($range['start'])) {
                    $this->assertGreaterThanOrEqual($range['start'], $actual);
                }
                if (isset($range['end'])) {
                    $this->assertLessThan($range['end'], $actual);
                }
            }
        }
    }

    public static function findByCriteriaRangeProvider(): \Generator
    {
        yield 'CreatedAt Start' => [
            'filters' => [
                'createdAt_gte' => new \DateTime('-4 days'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-4 days')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt End' => [
            'filters' => [
                'createdAt_lt' => new \DateTime('-2 days'),
            ],

            'fieldChecks' => [
                'createdAt' => [
                    'end' => new \DateTime('-2 days')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt Range' => [
            'filters' => [
                'createdAt_gte' => new \DateTime('-4 days'),
                'createdAt_lt' => new \DateTime('-1 days'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-4 days')->setTime(0, 0),
                    'end' => new \DateTime('-1 days')->setTime(0, 0),
                ],
            ],
        ];
    }
}
