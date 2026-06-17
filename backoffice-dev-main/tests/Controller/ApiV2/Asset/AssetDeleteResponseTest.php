<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Entity\Asset;
use App\Entity\AssetDocuments;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetDeleteResponseTest extends FixtureWebTestCase
{
    public function testDeleteAssetDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(Asset::class, [], true);
        $sample = $this->searchFixtures(AssetDocuments::class, ['asset' => $filter]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $actual = $this->entityManager
            ->getRepository(AssetDocuments::class)
            ->find($sample[0]->getId());
        $this->assertNull($actual);
    }
}
