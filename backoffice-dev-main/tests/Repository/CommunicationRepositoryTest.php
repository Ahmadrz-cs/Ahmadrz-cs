<?php

namespace App\Tests\Repository;

use App\Repository\CommunicationRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class CommunicationRepositoryTest extends FixtureTestCase
{
    private CommunicationRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(CommunicationRepository::class);
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
            'type' => 'prefunding',
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
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
        $results = $this->service->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['subject'])) {
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
        yield 'Basic equivalence field' => [['id' => 12]];
        yield 'Status field' => [['status' => 0]];
        yield 'Subject string match' => [['subject' => '[Admin]']];
        yield 'Combination 1' => [['subject' => 'Logi', 'status' => 0]];
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

    public function testSizeBySubject(): void
    {
        $expected = $this->service->count([]);
        // Primarily sanity checks rather than full correctness checks

        // Check that all emails are accounted for
        $actualDefault = $this->service->sizeBySubject();
        $this->assertSame($expected, array_sum(array_column($actualDefault, 'count')));
        // Check grouping working properly
        $this->assertSame(
            array_unique(array_column($actualDefault, 'subject')),
            array_column($actualDefault, 'subject'),
        );

        // Check year group emails all accounted for
        $actualYearGrouped = $this->service->sizeBySubject(null, true);
        $this->assertSame(
            $expected,
            array_sum(array_column($actualYearGrouped, 'count')),
        );
        // Check year grouping works properly
        $yearSubjects = [];
        foreach ($actualYearGrouped as $entry) {
            $yearSubjects[] = $entry['year'] . $entry['subject'];
        }
        $this->assertSame(array_unique($yearSubjects), $yearSubjects);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('endDateProvider')]
    public function testGetSafeEndDate(?\DateTimeInterface $endDate, bool $isSafe): void
    {
        $referenceSafeDate = new \DateTime('-1 month');
        $actual = $this->service->getSafeEndDate($endDate);
        if ($isSafe) {
            $this->assertEquals($endDate, $actual);
        } else {
            $this->assertGreaterThanOrEqual($referenceSafeDate, $actual);
        }
    }

    public static function endDateProvider(): \Generator
    {
        $justOver1Month = new \DateTime('-1 year');
        $justOver1Month->modify('-1 minute');
        yield 'Null' => [null, false];
        yield 'Now' => [new \DateTime(), false];
        yield '27 days' => [new \DateTime('-27 days'), false];
        yield '1 month' => [$justOver1Month, true];
        yield '6 months ago' => [new \DateTime('-6 months'), true];
        yield '12 months ago' => [new \DateTime('-12 months'), true];
        yield '18 months ago' => [new \DateTime('-18 months'), true];
    }

    public function testDeleteBySubject(): void
    {
        $emailsBySubject = $this->service->sizeBySubject();
        $topEntry = $emailsBySubject[0];
        $dateEnd = new \DateTime('-14 month');
        $matches = count($this->service->buildQueryWithAssociations([
            'subject' => $topEntry['subject'],
            'createdAt_lt' => $dateEnd,
        ])->getResult());
        $actual = $this->service->deleteBySubject($topEntry['subject'], $dateEnd);
        $this->assertNotEmpty($actual);
        $this->assertEquals($matches, $actual);

        // Check that the per subject report has also updated
        $emailsBySubject = $this->service->sizeBySubject();
        $emailsBySubject = array_combine(
            array_column($emailsBySubject, 'subject'),
            array_column($emailsBySubject, 'count'),
        );
        $this->assertEquals(
            $matches,
            $topEntry['count'] - $emailsBySubject[$topEntry['subject']],
        );
    }

    public function testDeleteBySubjectUnsafeDate(): void
    {
        // Delete action will prevent unsafe end date and auto set a safe one
        $emailsBySubject = $this->service->sizeBySubject();
        $topEntry = $emailsBySubject[0];
        $dateEnd = new \DateTime('-1 month');
        $matches = count($this->service->buildQueryWithAssociations([
            'subject' => $topEntry['subject'],
            'createdAt_lt' => $dateEnd,
        ])->getResult());
        $actual = $this->service->deleteBySubject(
            $topEntry['subject'],
            new \DateTime('-10 days'),
        );
        $this->assertNotEmpty($actual);
        $this->assertEquals($matches, $actual);
    }

    public function testDeleteBySubjectPartialSubject(): void
    {
        // Unlike the query builder, the subject is an exact match, not a fuzzy string match
        $emailsBySubject = $this->service->sizeBySubject();
        $topEntry = $emailsBySubject[0];
        $actual = $this->service->deleteBySubject(substr($topEntry['subject'], 2, 5));
        $this->assertEmpty($actual);
    }
}
