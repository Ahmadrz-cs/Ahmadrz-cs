<?php

namespace App\Tests\Service;

use App\Entity\AppSetting;
use App\Service\AppSettingService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AppSettingServiceTest extends KernelTestCase
{
    private AppSettingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AppSettingService::class);
    }

    public function testGetNotFound(): void
    {
        $this->assertNull($this->service->get('non-existent-setting'));
    }

    public function testGetNotFoundUseDefault(): void
    {
        $default = bin2hex(random_bytes(8));
        $this->assertEquals($default, $this->service->get(
            'non-existent-setting',
            $default,
        ));
    }

    public function testConvertToKv(): void
    {
        $appSetting1 = new AppSetting('exampleTestSetting', 'exampleSettingValue');
        $appSetting2 = new AppSetting('altTestSetting', 'altSettingValue');
        $expected = [
            'exampleTestSetting' => 'exampleSettingValue',
            'altTestSetting' => 'altSettingValue',
        ];
        $this->assertEquals(
            $expected,
            $this->service->convertToKv([$appSetting1, $appSetting2]),
        );
    }

    public function testSetup(): void
    {
        $current = $this->service->getAll();
        $possible = [
            'yieldersFeeWallet',
            'ypmlFeeWallet',
            'orderIssueLimit',
        ];
        $actual = $this->service->setup();
        // Need to reset keys with array_values due to https://github.com/sebastianbergmann/comparator/issues/112
        $this->assertEqualsCanonicalizing(
            array_values(array_diff($possible, array_keys($current))),
            $actual,
        );
    }

    public function testSetMultipleAndGetAll(): void
    {
        $this->service->setup();
        $expected = [
            'yieldersFeeWallet' => bin2hex(random_bytes(8)),
            'ypmlFeeWallet' => bin2hex(random_bytes(8)),
            'orderIssueLimit' => bin2hex(random_bytes(8)),
        ];
        $returned = $this->service->setMultiple($expected);
        $actual = $this->service->getAll();
        $this->assertEqualsCanonicalizing($expected, $returned);
        $this->assertEqualsCanonicalizing($returned, $actual);

        // Get single should also work as expected
        $actual = $this->service->get('yieldersFeeWallet');
        $this->assertSame($expected['yieldersFeeWallet'], $actual);

        // Get multiple should also work as expected
        $actual = $this->service->getMultiple(['yieldersFeeWallet']);
        $this->assertSame(
            ['yieldersFeeWallet' => $expected['yieldersFeeWallet']],
            $actual,
        );
    }
}
