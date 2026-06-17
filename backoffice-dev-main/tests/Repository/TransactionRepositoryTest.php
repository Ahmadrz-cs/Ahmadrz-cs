<?php

namespace App\Tests\Repository;

use App\Repository\TransactionRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TransactionRepositoryTest extends FixtureTestCase
{
    private TransactionRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(TransactionRepository::class);
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
            $actual = EntityIdTestUtil::extractIds($this->service->findByWithAssociations([
            ]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = EntityIdTestUtil::extractIds($this->service->findByWithAssociations([
            ], ['id' => 'DESC']));
        rsort($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->service->findByWithAssociations([
            'credited_wallet_id' => 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V',
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'credited_wallet_id' => 'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V',
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
                $actual = $propertyAccessor->getValue($object, $key);
                if (in_array($key, ['comments'])) {
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
        yield 'Basic equivalence field' => [['id' => '12']];
        yield 'Multi equivalence field' => [['credited_wallet_id' => [
            '87743271',
            'wlt_m_01HW3DKQ6D3Y5Z2AM717NBZD6V',
        ]]];
        yield 'String match' => [['comments' => 'demo']];
        yield 'Combination 1' => [[
            'credited_wallet_id' => '87743271',
            'payment_status' => 'SUCCEEDED',
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
}
