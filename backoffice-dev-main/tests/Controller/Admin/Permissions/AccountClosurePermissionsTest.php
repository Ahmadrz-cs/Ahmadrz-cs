<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\User;
use App\Test\PermissionsWebTestCase;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class AccountClosurePermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAdminProvider')]
    public function testAccountClosureRoutes(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'kycred.auto@test.yielderverse.co.uk'])
            ?->getId();
        $readPaths = [
            "/admin/users/{$user}/account-closure",
            "/admin/users/{$user}/account-closure/retention/none",
            "/admin/users/{$user}/account-closure/toggle-block",
            "/admin/users/{$user}/update-username",
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
