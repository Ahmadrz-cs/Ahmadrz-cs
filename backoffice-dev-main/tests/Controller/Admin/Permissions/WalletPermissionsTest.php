<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\User;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class WalletPermissionsTest extends PermissionsWebTestCase
{
    // Comment out to reduce Mangopay API call load during tests
    // #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    // public function testWalletRead(string $user, int $expected): void
    // {
    //     $this->loginWebClient($user);
    //     $this->client->followRedirects();
    //     /** @var User $user */
    //     $user = $this->searchFixtures(User::class, ['username' => self::USER_REGULAR])[0];
    //     $sampleWalletId = $user->getMangopayWalletId();
    //     $readPaths = [
    //         "/admin/wallets/{$sampleWalletId}/transactions",
    //     ];
    //     foreach ($readPaths as $path) {
    //         $this->client->request('GET', $path);
    //         $this->assertResponseStatusCodeSame($expected);
    //     }
    // }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testWalletUpdateAndDetails(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        /** @var User $user */
        $user = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];
        $sampleWalletId = $user->getMangopayWalletId();
        $readPaths = [
            "/admin/wallets/{$sampleWalletId}",
            "/admin/users/{$user->getId()}/wallets/create-all",
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
