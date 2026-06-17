<?php

namespace App\Tests\Controller\ApiV1\Investment;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetInvestmentPayoutsNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments/999999/payouts';
        $this->client->request('GET', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // 422 code (which is arguably the wrong one...but APIv1 is legacy at this point)
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INVESTMENT_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetInvestmentNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments/999999';
        $this->client->request('GET', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // 422 code (which is arguably the wrong one...but APIv1 is legacy at this point)
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INVESTMENT_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetInvestmentsPaginationInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $parameters = [
            'offset' => 'a',
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetInvestmentsCriteriaInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $parameters = [
            'id' => implode('.', [1, 8, 16, 22]),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetInvestmentsSortInvalid(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/investments';
        $parameters = [
            'sort' => implode(',', ['-id', '%name']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
