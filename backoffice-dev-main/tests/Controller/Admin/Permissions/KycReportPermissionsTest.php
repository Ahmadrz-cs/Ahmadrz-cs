<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class KycReportPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testKycReportRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/kyc-reports',
            '/admin/kyc-reports/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testKycReportTooling(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/kyc-reports/review-check/mangopay',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minSuperAdminProvider')]
    public function testKycReportCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/kyc-reports/create');
        $this->assertResponseStatusCodeSame($expected);
    }
}
