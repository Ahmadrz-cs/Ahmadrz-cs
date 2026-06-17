<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetDeletePermissionsTest extends FixtureWebTestCase
{
    public function testDeleteAssetDocumentAsAdminMissingScope(): void
    {
        $scopes = array_diff($this->permittedScopes, ['asset:write']);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteAssetDocumentAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteAssetDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            \App\Entity\Asset::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class, [
            'asset' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/assets/'
            . $sample[0]->getAsset()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
