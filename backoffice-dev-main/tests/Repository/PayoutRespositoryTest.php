<?php

namespace App\Tests\Repository;

use App\Entity\Investment;
use App\Entity\Payout;
use App\Repository\PayoutRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class PayoutRespositoryTest extends FixtureTestCase
{
    private PayoutRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(Payout::class);
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
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([
            ]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->repository->findByWithAssociations([
            ], ['id' => 'DESC']));
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
                if (in_array($key, ['userId'])) {
                    $key = lcfirst(substr($key, 4));
                    $relation = $object->getCreditedUser();
                    // Also check older payout-by-investment
                    if (!$relation) {
                        $relation = $object->getInvestment()->getUser();
                    }
                }
                if (in_array($key, ['assetId', 'assetName'])) {
                    $key = lcfirst(substr($key, 5));
                    $relation = $object->getAsset();
                    // Also check older payout-by-investment
                    if (!$relation) {
                        $relation = $object->getInvestment()->getOffering()->getAsset();
                    }
                }
                if (in_array($key, ['investmentId'])) {
                    $key = lcfirst(substr($key, 10));
                    $relation = $object->getInvestment();
                }

                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (is_iterable($expected)) {
                    $this->assertContains($actual, $expected);
                } elseif (in_array($key, ['name'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } else {
                    $this->assertEquals($expected, $actual);
                }
                unset($relation);
            }
        }
    }

    public static function findByCriteriaProvider(): \Generator
    {
        yield 'Basic equivalence field' => [['payoutType' => 1]];
        yield 'Basic equivalence field multi' => [['payoutType' => [0, 1]]];
        yield 'Asset relation' => [['assetId' => 1]];
        yield 'Asset relation multi' => [['assetId' => [1, 2]]];
        yield 'Investment relation' => [['investmentId' => 1]];
        yield 'Investment relation multi' => [['investmentId' => [1, 2]]];
        yield 'User relation' => [['userId' => 1]];
        yield 'User relation multi' => [['userId' => [1, 2]]];
        yield 'Asset name string match' => [['assetName' => 'Lodge']];
        yield 'Combination 1' => [['payoutType' => 0, 'userId' => 1]];
        yield 'Combination 2' => [['payoutType' => 0, 'investmentId' => 1]];
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
                'createdAt_gte' => new \DateTime('-2 months'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-2 months')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt End' => [
            'filters' => [
                'createdAt_lt' => new \DateTime('-2 months'),
            ],

            'fieldChecks' => [
                'createdAt' => [
                    'end' => new \DateTime('-2 months')->setTime(0, 0),
                ],
            ],
        ];
        yield 'CreatedAt Range' => [
            'filters' => [
                'createdAt_gte' => new \DateTime('-4 months'),
                'createdAt_lt' => new \DateTime('-1 months'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-4 months')->setTime(0, 0),
                    'end' => new \DateTime('-1 months')->setTime(0, 0),
                ],
            ],
        ];
    }

    public function testGetPayouts(): void
    {
        try {
            $count_payouts = $this->repository->count([]);

            $this->assertGreaterThan(15, $count_payouts[0][1]);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * @return Investment|null
     */
    public function testAddPayout()
    {
        try {
            $investmentRepository = $this->entityManager->getRepository(Investment::class);

            $rand_id = 10; // rand(4, 10);
            /** @var Investment $investObj */
            $investObj = $investmentRepository->find($rand_id);
            $this->assertNotNull($investObj);

            //check for 0 payouts
            $this->assertCount(0, $investObj->getPayouts());

            //create an payout and store it
            /** @var Payout $payout1 */
            $payout1 = new Payout();

            $payout1->setPayoutAmount('2000');
            $payout1->setCreatedBy('Admin');

            $dueDate = new \DateTime('NOW');
            $dueDate->add(new \DateInterval('P5D'));
            $payout1->setDueDate($dueDate);

            $investObj->addPayout($payout1);

            $investmentRepository->save($investObj, true);

            /** @var Investment $newInvestObj */
            $newInvestObj = $investmentRepository->find($rand_id);

            $this->assertEquals(1, $newInvestObj->getPayouts()->count());

            return $newInvestObj;
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    //    public function testEditPayout()
    //    {
    //
    //        try {
    //
    //            $investmentRepository = $this->getRepository(Investment::class);
    //
    //            /** @var Investment $investObj */
    //            $investObj = $investmentRepository->findOneBy(array('name' => 'Master Test 1'));
    //            $this->assertNotNull($investObj);
    //
    //            //get the first payout and edit it
    //            /** @var Payout $payout1 */
    //            $payout1 = $investObj->getPayouts()->get(0);
    //
    //            /** @var Payout $payout2 */
    //            $payout2 = $investObj->getPayouts()->get(1);
    //
    //            /** @var Payout $payout3 */
    //            $payout3 = $investObj->getPayouts()->get(2);
    //
    //            //update payout 2
    //            $payout2->setPayoutAmount('2500');
    //            $payout2->setUpdatedBy('Bob');
    //            $payout2->setUpdatedAt(new \DateTime('NOW'));
    //
    //            $this->repository = $this->getRepository(Payout::class);
    //
    //            $this->repository->save($payout2);
    //
    //            //update payout 3
    //            $payout3->setPayoutAmount('3500');
    //            $payout3->setUpdatedBy('Sue');
    //            $payout3->setUpdatedAt(new \DateTime('NOW'));
    //
    //            $this->repository->save($payout3);
    //
    //            /** @var Investment $updateInvestObj */
    //            $updateInvestObj = $investmentRepository->findOneBy(array('name' => 'Master Test 1'));
    //            $this->assertNotNull($updateInvestObj);
    //
    //            //check that is has been updated correctly
    //            /** @var Payout $update_payout2 */
    //            $update_payout2 = $updateInvestObj->getPayouts()->get(1);
    //            $this->assertEquals(2500, $update_payout2->getPayoutAmount());
    //            $this->assertEquals('Bob', $update_payout2->getUpdatedBy());
    //
    //            //check that is has been updated correctly
    //            /** @var Payout $update_payout3 */
    //            $update_payout3 = $updateInvestObj->getPayouts()->get(2);
    //
    //            $this->assertEquals(3500, $update_payout3->getPayoutAmount());
    //            $this->assertEquals('Sue', $update_payout3->getUpdatedBy());
    //
    //        } catch (\Exception $e) {
    //            echo 'Caught exception: ', $e->getMessage(), "\n";
    //        }
    //
    //    }
    /**
     * Add a new payout and try and edit it
     */
    public function testAddUpdateSinglePayout(): void
    {
        //add a new payouy
        $invest = $this->testAddPayout();

        $this->assertNotNull($invest);

        //get the first payout and edit it
        /** @var Payout $payout1 */
        $payout1 = $invest->getPayouts()->get(0);

        //update payout 1
        $payout1->setPayoutAmount('2010');
        $payout1->setUpdatedBy('Bob');
        $payout1->setUpdatedAt(new \DateTime('NOW'));

        $this->repository->save($payout1, true);

        $investmentRepository = $this->entityManager->getRepository(Investment::class);

        /** @var Investment $updateInvestObj */
        $updateInvestObj = $investmentRepository->find($invest->getId());
        $this->assertNotNull($updateInvestObj);

        //check that is has been updated correctly
        /** @var Payout $updatePayout1 */
        $updatePayout1 = $updateInvestObj->getPayouts()->get(0);
        $this->assertEquals(2010, $updatePayout1->getPayoutAmount());
        $this->assertEquals('Bob', $updatePayout1->getUpdatedBy());

        //update payout 1
        $updatePayout1->setPayoutAmount('2020');
        $updatePayout1->setUpdatedBy('Julie');
        $updatePayout1->setUpdatedAt(new \DateTime('NOW'));

        $this->repository->save($payout1, true);

        /** @var Investment $updateInvestObj */
        $updateInvestObj = $investmentRepository->find($invest->getId());
        $this->assertNotNull($updateInvestObj);

        //check that is has been updated correctly
        /** @var Payout $updatePayout1 */
        $updatePayout1 = $updateInvestObj->getPayouts()->get(0);
        $this->assertEquals(2020, $updatePayout1->getPayoutAmount());
        $this->assertEquals('Julie', $updatePayout1->getUpdatedBy());
    }
}
