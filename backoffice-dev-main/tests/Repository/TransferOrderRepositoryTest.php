<?php

namespace App\Tests\Repository;

use App\Entity\AbstractOrder;
use App\Entity\Asset;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\TransferOrder;
use App\Repository\TransferOrderRepository;
use App\Test\FixtureTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class TransferOrderRepositoryTest extends FixtureTestCase
{
    private TransferOrderRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(TransferOrderRepository::class);
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
            'status' => AbstractOrder::STATE_DRAFT,
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'status' => AbstractOrder::STATE_DRAFT,
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
                if (in_array($key, ['assetId', 'assetName'])) {
                    $key = lcfirst(substr($key, 5));
                    $relation = $object->getAsset();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
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
        yield 'Status single' => [['status' => TransferOrder::STATE_APPROVED]];
        yield 'Asset relation single' => [['assetName' => 'cam']];
        yield 'Asset relation multi' => [['assetId' => [1, 2, 11, 13]]];
        yield 'Combination 1' => [[
            'assetName' => 'sea',
            'status' => TransferOrder::STATE_APPROVED,
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
                'createdAt_gte' => new \DateTime('-4 months'),
            ],
            'fieldChecks' => [
                'createdAt' => [
                    'start' => new \DateTime('-4 months')->setTime(0, 0),
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

    public function testFindForCurrentMonthend(): void
    {
        /** @var Asset $assetWithoutDivestment */
        $assetWithoutDivestment = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Sagittarius Eystar - Horizon']);
        $actual = $this->service->findForCurrentMonthend(
            $assetWithoutDivestment,
            TransferOrderPreset::IncomeTransfer,
        );
        $this->assertNull($actual);

        /** @var Asset $assetWithDividend */
        $assetWithDividend = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Eversea Glades - Cambridge']);

        $actual = $this->service->findForCurrentMonthend(
            $assetWithDividend,
            TransferOrderPreset::IncomeTransfer,
        );
        $this->assertNotNull($actual);
        $this->assertEquals(
            TransferOrderPreset::IncomeTransfer->value,
            $actual->getDescription(),
        );
        $this->assertEquals($assetWithDividend->getId(), $actual->getAsset()->getId());
        $this->assertEquals(date('Y-m'), $actual->getScheduledFor()->format('Y-m'));
    }
}
