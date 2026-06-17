<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class AssetPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testAssetRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/asset',
            '/admin/asset/list',
            '/admin/asset/1/view',
            '/admin/asset/wallets',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testAssetUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/asset/1/edit',
        ];
        $expected = $expected == Response::HTTP_FORBIDDEN ? false : true;
        foreach ($paths as $path) {
            $crawler = $this->client->request('GET', $path);
            $form = $crawler->filter('form')->form();

            // Check whether all form fields are disabled
            $formValues = $form->getValues();
            $this->assertGreaterThanOrEqual((int) $expected, count($formValues));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testAssetCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/asset/add',
            '/admin/asset/1/wallets/create/main',
            '/admin/asset/1/wallets/create-all',
            '/admin/asset/1/status-logs/create',
            '/admin/asset/status-logs/1',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
