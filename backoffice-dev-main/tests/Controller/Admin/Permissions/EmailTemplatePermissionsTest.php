<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class EmailTemplatePermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minTechopsProvider')]
    public function testEmailTemplateActions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/email-templates/legacy',
            '/admin/email-templates/legacy/create',
            '/admin/email-templates/legacy/1',
            '/admin/email-templates/legacy/1/test',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
