<?php

namespace App\Tests\Repository;

use App\Repository\AssetDocumentRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class AssetDocumentRepositoryTest extends FixtureTestCase
{
    private AssetDocumentRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(AssetDocumentRepository::class);
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
        $expected = $this->service->findByWithAssociations([])->getNbResults();
        $actual = $this->service->findByWithAssociations(['abc' => 1, 'page' => 23]);
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
                if (in_array($key, ['documentTag'])) {
                    $key = lcfirst(substr($key, strlen('document')));
                    $relation = $object->getDocument();
                }
                if (in_array($key, ['assetId'])) {
                    $key = lcfirst(substr($key, strlen('asset')));
                    $relation = $object->getAsset();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (is_iterable($expected)) {
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
        yield 'Asset relation' => [['assetId' => '1']];
        yield 'Asset relation multi' => [['assetId' => [1, 2]]];
        yield 'Document relation' => [['documentTag' => 'read_to_activate']];
        yield 'Document relation multi' => [['documentTag' => [
            'read_to_activate',
            'logo',
        ]]];
    }
}
