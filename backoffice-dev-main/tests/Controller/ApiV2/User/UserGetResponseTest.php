<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserGetResponseTest extends FixtureWebTestCase
{
    public function testGetUsers(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    public function testGetSingleUser(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::USER_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
        $this->assertEquals(1, $apiResponse['id']);
    }

    public function testGetSingleUserWallet(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/users/'
            . $sample[0]->getId()
            . '/wallets/'
            . $sample[0]->getMangoPayWalletId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::WALLET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    // public function testGetSingleUserQueryViewAdmin(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/1';
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::USER_ADMIN;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
    // public function testGetUserDocuments(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $filter = $this->searchFixtures(\App\Entity\UserDocument::class, []);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter[0]->getUser()->getId()],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $filter[0]->getUser()->getId() . '/documents';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals(count($sample), count($apiResponse['data']));
    // }
    // public function testGetUserSingleDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\UserDocument::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getId();
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::DOCUMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $this->assertEquals($sample[0]->getId(), $apiResponse['id']);
    // }
    // public function testGetUserInvestments(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\Investment::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/investments';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["user" => $sample[0]->getUser()->getId()]
    //     );
    //     $this->assertEquals(count($sample), count($apiResponse['data']));
    // }
    // public function testGetUserPayouts(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     // find a user that has at least 1 payout
    //     $filter = $this->searchFixtures(\App\Entity\Payout::class, [], true);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["id" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/payouts';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
    //     $actualFields = array_keys($apiResponse['data'][0]);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    //     // find how many payouts the chosen user has
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["user" => $sample[0]->getUser()->getId()],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Payout::class,
    //         ["investment" => $filter]
    //     );
    //     $this->assertEquals(count($sample), count($apiResponse['data']));
    // }
}
