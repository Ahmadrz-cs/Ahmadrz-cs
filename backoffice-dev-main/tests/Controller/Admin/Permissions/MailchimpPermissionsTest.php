<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class MailchimpPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testMangopayReportPages(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/mailchimp/rejects',
            '/admin/mailchimp/rejects/delete/test@example.com',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
