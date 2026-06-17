<?php

namespace App\Tests\Controller\ApiV1\Report;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ReportGetPermissionTest extends FixtureWebTestCase
{
    public function testGetReportOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $parameters = [
            'userid' => $sample,
            'report_type' => 'test_report',
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/reports/getUserReport';
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('success', $apiResponse['outcome']);
        $this->assertEquals('200', $apiResponse['status']);
    }

    public function testGetReportOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_VIP],
            true,
        )[0];
        $parameters = [
            'userid' => $sample,
            'report_type' => 'test_report',
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/reports/getUserReport';
        $this->client->request('GET', $uri, $parameters);

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $apiResponse['status']); // bit of a weird response code
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    public function testGetUserAccountReportPublicUser(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUserAccountReportAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetUserAccountReportAsAdmin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetOtherUserAccountReportAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_VIP],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/account/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUserAssetsReportAsPublicUser(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_VIP],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetUserAssetsReportAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetUserAssetsReportAsAdminUser(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_REGULAR],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetOtherUserAssetsReportAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_VIP],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/reports/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
