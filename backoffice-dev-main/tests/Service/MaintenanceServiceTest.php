<?php

namespace App\Tests\Service;

use App\Service\MaintenanceService;
use App\Test\FixtureTestCase;

final class MaintenanceServiceTest extends FixtureTestCase
{
    private MaintenanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MaintenanceService::class);
    }

    public function testGetOAuth2ArtifactSummary(): void
    {
        // Mainly checking the shape of the data structure returned
        $actual = $this->service->getOAuth2ArtifactSummary();
        $this->assertEquals(
            ['accessTokens', 'refreshTokens', 'authCodes'],
            array_keys($actual),
        );
        foreach ($actual as $stats) {
            $this->assertEquals(['total', 'expired'], array_keys($stats));
        }
    }

    public function testClearExpiredOAuth2Artifacts(): void
    {
        $summary = $this->service->getOAuth2ArtifactSummary();
        $expected = [
            'accessTokens' => $summary['accessTokens']['expired'],
            'refreshTokens' => $summary['refreshTokens']['expired'],
            'authCodes' => $summary['authCodes']['expired'],
        ];
        $actual = $this->service->clearExpiredOAuth2Artifacts();
        $this->assertEquals($expected, $actual);
    }
}
