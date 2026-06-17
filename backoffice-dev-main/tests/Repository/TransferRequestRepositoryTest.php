<?php

namespace App\Tests\Repository;

use App\Entity\AbstractOrder;
use App\Entity\TransferRequest;
use App\Repository\TransferRequestRepository;
use App\Service\MonthEndService;
use App\Test\FixtureTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TransferRequestRepositoryTest extends FixtureTestCase
{
    private TransferRequestRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(TransferRequestRepository::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->service->findByWithAssociations([], [], 4, 2);
        $this->assertEquals(2, $actual->getCurrentPage());
        $this->assertEquals(4, $actual->getMaxPerPage());
    }

    public function testFindByWithAssociationsOrdering(): void
    {
        // Check ordering by comparing actual with manually sorted
        // default ordering: id ascending
        $expected =
            $actual = $this->convertToIds($this->service->findByWithAssociations([]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = $this->convertToIds($this->service->findByWithAssociations([], [
                'id' => 'DESC',
            ]));
        rsort($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->service->findByWithAssociations([
            'status' => TransferRequest::STATE_COMPLETE,
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'status' => TransferRequest::STATE_COMPLETE,
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
                if (in_array($key, ['name'])) {
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
        yield 'Basic equivalence field' => [['id' => 3]];
        yield 'Status single' => [['status' => TransferRequest::STATE_COMPLETE]];
        yield 'Description single' => [[
            'description' => MonthEndService::DESCRIPTION_PRESETS['dividend'],
        ]];
        yield 'Status multi' => [['status' => [
            TransferRequest::STATE_PENDING,
            TransferRequest::STATE_COMPLETE,
        ]]];
        yield 'Combination 1' => [
            [
                'description' => MonthEndService::DESCRIPTION_PRESETS['management'],
                'status' => TransferRequest::STATE_COMPLETE,
            ],
        ];
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
                if (in_array($fieldName, ['scheduledFor'])) {
                    $relation = $object->getTransferOrder();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $fieldName);
                // $actual = $propertyAccessor->getValue($object, $fieldName);
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
        yield 'ScheduledFor Start' => [
            'filters' => [
                'scheduledFor_gte' => new \DateTime('-4 months'),
            ],
            'fieldChecks' => [
                'scheduledFor' => [
                    'start' => new \DateTime('-4 months')->setTime(0, 0),
                ],
            ],
        ];
        yield 'ScheduledFor End' => [
            'filters' => [
                'scheduledFor_lt' => new \DateTime('-2 months'),
            ],

            'fieldChecks' => [
                'scheduledFor' => [
                    'end' => new \DateTime('-2 months')->setTime(0, 0),
                ],
            ],
        ];
        yield 'ScheduledFor Range' => [
            'filters' => [
                'scheduledFor_gte' => new \DateTime('-4 months'),
                'scheduledFor_lt' => new \DateTime('-1 months'),
            ],
            'fieldChecks' => [
                'scheduledFor' => [
                    'start' => new \DateTime('-4 months')->setTime(0, 0),
                    'end' => new \DateTime('-1 months')->setTime(0, 0),
                ],
            ],
        ];
    }
}
