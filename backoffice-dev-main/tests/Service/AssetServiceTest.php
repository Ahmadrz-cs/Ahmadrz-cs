<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\User;
use App\Service\AssetService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AssetServiceTest extends KernelTestCase
{
    private AssetService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssetService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sharePriceRangeGenerator')]
    public function testGenerateSharePriceRange(
        int $fundingGoal,
        ?int $min,
        ?int $max,
        $expected,
    ): void {
        $actual = $this->service->generateSharePriceRange($fundingGoal, $min, $max);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function sharePriceRangeGenerator(): \Generator
    {
        yield 'No limits' => [12856300, null, null, ['min' => 129, 'max' => 258]];
        yield 'Both limits' => [12856300, 100, 200, ['min' => 100, 'max' => 200]];
        yield 'Too low floor' => [12856300, 0, 200, ['min' => 129, 'max' => 200]];
        yield 'Too high cap' => [
            12856300,
            100,
            1000000,
            ['min' => 100, 'max' => sqrt(1285630000)],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sharePriceSuggestionGenerator')]
    public function testSuggestSharePrice(
        int $fundingGoal,
        int $min,
        int $max,
        $expected,
    ): void {
        $actual = $this->service->suggestSharePrice($fundingGoal, $min, $max);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function sharePriceSuggestionGenerator(): \Generator
    {
        // Prime when in pounds, note that the penny equivalent will never be prime!
        yield 'Prime number default limits' => [12856300, 129, 258, []];
        yield 'Prime number with lower limit' => [12856300, 100, 200, [100]];
        yield 'Divisible common limits' => [
            12586400,
            100,
            1000,
            [100, 160, 200, 400, 800],
        ];
        yield 'Divisible sub £1' => [12586400, 20, 100, [20, 25, 32, 40, 50, 80, 100]];
    }

    public function testapplyStatusChange(): void
    {
        $startDateTime = new \DateTime();
        $oldDateTime = new \DateTime('-7 days');

        $asset = new Asset();
        $this->assertEquals(AssetStatus::Draft, $asset->getCurrentStatus());
        $this->assertCount(0, $asset->getStatusLogs());
        $user = EntityIdTestUtil::setEntityId(new User(), 415);

        // Apply a status change
        $actual = $this->service->applyStatusChange(
            $asset,
            AssetStatus::Archived,
            'test status changes',
            $user,
            $oldDateTime,
        );
        // Should update the asset
        $this->assertEquals(AssetStatus::Archived, $asset->getCurrentStatus());
        // And create a status log
        $this->assertCount(1, $asset->getStatusLogs());
        $this->assertSame($actual, $asset->getStatusLogs()->first());
        $this->assertEquals(AssetStatus::Archived, $actual->getStatus());
        $this->assertEquals($user->getId(), $actual->getTransitionedBy()?->getId());
        $this->assertEquals('test status changes', $actual->getNotes());
        $this->assertEquals($oldDateTime, $actual->getOccuredAt());

        // Apply a second status change, but only provide the status
        $actual = $this->service->applyStatusChange($asset, AssetStatus::Active);
        $this->assertEquals(AssetStatus::Active, $asset->getCurrentStatus());
        $this->assertCount(2, $asset->getStatusLogs());
        $this->assertEquals(AssetStatus::Active, $actual->getStatus());
        $this->assertNull($actual->getTransitionedBy());
        $this->assertNull($actual->getNotes());
        $this->assertGreaterThanOrEqual($startDateTime, $actual->getOccuredAt());

        // Apply a 3rd status change where you provide nothing
        $actual = $this->service->applyStatusChange($asset);
        // Should reuse existing status
        $this->assertEquals(AssetStatus::Active, $asset->getCurrentStatus());
        // But still add a log
        $this->assertCount(3, $asset->getStatusLogs());
        $this->assertEquals(AssetStatus::Active, $actual->getStatus());
        $this->assertNull($actual->getTransitionedBy());
        $this->assertNull($actual->getNotes());
    }
}
