<?php

namespace App\Tests\Controller\ApiV2\Me;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class MeGetResponseTest extends FixtureWebTestCase
{
    public function testGetCurrentUser(): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $expected = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => self::USER_VIP],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V2 . '/me';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals($expected, $apiResponse['id']);
    }
}
