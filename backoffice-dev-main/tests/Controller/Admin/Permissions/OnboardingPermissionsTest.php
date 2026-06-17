<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class OnboardingPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testOnboardingHub(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/onboarding',
            '/admin/onboarding/categorisations',
            '/admin/onboarding/assessments',
            '/admin/onboarding/1',
            '/admin/onboarding/assessments/set/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testOnboardingEdits(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/onboarding/1/add-categorisation',
            '/admin/onboarding/1/add-assessment',
            // '/admin/onboarding/categorisations/1',
            // '/admin/onboarding/assessments/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
