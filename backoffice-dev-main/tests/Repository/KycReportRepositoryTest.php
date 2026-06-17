<?php

namespace App\Tests\Repository;

use App\Repository\KycReportRepository;
use App\Test\FixtureTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class KycReportRepositoryTest extends FixtureTestCase
{
    private KycReportRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(KycReportRepository::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->service->findByWithAssociations([], [], 2, 1);
        $this->assertEquals(1, $actual->getCurrentPage());
        $this->assertEquals(2, $actual->getMaxPerPage());
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
            'decision' => 1,
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'decision' => 1,
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
                if (in_array($key, ['subjectUsername'])) {
                    $key = lcfirst(substr($key, 7));
                    $relation = $object->subject;
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['username'])) {
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
        yield 'Verification single' => [['verified' => 0]];
        yield 'User relation single' => [['subjectUsername' => self::USER_REGULAR]];
        yield 'Combination 1' => [[
            'subjectUsername' => self::USER_REGULAR,
            'verified' => 0,
        ]];
    }
}
