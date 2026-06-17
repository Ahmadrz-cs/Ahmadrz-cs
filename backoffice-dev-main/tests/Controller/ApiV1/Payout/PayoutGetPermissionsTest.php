<?php

namespace App\Tests\Controller\ApiV1\Payout;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PayoutGetPermissionsTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPayoutsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetPayoutsRegUser(): void
    {
        // Check regular users cannot use this route
        // Regular users should retrieve their payouts via their user/self routes
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
