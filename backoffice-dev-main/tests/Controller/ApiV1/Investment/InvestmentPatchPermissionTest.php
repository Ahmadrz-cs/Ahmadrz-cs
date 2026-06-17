<?php

namespace App\Tests\Controller\ApiV1\Investment;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Investment;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentPatchPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateInvestmentOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(
            Investment::class,
            [
                'user' => $filter,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/investments/{$sample}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['name' => 'sample investment 1']);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateInvestmentOwn(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(
            Investment::class,
            [
                'user' => $filter,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/investments/{$sample}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['name' => 'sample investment 1']);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }
}
