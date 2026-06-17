<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class ApiClientPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testApiClientRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/administration/clients',
            '/admin/administration/clients/' . self::OAUTH2_CLIENT_VENDOR['clientId'],
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testApiClientCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/administration/clients/add');
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testApiClientDelete(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $this->client->request(
            'GET',
            '/admin/administration/clients/'
            . self::OAUTH2_CLIENT_VENDOR['clientId']
            . '/delete',
        );
        $this->assertResponseStatusCodeSame($expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testApiClientUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $crawler = $this->client->request(
            'GET',
            '/admin/administration/clients/' . self::OAUTH2_CLIENT_VENDOR['clientId'],
        );
        $form = $crawler->filter('form')->form();

        // Check whether all form fields are disabled
        $formValues = $form->getValues();
        $expectedCount = $expected == Response::HTTP_FORBIDDEN ? 0 : 1;
        $this->assertGreaterThanOrEqual($expectedCount, count($formValues));
    }
}
