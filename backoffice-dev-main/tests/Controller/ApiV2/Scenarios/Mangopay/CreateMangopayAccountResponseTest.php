<?php

namespace App\Tests\Controller\ApiV2\Scenarios\Mangopay;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateMangopayAccountResponseTest extends FixtureWebTestCase
{
    public function testScenarioMpAccountCreatedOnUserCreation(): void
    {
        $this->markTestSkipped('APIv2 user KYC deprecated');

        //create new user that meets mangopay user creation critria
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe50@test.com',
            'firstName' => 'John',
            'lastName' => 'doe',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'address' => [
                'address1' => '18 Victoria Road',
                'city' => 'Oxford',
                'postCode' => 'OX54 9HZ',
                'country' => 'GB',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //check user now has a mangopay user id
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertNotEmpty($user->getMangoPayUserId());
        $this->assertNotEmpty($user->getMangoPayWalletId());
    }
}
