<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutUpdateResponseTest extends FixtureWebTestCase
{
    public function testUpdatePayout(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
        // $actualFields = array_keys($apiResponse);
        // $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testUpdatePayoutFieldset(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
    //     $date = new DateTime('first of this month');
    //     $fieldset = [
    //         'type' => 'profit share',
    //         'amount' => 21.87,
    //         'dueDate' => $date->format(DateTime::ATOM),
    //     ];
    //     $content = json_encode([$fieldset]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     foreach ($fieldset as $key => $expected) {
    //         $this->assertEquals($expected, $apiResponse[$key]);
    //     }
    // }
}
