<?php

namespace App\Tests\Controller\ApiV1\Public;

use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PublicGetResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPublicAssetFeatured(): void
    {
        //no authenication
        $sample = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
            'isFeatured' => 1,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1 . "/public/assets/{$sample->getAsset()->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $object = $apiResponse['data']['organization'];
        $this->assertArrayHasKey('id', $object);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPublicOfferingsFeatured(): void
    {
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'isFeatured' => 1,
            ],
            true,
        );
        $uri = self::API_PATH_PREFIX_V1 . '/public/featuredOfferings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertEquals(count($sample), count($objects));
        foreach ($objects as $item) {
            $this->assertEquals(true, $item['is_featured']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPublicOfferingsFeaturedWithParams(): void
    {
        // Check null query handling
        // https://gitlab.com/yielders2/backoffice-dev/-/issues/2131
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'isFeatured' => 1,
            ],
            true,
        );
        $parameters = [
            'offset' => null,
            'limit' => 100,
        ];
        $uri = self::API_PATH_PREFIX_V1 . '/public/featuredOfferings';
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $objects = $apiResponse['data']['list'];
        $this->assertEquals(count($sample), count($objects));
        foreach ($objects as $item) {
            $this->assertEquals(true, $item['is_featured']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    public function testGetPublicOfferings(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $objects = $apiResponse['data']['list'];
        foreach ($objects as $item) {
            $this->assertEquals(true, $item['is_featured']);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetPublicOffering(): void
    {
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'isFeatured' => 1,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/public/offerings/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $object = $apiResponse['data']['offering'];
        $this->assertEquals(true, $object['is_featured']);
    }
}
