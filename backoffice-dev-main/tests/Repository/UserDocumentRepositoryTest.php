<?php

namespace App\Tests\Repository;

use App\Repository\UserDocumentRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class UserDocumentRepositoryTest extends FixtureTestCase
{
    private UserDocumentRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserDocumentRepository::class);
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
                if (in_array($key, ['userId'])) {
                    $key = lcfirst(substr($key, strlen('user')));
                    $relation = $object->getUser();
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
        yield 'POI Document relation' => [['documentTag' => 'proof_of_identity']];
        yield 'POI Document relation multi' => [['documentTag' => [
            'proof_of_address',
            'proof_of_identity',
        ]]];
        yield 'User relation' => [['userId' => '2']];
        yield 'User relation multi' => [['userId' => [1, 2]]];
    }
}
