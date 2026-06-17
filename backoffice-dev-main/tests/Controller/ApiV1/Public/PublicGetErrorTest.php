<?php

namespace App\Tests\Controller\ApiV1\Public;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PublicGetErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetPublicAssetNotFeatured(): void
    {
        //no authenication of API client
        $filter1 = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
            'isFeatured' => 0,
        ]);
        $filterAssets1 = array_map(
            fn($item): int => $item->getAsset()->getId(),
            $filter1,
        );
        $filter2 = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
            'isFeatured' => 1,
            'asset' => $filterAssets1,
        ]);
        $filterAssets2 = array_map(
            fn($item): int => $item->getAsset()->getId(),
            $filter2,
        );
        // array_values to reset array keys so they start from 0
        // quirk of PHP arrays unlike python lists
        $sample = array_values(array_diff($filterAssets1, $filterAssets2))[0];
        $uri = self::API_PATH_PREFIX_V1 . "/public/assets/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_ASSET_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }

    /***
     * @group error
     */
    public function testGetPublicOfferingNotFeatured(): void
    {
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
                'isFeatured' => 0,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/public/offerings/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
        $expectedResponse =
            ErrorResponse::$errorDetails[ErrorResponse::ERROR_OFFERING_NOT_FOUND];
        $this->assertEquals(
            $expectedResponse['user_message'],
            $apiResponse['data']['user_message'],
        );
    }
}
