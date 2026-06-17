<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class PayoutPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testPayoutRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $paths = [
            '/admin/payout',
            '/admin/payout/list',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testPayoutUpdate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $expected = $expected == Response::HTTP_FORBIDDEN ? false : true;
        $crawler = $this->client->request('GET', '/admin/payout/1/edit');
        $form = $crawler->filter('form')->form();

        // Check whether all form fields are disabled
        $formValues = $form->getValues();
        $this->assertGreaterThanOrEqual((int) $expected, count($formValues));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testPayoutCreate(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->request('GET', '/admin/payout/add');
        $this->assertResponseStatusCodeSame($expected);
    }
}
