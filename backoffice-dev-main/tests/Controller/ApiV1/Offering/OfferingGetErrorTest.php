<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings/10000';
        $this->client->request('GET', $uri);

        // $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingsCancelled(): void
    {
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_CANCELLED,
                'isSecondaryMrkt' => 1,
            ],
            true,
        );

        // Admins can still get cancelled offerings
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/{$sample[0]}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Regular users cannot get them
        // Seems to be unsafe behaviour when trying to get the single offering
        // Error: Call to a member function getIsSecondaryMrkt() on bool
        $this->loginApiClientUser(self::USER_REGULAR);
        // $uri = self::API_PATH_PREFIX_V1 . "/offerings/{$sample[0]}";
        // $this->client->request('GET', $uri);
        // $this->assertResponseIsSuccessful();
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);

        // Regular users won't see them in the list
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $parameters = [
            'offset' => 0,
            'limit' => 100,
        ];
        $this->client->request('GET', $uri, $parameters);

        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actual = array_map(
            fn($item): int => $item['id'],
            $apiResponse['data']['list'],
        );
        // No cancelled offerings should be in the list of returned offerings
        $this->assertEquals($sample, array_diff($sample, $actual));
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingsPaginationInvalid(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $parameters = [
            'offset' => 'a',
            'limit' => 3,
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingsCriteriaInvalid(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $parameters = [
            'id' => implode('.', [1, 8, 16, 22]),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingsSortInvalid(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $parameters = [
            'sort' => implode(',', ['-id', '%name']),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetOfferingsFilterInvalid(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $parameters = [
            'term' => 'a',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
