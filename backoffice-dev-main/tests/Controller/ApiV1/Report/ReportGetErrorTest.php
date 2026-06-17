<?php

namespace App\Tests\Controller\ApiV1\Report;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ReportGetErrorTest extends FixtureWebTestCase
{
    public static function reportParametersProvider(): \Generator
    {
        yield 'Empty' => [[]];
        yield 'Missing report type' => [['userid' => 1, 'report_type' => '']];
        yield 'Missing report type key' => [['userid' => 1]];
        yield 'Missing userId' => [['report_type' => '']];
        yield 'Unsupported report type' => [[
            'userid' => 1,
            'report_type' => 'supercali',
        ]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reportParametersProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetReportFieldsMissingAsAdmin(array $parameters): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/reports/getUserReport';
        $this->client->request('GET', $uri, $parameters);

        // Has the unusual response code system again
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_PARAMS];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
