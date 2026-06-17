<?php

namespace App\Tests\Service\Manager;

use App\Entity\Asset;
use App\Entity\BaseEntity;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Service\Manager\OfferingManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OfferingManagerTest extends KernelTestCase
{
    private OfferingManager $service;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(OfferingManager::class);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
    }

    private $testQueryParams = [
        'offest' => '0',
        'limit' => '1',
        'sort' => '+id,-updatedAt',
        'id' => '1,3,4',
        'status' => '4,5',
        'type' => 'offering',
        'term' => '1,3,5',
        'biscuits' => 'digestive,shortbread',
        'user' => 'whodat,whatder',
    ];

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetCriteria(): void
    {
        $expected = ['id', 'offeringType', 'offeringTerm'];

        // only return allowed criteria
        $actual = $this->service->getCriteria($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass empty queryParams
        $actual = $this->service->getCriteria([]);
        $this->assertEmpty($actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetAuxiliaryFilters(): void
    {
        $expected = ['status'];

        // only return allowed auxiliary filters
        $actual = $this->service->getAuxiliaryFilters($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass default status null as admin - may want to improve it to handle empty arrays
        $actual = $this->service->getAuxiliaryFilters(['status' => null], true);
        $this->assertEmpty($actual);

        // pass default status null as non-admin
        $actual = $this->service->getAuxiliaryFilters(['status' => null]);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));
        $this->assertEquals(3, count($actual['status']));
        $this->assertEmpty(array_diff(
            [
                OfferingLifecycle::STATE_PUBLISHED_INT,
                OfferingLifecycle::STATE_SETTELED_INT,
                OfferingLifecycle::STATE_CLOSED_INT,
            ],
            $actual['status'],
        ));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minCommitProvider')]
    public function testValidateMinCommit(
        string $assetSharePrice,
        string $offeringSharePrice,
        float $rawMinCommit,
        float $expected,
    ): void {
        $asset = new \App\Entity\Asset();
        $asset->setPricePerShare($assetSharePrice);
        $offering = new \App\Entity\Offering();
        $offering->setPricePerShare($offeringSharePrice);
        $offering->setAsset($asset);

        $offering->setMinCommitUser($rawMinCommit);
        // If the min commit changes, should return false (i.e. it was not a valid min commit)
        // Otherwise should return true, because min commit did not need to change
        $this->assertEquals(
            $rawMinCommit === $expected,
            $this->service->validateMinCommit($offering),
        );
        $this->assertEquals($expected, $offering->getMinCommitUser());
    }

    public static function minCommitProvider(): \Generator
    {
        yield 'No share prices at all' => ['0', '0', 100, 100];
        yield 'No share prices with decimals' => ['0.00', '0.00', 100, 100];
        yield 'No offering share price, use asset as fallback' => [
            '1.28',
            '0',
            100,
            101.12,
        ];
        yield 'No offering share price, use asset as fallback with decimals' => [
            '1.28',
            '0.00',
            100,
            101.12,
        ];
        yield 'No asset share price, offering takes priority' => [
            '0.00',
            '1.28',
            100,
            101.12,
        ];
        yield 'Standard 100 boundary' => ['1.28', '1.28', 100, 101.12];
        yield 'Minimum single share' => ['1.28', '1.28', 1, 1.28];
        yield 'Exact amount' => ['1.28', '1.28', 81.92, 81.92];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringModeProvider')]
    public function testSwitchOfferingMode(
        string $mode,
        string $type,
        int $visibility,
    ): void {
        $asset = new Asset();
        $asset->setName('test');
        $offering = new Offering();
        $offering->setAsset($asset);
        $offering->setName('test');
        $this->em->persist($asset);

        $this->service->switchOfferingMode($offering, $mode);

        $this->assertEquals($visibility, $offering->getVisibility());
        $this->assertEquals($type, $offering->getOfferingType());
    }

    public static function offeringModeProvider(): \Generator
    {
        yield 'Retail mode' => ['retail', 'retail', BaseEntity::VISIBILITY_AUTO];
        yield 'Prefunding mode' => [
            'prefunding',
            'prefunding',
            BaseEntity::VISIBILITY_VIP,
        ];
        yield 'Unknown mode' => ['random', 'retail', BaseEntity::VISIBILITY_AUTO];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringExternalCommitsProvider')]
    public function testAggregateExternalCommits(
        float $expectedMonetary,
        int $expectedShares,
        array $offerings,
    ): void {
        $actual = $this->service->aggregateExternalCommits($offerings);
        if (empty($offerings)) {
            $this->assertEmpty($actual);
        } else {
            $assetIdFirst = array_key_first($actual);
            $this->assertEquals(8, $assetIdFirst);
            $this->assertEquals($expectedMonetary, $actual[$assetIdFirst]['monetary']);
            $this->assertEquals($expectedShares, $actual[$assetIdFirst]['shares']);
        }
    }

    public static function offeringExternalCommitsProvider(): \Generator
    {
        $asset = new Asset();
        $asset->setName('test');
        $asset->setPricePerShare(1.27);
        $reflection = new \ReflectionClass($asset);
        $reflectionProperty = $reflection->getProperty('id');
        $reflectionProperty->setValue($asset, 8);

        $offeringA = new Offering();
        $offeringA->setAsset($asset);
        $offeringA->setName('test');
        $offeringA->setExternalCommitments(100.33); // eqv shares 79

        $offeringB = new Offering();
        $offeringB->setAsset($asset);
        $offeringB->setName('test2');
        $offeringB->setPricePerShare('0.00'); // check division by 0 bypass empty() method
        $offeringB->setExternalCommitments(471.17); // eqv shares 371

        yield 'Empty' => [0, 0, []];
        yield 'One offering' => [471.17, 371, [$offeringB]];
        yield 'Multiple offerings' => [
            471.17 + 100.33,
            371 + 79,
            [$offeringA, $offeringB],
        ];
    }
}
