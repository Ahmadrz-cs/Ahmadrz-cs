<?php

namespace App\Tests\Repository;

use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Repository\BankAccountRepository;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Component\PropertyAccess\PropertyAccess;

class BankAccountRepositoryTest extends FixtureTestCase
{
    private BankAccountRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(BankAccount::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->repository->findByWithAssociations([], [], 2, 2);
        $this->assertEquals(2, $actual->getCurrentPage());
        $this->assertEquals(2, $actual->getMaxPerPage());
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
        if ($results->count() == 0) {
            $this->fail('No fixtures found for criteria');
        }
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                if (in_array($key, ['userId'])) {
                    $key = lcfirst(substr($key, 4));
                    $relation = $object->getUser();
                }
                if ('username' == $key) {
                    $relation = $object->getUser();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['username', 'providerId'])) {
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
        yield 'Basic equivalence field' => [['id' => '2']];
        yield 'Status relation single' => [['status' => BankAccountStatus::Pending]];
        yield 'User relation single' => [['userId' => 1]];
        yield 'Status relation multi' => [['status' => [
            BankAccountStatus::Pending,
            BankAccountStatus::Active,
        ]]];
        yield 'Account type single' => [['accountType' => BankAccountType::IBAN]];
        yield 'Account holder type single' => [[
            'accountHolderType' => BankAccountHolderType::Personal,
        ]];
        yield 'Provider Id field' => [['providerId' => 'bankacc']];
        yield 'User string match' => [['username' => 'ben.auto']];
    }
}
