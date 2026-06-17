<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service;

use AppBundle\Entity\UserCustomInfo as UserInfo;
use ClientBundle\Service\CrowdTekService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CrowdtekServiceTest extends KernelTestCase
{
    /**
     * @var CrowdTekService
     */
    private $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(CrowdTekService::class);
    }

    /**
     * @return array
     */
    public function gdprOptionsProvider()
    {
        $gdprAccepted = [
            'contact_via_email' => 1,
            'contact_via_tele' => 1,
            'contact_via_sms' => 1
        ];
        $gdprEmailOnly = [
            'contact_via_email' => 1,
            'contact_via_tele' => 0,
            'contact_via_sms' => 0
        ];
        $gdprEmailSmsOnly = [
            'contact_via_email' => 1,
            'contact_via_tele' => 0,
            'contact_via_sms' => 1
        ];
        $gdprEmailTeleOnly = [
            'contact_via_email' => 1,
            'contact_via_tele' => 1,
            'contact_via_sms' => 0
        ];
        $gdprTeleOnly = [
            'contact_via_email' => 0,
            'contact_via_tele' => 1,
            'contact_via_sms' => 0
        ];
        $gdprSmsOnly = [
            'contact_via_email' => 0,
            'contact_via_tele' => 0,
            'contact_via_sms' => 1
        ];
        $gdprSmsTeleOnly = [
            'contact_via_email' => 0,
            'contact_via_tele' => 1,
            'contact_via_sms' => 1
        ];
        $gdprRejected = [
            'contact_via_email' => 0,
            'contact_via_tele' => 0,
            'contact_via_sms' => 0
        ];

        return [
            "gdprAccepted" => [$gdprAccepted, '1'],
            "gdprEmailOnly" => [$gdprEmailOnly, '1'],
            "gdprEmailSmsOnly" => [$gdprEmailSmsOnly, '1'],
            "gdprEmailTeleOnly" => [$gdprEmailTeleOnly, '1'],
            "gdprTeleOnly" => [$gdprTeleOnly, '0'],
            "gdprSmsOnly" => [$gdprSmsOnly, '0'],
            "gdprSmsTeleOnly" => [$gdprSmsTeleOnly, '0'],
            "gdprRejected" => [$gdprRejected, '0']
        ];
    }

    /**
     * @dataProvider gdprOptionsProvider
     */
    public function testGenerateGdprUpdateBody($gdprData, $expected): void
    {
        $userData = new UserInfo('testUserInfo');
        $userData->setContactViaEmail($gdprData['contact_via_email']);
        $userData->setContactViaTele($gdprData['contact_via_tele']);
        $userData->setContactViaSms($gdprData['contact_via_sms']);

        $result = $this->service->generateGdprUpdateBody($userData);

        $this->assertEquals($expected, $result['gdpr_accepted']);
    }
}
