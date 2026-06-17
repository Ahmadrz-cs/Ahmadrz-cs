<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Entity\Asset;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetGetPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetsUnauthenticatedAsPublic(): void
    {
        // This is without API authentication
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetOfferingsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
        ])[0]
            ->getAsset()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetOfferingsAsVip(): void
    {
        $this->loginApiClientUser(self::USER_VIP);
        $sample = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
        ])[0]
            ->getAsset()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetOfferingsDraftAsAdmin(): void
    {
        /**
         * Related to issue #935
         * Only offerings in published/settled/closed should be returned
         * No draft
         */
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'companyNumber' => 'SPVTMultiState',
            ],
            true,
        )[0];
        $checkAssetHasDraft = $this->searchFixtures(
            Offering::class,
            [
                'asset' => $sample,
                'status' => OfferingLifecycle::STATE_DRAFT,
            ],
            true,
        );
        $this->assertNotEmpty($checkAssetHasDraft);

        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        foreach ($objects as $object) {
            $this->assertTrue(in_array($object['life_cycle_stage'], [
                OfferingLifecycle::STATE_PUBLISHED_INT,
                OfferingLifecycle::STATE_CLOSED_INT,
                OfferingLifecycle::STATE_SETTELED_INT,
            ]));
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetAssetsAsRegUser(): void
    {
        // Check regular users only have access to published assets
        $this->loginApiClientUser(self::USER_REGULAR);

        /** @var Asset[] $assets */
        $assets = $this->searchFixtures(Asset::class, []);

        // Get one asset of each state
        $sample = [];
        foreach ($assets as $asset) {
            if (in_array($asset->getLifecycleStatus(), $sample)) {
                continue;
            }
            $sample[$asset->getId()] = $asset->getLifecycleStatus();
        }

        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $parameters = [
            'id' => implode(',', array_keys($sample)),
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        foreach ($objects as $object) {
            $this->assertEquals(
                OfferingLifecycle::STATE_PUBLISHED_INT,
                $object['life_cycle_stage'],
            );
        }
    }
}
