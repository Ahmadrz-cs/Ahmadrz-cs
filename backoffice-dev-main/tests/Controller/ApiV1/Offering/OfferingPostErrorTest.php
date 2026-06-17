<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingPostErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateOfferingInvestmentFieldsMissingAll(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings/1/investments';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_MISSING_REQUEST_DATA];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateOfferingInvestmentOfferingNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings/100001/investments';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 2000,
            'name' > 'new investment',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_OFFERING_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateOfferingInvestmentAmountMissing(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings/1/investments';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['offering_id' => 1]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INVESTMENT_MISSING_AMOUNT];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateOfferingInvestmentLimitExceeded(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        /** @var Offering $sample */
        $sample = $this->searchFixtures(Offering::class, [
            'name' => 'Lodge de Lac - Cumbria',
        ])[0];
        $investmentLimit = $sample->getMaxCommitUser();
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/{$sample->getId()}/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => $investmentLimit + 1,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INVESTMENT_VALUE_LIMIT];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
