<?php

namespace App\Tests\Repository;

use App\Entity\Address;
use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\User;
use App\Repository\InvestmentRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class InvestmentRepositoryTest extends FixtureTestCase
{
    private InvestmentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(Investment::class);
    }

    public function testFindAllByAssetsQuerySettlementBeforeDate(): void
    {
        $sample = $this->searchFixtures(Investment::class, ['status' => 'approved']);
        $assetId = $sample[0]->getOffering()->getAsset()->getId();
        $expected = $this->searchFixtures(
            Investment::class,
            ['status' => 'approved', 'asset' => $assetId],
            true,
        );

        /**
         * Try to find all approved investments before the day they were made
         * Should find no investments
         */
        $actual = $this->repository
            ->findAllByAssetsQuery(
                $expected,
                '',
                [$assetId],
                true,
                $sample[0]->getCreatedAt(),
            )
            ->getQuery()
            ->getResult();
        $this->assertCount(0, $actual);

        /**
         * Try to find all approved investments before the day after they were made
         * Should find some investments
         */
        $actual = $this->repository
            ->findAllByAssetsQuery(
                $expected,
                '',
                [$assetId],
                true,
                $sample[0]->getCreatedAt()->add(new \DateInterval('P1D')),
            )
            ->getQuery()
            ->getResult();
        $this->assertCount(count($expected), $actual);
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
            'type' => 'prefunding',
        ])->getNbResults();
        $actual = $this->repository->findByWithAssociations([
            'type' => 'prefunding',
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
                if (in_array($key, ['userId', 'userIsVIP'])) {
                    $key = lcfirst(substr($key, 4));
                    $relation = $object->getUser();
                }
                if (in_array($key, ['assetId', 'assetName'])) {
                    $key = lcfirst(substr($key, 5));
                    $relation = $object->getOffering()->getAsset();
                }
                if ('offeringId' == $key) {
                    $key = 'id';
                    $relation = $object->getOffering();
                }
                if ('corporateInvestor' == $key) {
                    $key = 'corporateInvestor';
                    $relation = $object->getUser()->getInvestor();
                }
                if ('username' == $key) {
                    $relation = $object->getUser();
                }
                if (in_array($key, ['hasDocuments'])) {
                    $key = lcfirst(substr($key, 3));
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['documents'])) {
                    if ($expected) {
                        $this->assertNotEmpty($actual);
                    } else {
                        $this->assertEmpty($actual);
                    }
                } elseif (in_array($key, ['username', 'name'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } elseif (is_iterable($expected)) {
                    $this->assertContains($actual, $expected);
                } else {
                    $this->assertEquals($expected, $actual);
                }
                unset($relation);
            }
        }
    }

    public static function findByCriteriaProvider(): \Generator
    {
        yield 'Basic equivalence field' => [['type' => 'prefunding']];
        yield 'Status relation single' => [['lifecycleStatus' => 'approved']];
        yield 'Asset relation single' => [['assetId' => 2]];
        yield 'Offering relation single' => [['offeringId' => 5]];
        yield 'User relation single' => [['userId' => 2]];
        yield 'Status relation multi' => [['lifecycleStatus' => [
            'approved',
            'rejected',
        ]]];
        yield 'Asset relation multi' => [['assetId' => [2, 4]]];
        yield 'Offering relation multi' => [['offeringId' => [4, 5]]];
        yield 'User relation multi' => [['userId' => [2, 4]]];
        yield 'User string match' => [['username' => 'freya.auto']];
        yield 'Asset name string match' => [['assetName' => 'Lodge']];
        yield 'VIP' => [['userIsVIP' => 1]];
        yield 'Corporate' => [['corporateInvestor' => 1]];
        yield 'Documents existence' => [['hasDocuments' => 1]];
        yield 'Combination 1' => [['userIsVIP' => 1, 'lifecycleStatus' => 'settled']];
        yield 'Combination 2' => [[
            'userId' => 1,
            'type' => 'normal',
            'lifecycleStatus' => 'settled',
        ]];
        yield 'Combination 3' => [[
            'hasDocuments' => 0,
            'lifecycleStatus' => 'settled',
        ]];
    }

    public function testCountInvestmentsInDateRangeByStatus(): void
    {
        $start = new \DateTime('-3 months')->setTime(0, 0);
        $end = new \DateTime('-1 months')->setTime(0, 0);

        $sample = $this->repository->buildQueryWithAssociations([
            'lifecycleStatus' => InvestmentLifecycle::STATE_SETTLED,
            'createdAt_gte' => $start,
            'createdAt_lt' => $end,
        ])->getResult();

        $actual = $this->repository->countInvestmentsInDateRangeByStatus(
            InvestmentLifecycle::STATE_SETTLED,
            $start,
            $end,
        );
        $this->assertEquals(count($sample), $actual);
    }

    public function testFindUserInvestmentsInAsset(): void
    {
        $sampleAsset = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['companyNumber' => 'SPVAF00013'], // Royal Eversea Glades - Cambridge
            true,
        )[0];
        $sampleUser = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        )[0];
        $actual = $this->repository->findUserInvestmentsInAsset(
            $sampleAsset,
            $sampleUser,
        );
        $this->assertGreaterThanOrEqual(1, count($actual));
        $shareAmounts = [];
        foreach ($actual as $item) {
            $this->assertEquals('settled', $item->getLifecycleStatus());
            $this->assertEquals(
                $sampleAsset,
                $item->getOffering()->getAsset()->getId(),
            );
            $this->assertEquals($sampleUser, $item->getUser()->getId());
            $shareAmounts[] = (int) $item->getShareAmount();
        }
        // check ordering
        $expected = $shareAmounts;
        sort($expected);
        $this->assertEquals($expected, $shareAmounts);
    }

    public function testFindUserInvestmentsInAssetPrefundingOnly(): void
    {
        $sampleAsset = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['companyNumber' => 'SPVAF00003'], // Lodge de Lac
            true,
        )[0];
        $sampleUser = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'freya.auto@test.yielderverse.co.uk'],
            true,
        )[0];
        $actual = $this->repository->findUserInvestmentsInAsset(
            $sampleAsset,
            $sampleUser,
            true,
        );
        // must have at least one result otherwise test is doing nothing
        $this->assertGreaterThanOrEqual(1, count($actual));
        $shareAmounts = [];
        foreach ($actual as $item) {
            $this->assertEquals('prefunding', $item->getType());
            $shareAmounts[] = (int) $item->getShareAmount();
        }
        $expected = $shareAmounts;
        sort($expected);
        $this->assertEquals($expected, $shareAmounts);
    }

    public function testFindSettlementsInDateRange(): void
    {
        /** @var Investment $sampleInvestment */
        $sampleInvestment = $this->searchFixtures(Investment::class, [
            'comments' => 'Varied settlement date asset 2',
        ])[0];

        $startDate = new \DateTime('-8 months');
        $endDate = new \DateTime('-2 months');
        /** @var Investment[] $actual */
        $actual = $this->repository->findSettlementsInDateRange($startDate, $endDate);
        // must have at least one result otherwise test is doing nothing
        $this->assertGreaterThanOrEqual(1, count($actual));
        foreach ($actual as $item) {
            $this->assertGreaterThanOrEqual(
                $startDate,
                $item->getStatus()->getSettledOn(),
            );
            $this->assertLessThan($endDate, $item->getStatus()->getSettledOn());
        }

        // Try again but with asset id
        $actual = $this->repository->findSettlementsInDateRange(
            $startDate,
            $endDate,
            $sampleInvestment->getOffering()->getAsset()->getId(),
        );
        $this->assertGreaterThanOrEqual(1, count($actual));
        foreach ($actual as $item) {
            $this->assertGreaterThanOrEqual(
                $startDate,
                $item->getStatus()->getSettledOn(),
            );
            $this->assertLessThan($endDate, $item->getStatus()->getSettledOn());
            $this->assertEquals(
                $sampleInvestment->getOffering()->getAsset()->getId(),
                $item->getOffering()->getAsset()->getId(),
            );
        }
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
                'createdAt_gte' => new \DateTime('-2 days'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-2 days')->setTime(0, 0),
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

    public function testCreateInvestmentUser1Offer1(): void
    {
        //Get User to set as contact point for asset
        $randomnumber = rand(1, 10);
        $user = $this->getValidUser($randomnumber);

        //Create Asset with the user as contactPoint, Save to db and Fetch from DB.
        $createdAsset = $this->getValidAsset($user);
        $createdAssetName = $createdAsset->getName();
        $asset = $this->getCreatedAsset($createdAssetName);

        //Create Offering for the asset created above, save to DB and fetch from DB
        $createdOffering = $this->getValidOffering($asset);
        $createdOfferingName = $createdOffering->getName();
        $offering = $this->getCreatedOffering($createdOfferingName);

        //Test Case to create Investment with User1 and Offering1
        $testInvestment = $this->getValidInvestment($user, $offering);
        $investmentName = $testInvestment->getName();
        $this->repository->save($testInvestment, true);

        $testCreatedInvestment = $this->getCreatedInvestment($investmentName);
        $this->compareInvestmentObjects($testInvestment, $testCreatedInvestment);
    }

    public function testCreateInvestmentUser2Offer1(): void
    {
        //Get User to set as contact point for asset
        $randomUser = rand(1, 10);
        $user = $this->getValidUser($randomUser);

        //Create Asset with the user as contactPoint, Save to db and Fetch from DB.
        $createdAsset = $this->getValidAsset($user);
        $createdAssetName = $createdAsset->getName();
        $asset = $this->getCreatedAsset($createdAssetName);

        //Create Offering for the asset created above, save to DB and fetch from DB
        $createdOffering = $this->getValidOffering($asset);
        $createdOfferingName = $createdOffering->getName();
        $offering = $this->getCreatedOffering($createdOfferingName);

        //create Investment with User1 and Offering1
        $testInvestment1 = $this->getValidInvestment($user, $offering);
        $investmentName1 = $testInvestment1->getName();
        $this->repository->save($testInvestment1, true);

        //Get details of User 2. Should be different from user above
        if ($randomUser > 9) {
            $user2 = $this->getValidUser($randomUser - 1);
        } else {
            $user2 = $this->getValidUser($randomUser + 1);
        }

        //create Investment with User2 and Offering1
        $testInvestment2 = $this->getValidInvestment($user2, $offering);
        $investmentName2 = $testInvestment2->getName();
        $this->repository->save($testInvestment2, true);

        //Fetch Investment1 & Investment2 by different users on same offering
        $testCreatedInvestment1 = $this->getCreatedInvestment($investmentName1);
        $testCreatedInvestment2 = $this->getCreatedInvestment($investmentName2);

        //Asset the offering should be same but user should be different between 1 & 2
        $this->assertEquals(
            $testCreatedInvestment1->getOffering(),
            $testCreatedInvestment2->getOffering(),
        );

        //Don't know how to write assert not equals to.
        //   $this->assertN($testCreatedInvestment1->getUser(), $testCreatedInvestment2->getUser());
    }

    public function testCreateInvestmentUser1Offer2(): void
    {
        //Get User to set as contact point for asset
        $randomUser = rand(1, 10);
        $user = $this->getValidUser($randomUser);

        //Create Asset with the user as contactPoint, Save to db and Fetch from DB.
        $createdAsset = $this->getValidAsset($user);
        $createdAssetName = $createdAsset->getName();
        $asset = $this->getCreatedAsset($createdAssetName);

        //Create Offering1 for the asset created above, save to DB and fetch from DB
        $createdOffering1 = $this->getValidOffering($asset);
        $createdOfferingName1 = $createdOffering1->getName();
        $offering1 = $this->getCreatedOffering($createdOfferingName1);

        //create Investment with User1 and Offering1
        $testInvestment1 = $this->getValidInvestment($user, $offering1);
        $investmentName1 = $testInvestment1->getName();
        $this->repository->save($testInvestment1, true);

        //Create Offering2 for the same asset created above, save to DB and fetch from DB
        $createdOffering2 = $this->getValidOffering($asset);
        $createdOfferingName2 = $createdOffering2->getName();
        $offering2 = $this->getCreatedOffering($createdOfferingName2);

        //create Investment with User1 and Offering2
        $testInvestment2 = $this->getValidInvestment($user, $offering2);
        $investmentName2 = $testInvestment2->getName();
        $this->repository->save($testInvestment2, true);

        //Fetch Investment1 & Investment2 by different users on same offering
        $testCreatedInvestment1 = $this->getCreatedInvestment($investmentName1);
        $testCreatedInvestment2 = $this->getCreatedInvestment($investmentName2);

        //the user should be same but offering should be different
        $this->assertEquals(
            $testCreatedInvestment1->getUser(),
            $testCreatedInvestment2->getUser(),
        );

        //Don't know how to write assert not equals to.
        //   $this->assertN($testCreatedInvestment1->getOffering(), $testCreatedInvestment2->getOffering());
    }

    public function getValidInvestment(User $user, Offering $offering): Investment
    {
        $investment = new Investment();
        $randomNumber = rand(0, 100);

        $transactionId = 'Tran2001';
        $name = 'Dante';
        $investmentValue = '10000';
        $numberOfShares = 100;
        $currency = 'GBP';
        $interestRate = 3.456;
        $term = 12;
        $orgPricePerShare = 1000;
        $comments = 'Testing Create Investment';
        //   $investmentOffering1 = $this->getValidOffering($offeringName);
        //   $investUser1 = $this->getValidUser($userName);

        $investment
            ->setName($name . $randomNumber)
            ->setInvestmentValue($investmentValue)
            ->setNumberOfShares($numberOfShares)
            ->setCurrency($currency)
            ->setInterestRate($interestRate)
            ->setTerm($term)
            ->setOrgPricePerShare($orgPricePerShare)
            ->setComments($comments . $randomNumber)
            ->setOffering($offering)
            ->setUser($user)
            ->setVisibility(0);

        return $investment;
    }

    /*
     * private function getValidUser(string $userName)
     * {
     *
     * $repository = $this->entityManager->getRepository(User::class);
     *
     * $investmentUser = $this->repository->findOneBy(
     * array('username' => $userName));
     *
     * return $investmentUser;
     * }
     *
     * private function getValidOffering(string $offeringName)
     * {
     *
     * $repository = $this->entityManager->getRepository(Offering::class);
     *
     * $investmentOffering = $this->repository->findOneBy(
     * array('name' => $offeringName));
     *
     * return $investmentOffering;
     * }
     */
    private function getCreatedInvestment(string $name)
    {
        $investment = $this->repository->findOneBy(['name' => $name]);

        return $investment;
    }

    /**
     * @todo Sayak please fix this test case as its failing after a recent merge
     */
    public function testCanNotCreateIfInvalid(): void
    {
        $repository = $this->entityManager->getRepository(Asset::class);

        //    $this->assertEquals( 0, count( $repository->findAll() ) );
        //    $this->assertNull( $repository->find(1) );

        $testAsset = $this->getInvalidAsset();
        parent::expectException(NotNullConstraintViolationException::class);

        $repository->save($testAsset, true);
    }

    protected function getInvalidAsset(): Asset
    {
        $asset = new Asset();
        $asset->setAssetType('test');
        return $asset;
    }

    protected function getInvalidAddress()
    {
        $address = new Address();
        $address->setAddress2('test');
        return $address;
    }

    // Create and Store valid offering for a given asset
    protected function getValidOffering(Asset $offeringasset1)
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $testOffering = $this->getValidOfferingDetails($offeringasset1);
        $offeringName = $testOffering->getName();

        $repository->save($testOffering, true);

        $createdTestOffering = $this->getCreatedOffering($offeringName);

        return $createdTestOffering;
    }

    // Fetch the offering created from database
    protected function getCreatedOffering(string $offeringName)
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $offering = $repository->findOneBy(['name' => $offeringName]);

        return $offering;
    }

    // Create random offerng details for given asset
    protected function getValidOfferingDetails(Asset $offeringasset1): Offering
    {
        $randomNumber = rand(0, 25);
        $Offering = new Offering();
        $offeringName = 'Offering';
        $createdAt = new \DateTime();
        $isFeatured = true;
        $isSecondaryMarket = true;
        $valuation = 500000;
        $equityOffered = 2000;
        $noOfShares = 10000;
        $pricePerShare = 10;
        $netRentProjected = 5000;
        $grossProjectReturn = 60;
        $openDate = new \DateTime('2016-09-12');
        $closeDate = new \DateTime('2017-01-31');
        $minCommitUser = 10;
        $maxCommitUser = 20;
        $maxOverFunding = 100000;
        $category = 'category';

        // $offeringasset1 = $this->getValidAsset();
        $Offering
            ->setName($offeringName . $randomNumber)
            ->setIsFeatured($isFeatured)
            ->setIsSecondaryMrkt($isSecondaryMarket)
            ->setasset($offeringasset1)
            ->setCreatedAt($createdAt)
            ->setValuation($valuation)
            ->setEquityOffered($equityOffered)
            ->setNoOfShares($noOfShares)
            ->setPricePerShare($pricePerShare)
            ->setNetRentProjected($netRentProjected)
            ->setGrossProjectReturn($grossProjectReturn)
            ->setOpenDate($openDate)
            ->setCloseDate($closeDate)
            ->setMinCommitUser($minCommitUser)
            ->setMaxCommitUser($maxCommitUser)
            ->setMaxOverFunding($maxOverFunding)
            ->setCategory($category . $randomNumber)
            ->setVisibility(0);

        return $Offering;
    }

    // Create the asset in the database for a given contact point
    protected function getValidAsset(User $user)
    {
        $repository = $this->entityManager->getRepository(Asset::class);

        $testAsset = $this->getValidAssetDetails($user);
        $assetName = $testAsset->getName();

        $repository->save($testAsset, true);

        $createdTestAsset = $this->getCreatedAsset($assetName);

        return $createdTestAsset;
    }

    // Fetch the created asset from database
    private function getCreatedAsset(string $assetName)
    {
        $repository = $this->entityManager->getRepository(Asset::class);
        $asset = $repository->findOneBy(['name' => $assetName]);
        return $asset;
    }

    //Create random Asset Object for Given contact point
    protected function getValidAssetDetails(User $user): Asset
    {
        $asset = new Asset();
        $randomnumber = rand(0, 25);
        $date1 = new \DateTime();
        //   $user = $this->getValidUser();
        $asset
            ->setName('DanAsset' . $randomnumber)
            ->setAdditionalType('additionalType' . $randomnumber)
            ->setAlternateName('alternateName' . $randomnumber)
            ->setBriefDescription('Created Asset' . $randomnumber)
            ->setCompanyNumber('companyNumber' . $randomnumber)
            ->setContactPoint($user)
            ->setDetailedDesc('detailedDesc' . $randomnumber)
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

    //Fetch user record from database
    /**
     * @psalm-param int<1, 10> $randomnumber
     */
    private function getValidUser(int $randomnumber)
    {
        //    $randomnumber = rand(0,10);
        $repository = $this->entityManager->getRepository(User::class);
        $user = $repository->find($randomnumber);
        return $user;
    }

    public function compareInvestmentObjects(
        Investment $investmentCreated,
        Investment $investmentFetched,
    ): void {
        $this->assertEquals($investmentCreated, $investmentFetched);
        $this->assertEquals(
            $investmentCreated->getName(),
            $investmentFetched->getName(),
        );
        $this->assertEquals(
            $investmentCreated->getInvestmentValue(),
            $investmentFetched->getInvestmentValue(),
        );
        $this->assertEquals(
            $investmentCreated->getNumberOfShares(),
            $investmentFetched->getNumberOfShares(),
        );
        $this->assertEquals(
            $investmentCreated->getCurrency(),
            $investmentFetched->getCurrency(),
        );
        $this->assertEquals(
            $investmentCreated->getInterestRate(),
            $investmentFetched->getInterestRate(),
        );
        $this->assertEquals(
            $investmentCreated->getTerm(),
            $investmentFetched->getTerm(),
        );
        $this->assertEquals(
            $investmentCreated->getOrgPricePerShare(),
            $investmentFetched->getOrgPricePerShare(),
        );

        $this->assertEquals(
            $investmentCreated->getComments(),
            $investmentFetched->getComments(),
        );
        $this->assertEquals(
            $investmentCreated->getOffering(),
            $investmentFetched->getOffering(),
        );
        $this->assertEquals(
            $investmentCreated->getUser(),
            $investmentFetched->getUser(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('opportunitySharePriceProvider')]
    public function testCapitalRepaidOverride(
        int $expected,
        int $input,
        string $type,
    ): void {
        $sharePrice = 1.28;
        $exampleName = 'testCapitalRepaid';

        // create our long daisy chain of entities
        $asset = new Asset();
        $asset->setPricePerShare($sharePrice);
        $asset->setName($exampleName);
        $offering = new Offering();
        $offering->setAsset($asset);
        $offering->setName($exampleName);
        $investment = new Investment();
        $investment->setName($exampleName);
        $investment->setOffering($offering);
        $investment->setShareAmount(1000);
        $investment->setInvestmentValue(1280);
        $investment->setType($type);
        $additionalField = new InvestmentAddFields();
        $additionalField->setFieldKey('capitalRepaid');
        $additionalField->setFieldValue($input);
        $investment->addAddField($additionalField);

        // save this to the database so that we can "load" it from db
        $this->entityManager->persist($asset);
        $this->entityManager->persist($offering);
        $this->entityManager->persist($investment);
        $this->entityManager->flush();

        // trigger the postLoad event by syncing up our $investment object with what's in db
        $this->entityManager->refresh($investment);
        $this->assertEquals($expected, $investment->getDivestedShares());
        $this->assertEquals($expected * $sharePrice, $investment->getDivestedAmount());
    }

    public static function opportunitySharePriceProvider(): \Generator
    {
        yield 'Prefunding type' => [500, 500, 'prefunding'];
        yield 'Normal type' => [0, 500, 'normal'];
        yield 'Off-market type' => [0, 500, 'off-market'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('queryInvestmentSummaryProvider')]
    public function testQueryInvestmentSummary(?bool $settledOnly): void
    {
        $investmentState = match ($settledOnly) {
            false => OfferingLifecycle::STATE_APPROVED,
            default => OfferingLifecycle::STATE_SETTELED,
        };

        $actual = match ($settledOnly) {
            null => $this->repository->queryInvestmentSummary(),
            default => $this->repository->queryInvestmentSummary($settledOnly),
        };

        $expectedColumns = [
            'assetId',
            'shares',
            'value',
            'isRelisted',
            'investmentType',
        ];

        // Should have some results with the fixtures
        $this->assertNotEmpty($actual);
        // Check we have the columns we need - sample from first element
        $this->assertEquals($expectedColumns, array_keys(reset($actual)));

        // By default, should only aggregate settled investments
        // Will just sample the first asset that comes back
        $actualAssetShares = 0;
        $assetId = null;
        // Sum up all the shares for a given asset
        foreach ($actual as $sample) {
            if (is_null($assetId) || $assetId === $sample['assetId']) {
                $actualAssetShares += $sample['shares'];
            } else {
                break;
            }
            $assetId = $sample['assetId'];
        }
        // Then query the fixtures for the same thing and ensure they match
        $matchingInvestments = $this->searchFixtures(Investment::class, [
            'asset' => $assetId,
            'status' => $investmentState,
        ]);
        $expectedAssetShares = 0;
        foreach ($matchingInvestments as $investment) {
            $expectedAssetShares += $investment->getShareAmount();
        }
        $this->assertEquals($expectedAssetShares, $actualAssetShares);
    }

    public static function queryInvestmentSummaryProvider(): \Generator
    {
        yield 'Default' => [null];
        yield 'Settled only' => [true];
        yield 'Non settled only' => [false];
    }
}
