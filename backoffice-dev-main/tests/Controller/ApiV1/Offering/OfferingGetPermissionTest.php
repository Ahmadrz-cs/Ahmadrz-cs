<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetOfferingsInvestmentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(Investment::class, []);
        $sampleOffering = $sample[0]->getOffering()->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sampleOffering/investments";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetOfferingsAsVip(): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetOfferingsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetOfferingsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V1 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetOfferingSingleAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }
}
