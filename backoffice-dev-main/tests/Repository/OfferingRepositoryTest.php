<?php

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\OfferingAddFields;
use App\Entity\User;
use App\Repository\OfferingRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class OfferingRepositoryTest extends FixtureTestCase
{
    private OfferingRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(OfferingRepository::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->service->findByWithAssociations([], [], 6, 2);
        $this->assertEquals(2, $actual->getCurrentPage());
        $this->assertEquals(6, $actual->getMaxPerPage());
    }

    public function testFindByWithAssociationsOrdering(): void
    {
        // Check ordering by comparing actual with manually sorted
        // default ordering: id ascending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->service->findByWithAssociations([]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->service->findByWithAssociations([], [
                'id' => 'DESC',
            ]));
        rsort($expected);
        $this->assertEquals($expected, $actual);

        // multiple ordering by precedence
        $actual = $this->service->findByWithAssociations([], [
            'isFeatured' => 'DESC',
            'id' => 'ASC',
        ]);
        $t = $f = [];
        foreach ($actual as $object) {
            if ($object->getIsFeatured()) {
                $t[] = $object->getId();
            } else {
                $f[] = $object->getId();
            }
        }
        sort($t);
        sort($f);
        $actual = EntityIdTestUtil::extractIds($actual);
        $this->assertEquals(array_merge($t, $f), $actual);
    }

    public function testFindByWithAssociationsGrouping(): void
    {
        /**
         * Check that grouping does what you'd expect with a few scenarios (add more as necessary)
         * - offeringType should result in 2 results, one for retail, the other for prefunding
         * - assetId should result in just one offering per asset (similar to distinct)
         */
        $actual = $this->service->findByWithAssociations(
            [],
            [],
            10,
            1,
            ['offeringType'],
        );
        $this->assertCount(2, $actual);

        /** @var Offering[] $allOfferings */
        $allOfferings = $this->entityManager->getRepository(Offering::class)->findAll();
        $assetIds = [];
        foreach ($allOfferings as $offering) {
            $assetIds[] = $offering->getAsset()->getId();
        }
        // Need to reset keys due to https://github.com/sebastianbergmann/comparator/issues/112
        $expected = array_values(array_unique($assetIds));
        $actual = $this->service->findByWithAssociations(
            [],
            [],
            count($expected),
            1,
            ['assetId'],
        );
        $actualAssetIds = [];
        foreach ($actual as $offering) {
            $actualAssetIds[] = $offering->getAsset()->getId();
        }
        $this->assertCount(count($expected), $actual);
        $this->assertEqualsCanonicalizing($expected, $actualAssetIds);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->service->findByWithAssociations([
            'offeringType' => 'retail',
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'offeringType' => 'retail',
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
        $results = $this->service->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                if (in_array($key, [
                    'assetId',
                    'assetName',
                    'assetCompanyNumber',
                    'assetAssetType',
                    'assetCurrentStatus',
                ])) {
                    $key = lcfirst(substr($key, 5));
                    $relation = $object->getAsset();
                }
                if (in_array($key, ['investmentUser'])) {
                    $key = 'Id';
                    $relation = $object->getSellInvestment()->getUser();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['name', 'createdBy', 'companyNumber'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } elseif ('sell_investment' == $key) {
                    if ($actual) {
                        $this->assertNotNull($actual);
                    } else {
                        $this->assertNull($actual);
                    }
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
        yield 'Basic equivalence field' => [['isFeatured' => 1]];
        yield 'String match' => [['name' => 'cam']];
        yield 'Created by match' => [['createdBy' => 'ben']];
        yield 'Null conversion true' => [['sell_investment' => 1]];
        yield 'Null conversion false' => [['sell_investment' => 0]];
        yield 'Status relation' => [['lifecycleStatus' => 'approved']];
        yield 'Status relation multi' => [['lifecycleStatus' => ['draft', 'approved']]];
        yield 'Asset relation' => [['assetId' => 2]];
        yield 'Asset string relation' => [['assetName' => 'ev']];
        yield 'Asset string relation 2' => [['assetCompanyNumber' => '008']];
        yield 'Asset status relation' => [[
            'assetCurrentStatus' => AssetStatus::Acquiring,
        ]];
        yield 'Asset relation multi' => [['assetId' => [1, 2]]];
        yield 'Relisting seller' => [['investmentUser' => 3]];
        yield 'Combination 1' => [['offeringType' => 'prefunding', 'name' => 'nix']];
        yield 'Combination 2' => [['isFeatured' => 1, 'name' => 'cam']];
        yield 'Combination 3' => [[
            'offeringType' => 'retail',
            'lifecycleStatus' => 'published',
            'name' => 'bristol',
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaRangeProvider')]
    public function testFindByWithAssociationsCriteriaRanges(
        array $filters,
        array $fieldChecks,
    ): void {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $results = $this->service->findByWithAssociations($filters);
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

    public function testFindFirstPartyByAssetId(): void
    {
        // Use an asset with a secondary market listing fixture
        /** @var Asset $sampleAsset */
        $sampleAsset = $this->searchFixtures(Asset::class, [
            'name' => 'Lodge de Lac - Cumbria',
        ])[0];

        /**
         * Check that if there are more than 1 possible result
         * Still only 1 returns (no crashes)
         */
        $newOffering = new Offering();
        $newOffering->setAsset($sampleAsset);
        $newOffering->setName('Second first party offering test');
        $this->entityManager->persist($newOffering);
        $this->entityManager->flush();

        // Check the offering returnedis what we expected
        $actual = $this->service->findFirstPartyByAssetId($sampleAsset->getId());
        $this->assertInstanceOf(Offering::class, $actual);
        $this->assertSame($sampleAsset->getId(), $actual->getAsset()->getId());
        $this->assertNull($actual->getSellInvestment());
    }

    public function testFindFirstPartyByAssetIdNoneFound(): void
    {
        $newAsset = new Asset();
        $newAsset->setName('Asset with no offering test');
        $this->entityManager->persist($newAsset);
        $this->entityManager->flush();

        // Check that null returns (rather than a crash)
        $actual = $this->service->findFirstPartyByAssetId($newAsset->getId());
        $this->assertNull($actual);
    }

    public function testCreateIfValid(): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $testOffering = $this->getValidOffering();
        $offeringName = $testOffering->getName();

        $repository->save($testOffering, true);

        $createdTestOffering = $this->getCreatedOffering($offeringName);

        $this->compareOfferingsObjects($testOffering, $createdTestOffering);
    }

    //Second Offering on the same Asset
    public function testCreateIfValidSecondOffering(): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $testOffering = $this->getValidOffering();
        $offeringName = $testOffering->getName();

        $this->assertNotEmpty($offeringName);

        $repository->save($testOffering, true);
    }

    public function testCanNotCreateIfInvalid(): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $recCountBefore = count($repository->findAll());

        $testOffering = $this->getInvalidOffering();
        parent::expectException(NotNullConstraintViolationException::class);

        $repository->save($testOffering, true);

        $recCountAfter = count($repository->findAll());
        $this->assertEquals($recCountBefore, $recCountAfter);
    }

    //Cannot run Edit as asset is returning empty value. This was working before
    // but stopped working after code merge with stable.

    public function testCanEdit(): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $offeringForEdit = $repository->find(1);
        $offeringName = $offeringForEdit->getName();

        $newValuation = 300000;

        $offeringForEdit->setValuation($newValuation);
        $repository->save($offeringForEdit, true);

        $offeringForEdit1 = $repository->findOneBy(['name' => $offeringName]);

        $this->assertEquals($newValuation, $offeringForEdit1->getValuation());
    }

    private function getCreatedOffering(string $offeringName)
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $offering = $repository->findOneBy(['name' => $offeringName]);

        return $offering;
    }

    protected function getInvalidOffering(): Offering
    {
        $Offering = new Offering();

        $Offering->setIsSecondaryMrkt('test');

        return $Offering;
    }

    protected function getValidOffering(): Offering
    {
        $randomNumber = rand(0, 25);
        $Offering = new Offering();
        $offeringName = 'Offering';
        $createdAt = new \DateTime();
        $isFeatured = 'Yes';
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

        $offeringasset1 = $this->getValidAsset();
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

    protected function getValidAsset()
    {
        $repository = $this->entityManager->getRepository(Asset::class);

        $offeringasset = $repository->find(1);
        return $offeringasset;
    }

    protected function compareOfferingsObjects(
        Offering $offeringCreated,
        offering $offeringFetched,
    ): void {
        $this->assertEquals($offeringCreated->getName(), $offeringFetched->getName());
        $this->assertEquals(
            $offeringCreated->getCategory(),
            $offeringFetched->getCategory(),
        );
        $this->assertEquals(
            $offeringCreated->getFundingGoal(),
            $offeringFetched->getFundingGoal(),
        );
        $this->assertEquals(
            $offeringCreated->getIsFeatured(),
            $offeringFetched->getIsFeatured(),
        );
        $this->assertEquals(
            $offeringCreated->getIsSecondaryMrkt(),
            $offeringFetched->getIsSecondaryMrkt(),
        );
        $this->assertEquals(
            $offeringCreated->getValuation(),
            $offeringFetched->getValuation(),
        );
        $this->assertEquals(
            $offeringCreated->getEquityOffered(),
            $offeringFetched->getEquityOffered(),
        );
        $this->assertEquals(
            $offeringCreated->getNoOfShares(),
            $offeringFetched->getNoOfShares(),
        );
        $this->assertEquals(
            $offeringCreated->getPricePerShare(),
            $offeringFetched->getPricePerShare(),
        );
        $this->assertEquals(
            $offeringCreated->getNetRentProjected(),
            $offeringFetched->getNetRentProjected(),
        );
        $this->assertEquals(
            $offeringCreated->getGrossProjectReturn(),
            $offeringFetched->getGrossProjectReturn(),
        );
        $this->assertEquals(
            $offeringCreated->getOfferingTerm(),
            $offeringFetched->getOfferingTerm(),
        );
        $this->assertEquals(
            $offeringCreated->getOpenDate(),
            $offeringFetched->getOpenDate(),
        );
        $this->assertEquals(
            $offeringCreated->getCloseDate(),
            $offeringFetched->getCloseDate(),
        );
        $this->assertEquals(
            $offeringCreated->getMinCommitUser(),
            $offeringFetched->getMinCommitUser(),
        );
        $this->assertEquals(
            $offeringCreated->getMaxCommitUser(),
            $offeringFetched->getMaxCommitUser(),
        );
        $this->assertEquals(
            $offeringCreated->getMaxOverFunding(),
            $offeringFetched->getMaxOverFunding(),
        );
        $this->assertEquals(
            $offeringCreated->getComments(),
            $offeringFetched->getComments(),
        );
        //      $this->assertEquals($offeringCreated->getoffset(), $offeringFetched->getoffset());
        $this->assertEquals(
            $offeringCreated->getExternalCommitments(),
            $offeringFetched->getExternalCommitments(),
        );
    }

    public function testCalculatedFields(): void
    {
        // Check that calculated fields are working after entity load
        $repository = $this->entityManager->getRepository(Offering::class);

        // Offering with multiple investments per user
        /** @var Offering $offering */
        $offering = $repository->findOneBy([
            'name' => 'Royal Eversea Glades - Cambridge',
        ]);

        $this->assertLessThan(
            $offering->getInvestmentCount(),
            $offering->getInvestorCount(),
        );
        $this->assertNotEmpty($offering->getRaisedAmount());
        $this->assertNotEmpty($offering->getRaisedPercent());
    }

    public function testQueryAssetListingSummary(): void
    {
        $actual = $this->service->queryAssetListingSummary();
        $expected = [
            'assetId',
            'listings',
            'relistings',
            'sharesListed',
            'valueListed',
            'fundingGoal',
            'equivalentSharesListed',
            'status',
        ];

        // Should have some results with the fixtures
        $this->assertNotEmpty($actual);
        // Check we have the columns we need - sample from first element
        $this->assertEquals($expected, array_keys(reset($actual)));
    }

    /**
     * @psalm-return \Generator<'0 investors'|'2 investors'|'5 investors', array{0: 0|2|5, 1: 0|2|5}, mixed, void>
     */
    public static function investorCountProvider(): \Generator
    {
        yield '5 investors' => [5, 5];
        yield '2 investors' => [2, 2];
        yield '0 investors' => [0, 0];
    }

    /**
     * @psalm-return \Generator<string, array{0: int, 1: int}, mixed, void>
     */
    public static function investmentCountProvider(): \Generator
    {
        yield '7 investments' => [7, 7];
        yield '2 investments' => [2, 2];
        yield '1 investments' => [1, 1];
        yield '0 investments' => [0, 0];
    }

    /**
     * @psalm-return \Generator<string, array{0: int, 1: int, 2: 0|float}, mixed, void>
     */
    public static function raisedAmountProvider(): \Generator
    {
        yield 'raised 550' => [550, 10, 55.01];
        yield 'raised 100105' => [100105, 67, 1494.11];
        yield 'raised 0' => [0, 3, 0];
        yield 'raised 88' => [88, 5, 17.6];
    }

    /**
     * @psalm-return \Generator<string, array{0: 0|5|55|float, 1: int, 2: int, 3: 625|1305|float}, mixed, void>
     */
    public static function raisedPercentProvider(): \Generator
    {
        yield 'raised 55%' => [55, 100000, 45, 1222.22];
        yield 'raised 78.3%' => [78.3, 125000, 75, 1305];
        yield 'raised 0%' => [0, 0, 5, 1305];
        yield 'raised 5%' => [5, 250000, 20, 625];
    }

    public function testCreateOffering(): Offering
    {
        $repository = $this->entityManager->getRepository(Offering::class);

        $testOffering = new Offering();
        $offeringName = 'Offering second';
        $testOffering->setName($offeringName);

        $repository->save($testOffering, true);

        /** @var Offering $createdTestOffering */
        $createdTestOffering = $this->getCreatedOffering($offeringName);

        $this->assertEquals($offeringName, $createdTestOffering->getName());
        $this->assertEquals(0, $createdTestOffering->getIsSecondaryMrkt());

        return $createdTestOffering;
    }

    public function testOfferingAdditionalFields(): void
    {
        /** @var Offering $off */
        $off = $this->testCreateOffering();

        /** @var OfferingAddFields $ad1 */
        $ad1 = new OfferingAddFields();

        $ad1->setFieldKey('newname');
        $ad1->setFieldValue('somethingelse');

        $off->addAddField($ad1);

        $repository = $this->entityManager->getRepository(Offering::class);

        $repository->save($off, true);

        /** @var Offering[] $off_new */
        $off_new = $this->entityManager
            ->getRepository(Offering::class)
            ->findBy(['name' => $off->getName()]);

        $this->assertCount(1, $off_new[0]->getAddFields());

        $ad1_new = $off_new[0]->getAddFields()->get(0);

        $this->assertEquals($ad1->getFieldValue(), $ad1_new->getFieldValue());
        $this->assertEquals($ad1->getFieldKey(), $ad1_new->getFieldKey());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investorCountProvider')]
    public function testGetInvestorCount(int $expected, int $investors): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);
        $offering = new Offering();
        $offering->setName('offering test investor count');
        $repository->save($offering, true);

        for ($i = 0; $i < $investors; $i++) {
            $userRepo = $this->entityManager->getRepository(User::class);
            $user = new User();
            $user->setEmail('john@sm22' . $i . '.co.uk');
            $user->setUsername($user->getEmail());
            $user->setPassword('1!aaaffbbpk!');
            $userRepo->save($user, true);

            $invRepo = $this->entityManager->getRepository(Investment::class);
            $investment = new Investment();
            $investment->setName('investment test');
            $investment->setOffering($offering);
            $investment->setUser($user);
            $investment->setLifecycleStatus('approved');
            $invRepo->save($investment, true);
        }

        $this->entityManager->clear();
        $dbOffering = $repository->findOneBy(['name' => $offering->getName()]);
        $this->assertEquals($expected, $dbOffering->getInvestorCount());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentCountProvider')]
    public function testGetInvestmentCount(int $expected, int $investments): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);
        $offering = new Offering();
        $offering->setName('offering test investment count');
        $repository->save($offering, true);

        //check rejected investment are not counted
        $userRepo = $this->entityManager->getRepository(User::class);
        $user = new User();
        $user->setEmail('john@sm1p.co.uk');
        $user->setUsername($user->getEmail());
        $user->setPassword('1!aaaffbbpk!');
        $userRepo->save($user, true);

        $invRepo = $this->entityManager->getRepository(Investment::class);
        $investment = new Investment();
        $investment->setName('investment test');
        $investment->setOffering($offering);
        $investment->setUser($user);
        $investment->setLifecycleStatus('rejected');
        $invRepo->save($investment, true);

        for ($i = 0; $i < $investments; $i++) {
            $invRepo = $this->entityManager->getRepository(Investment::class);
            $investment = new Investment();
            $investment->setName('investment test');
            $investment->setOffering($offering);
            $investment->setUser($user);
            $investment->setLifecycleStatus('approved');
            $invRepo->save($investment, true);
        }

        $this->entityManager->clear();
        $dbOffering = $repository->findOneBy(['name' => $offering->getName()]);
        $this->assertEquals($expected, $dbOffering->getInvestmentCount());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('raisedAmountProvider')]
    public function testGetRaisedAmount(
        float $expected,
        int $investments,
        float $investmentValue,
    ): void {
        $repository = $this->entityManager->getRepository(Offering::class);
        $offering = new Offering();
        $offering->setName('offering test raised amount');
        $repository->save($offering, true);

        for ($i = 0; $i < $investments; $i++) {
            $invRepo = $this->entityManager->getRepository(Investment::class);
            $investment = new Investment();
            $investment->setName('investment test');
            $investment->setOffering($offering);
            $investment->setLifecycleStatus('approved');
            $investment->setInvestmentValue($investmentValue);
            $invRepo->save($investment, true);
        }

        $this->entityManager->clear();
        $dbOffering = $repository->findOneBy(['name' => $offering->getName()]);
        $this->assertEquals($expected, round($dbOffering->getRaisedAmount()));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('raisedPercentProvider')]
    public function testGetRaisedPercent(
        float $expected,
        float $fundingGoal,
        int $investments,
        float $investmentValue,
    ): void {
        $repository = $this->entityManager->getRepository(Offering::class);
        $offering = new Offering();
        $offering->setName('offering test raised percent');
        $offering->setFundingGoal($fundingGoal);
        $repository->save($offering, true);

        for ($i = 0; $i < $investments; $i++) {
            $invRepo = $this->entityManager->getRepository(Investment::class);
            $investment = new Investment();
            $investment->setName('investment test');
            $investment->setOffering($offering);
            $investment->setLifecycleStatus('approved');
            $investment->setInvestmentValue($investmentValue);
            $invRepo->save($investment, true);
        }

        $this->entityManager->clear();
        $dbOffering = $repository->findOneBy(['name' => $offering->getName()]);
        $this->assertEquals($expected, round($dbOffering->getRaisedPercent(), 1));
    }

    public function testScenarioGetAmountRaisedMissingInvestments(): void
    {
        $repository = $this->entityManager->getRepository(Offering::class);
        $offering = new Offering();
        $offering->setName('offering test external amount only');
        $offering->setExternalCommitments(1928388.11);
        $repository->save($offering, true);

        $this->entityManager->clear();
        $dbOffering = $repository->findOneBy(['name' => $offering->getName()]);
        $this->assertEquals(1928388.11, $dbOffering->getRaisedAmount());
    }
}
