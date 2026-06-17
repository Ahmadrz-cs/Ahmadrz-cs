<?php

namespace App\Tests\Service;

use App\Service\MangopayWalletService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayWalletServiceTest extends KernelTestCase
{
    private MangopayWalletService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(MangopayWalletService::class);
    }

    public function testEmptyRateLimits(): void
    {
        $this->assertSame([], $this->service->getRateLimits());
    }
}
