<?php

namespace App\Tests\Controller\ApiV1\Investment;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetInvestmentPayoutsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/investments/1/payouts';
        $this->client->request('GET', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // 422 code (which is arguably the wrong one...but APIv1 is legacy at this point)
        $this->assertEquals(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $apiResponse['status'],
        );
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetInvestmentsAsPublic(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetInvestmentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
