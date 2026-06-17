<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutCreateResponseTest extends FixtureWebTestCase
{
    public function testCreatePayout(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'investmentId' => $sample[0],
            'amount' => 218.70,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
        // $actualFields = array_keys($apiResponse);
        // $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testCreatePayoutFieldTypeProfitShare(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled'],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts';
    //     $date = new DateTime('first of this month');
    //     $content = json_encode([
    //         'investmentId' => $sample[0],
    //         'amount' => 218.70,
    //         'dueDate' => $date->format(DateTime::ATOM),
    //         'type' => 'profit share'
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
}
