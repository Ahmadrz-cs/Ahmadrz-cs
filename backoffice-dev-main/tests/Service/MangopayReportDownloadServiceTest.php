<?php

namespace App\Tests\Service;

use App\Service\MangopayReportDownloadService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayReportDownloadServiceTest extends KernelTestCase
{
    private MangopayReportDownloadService $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(MangopayReportDownloadService::class);
    }

    public function testDownloadFromUrlToStringUnsupportedUrl(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches(
            '/Unsupported CSV source. Only the following hosts are supported:.*/',
        );
        $this->service->downloadFromUrlToString('https://example.com/');
    }

    public function testDownloadFromUrlToStringResponseError(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Error downloading from url:.*/');
        $this->service->downloadFromUrlToString(
            'https://downloads.sandbox.mangopay.com/abc',
        );
    }
}
