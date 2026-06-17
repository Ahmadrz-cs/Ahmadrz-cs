<?php

namespace App\Tests\Controller\ApiV1\Payout;

use App\Entity\Payout;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PayoutGetErrorsTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetPayoutsPaginationInvalid(): void
    {
        // Check query parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $parameters = [
            'offset' => 'a',
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetPayoutsCriteriaInvalid(): void
    {
        // Check query parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $parameters = [
            'id' => implode('.', [1, 8, 16, 22]),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetPayoutsSortInvalid(): void
    {
        // Check sort parameter strict requirements
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/payouts';
        $parameters = [
            'sort' => implode(',', ['-id', '%name']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
