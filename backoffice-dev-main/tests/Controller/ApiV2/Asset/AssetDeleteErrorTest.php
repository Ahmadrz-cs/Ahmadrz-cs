<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetDeleteErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function undeletableAssetEndpointsProvider(): \Generator
    {
        yield 'asset collection' => ['/assets'];
        yield 'asset single' => ['/assets/1'];
        yield 'asset documents' => ['/assets/1/documents'];
        yield 'asset offerings' => ['/assets/1/offerings'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('undeletableAssetEndpointsProvider')]
    public function testDeleteAssetEndpointsNotAllowed($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;

        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDeleteAssetDocumentNotExists(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/asset/1/documents/-1';
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // need more fixtures for testDeleteAssetDocumentWrongId to work
    // public function testDeleteAssetDocumentWrongId(): void
    // {
    //
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => "published"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\AssetDocuments::class,
    //         ["asset" => $filter]
    //     );
    //     $sample = array_filter($sample, function($k) {
    //         return $k->getDocument()->getId() != $k->getId();
    //     });
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getAsset()->getId() . '/documents/' . $sample[0]->getDocument()->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    //     $sample = $this->filterAssetDocs(
    //         $this->getAllOfType(\App\Entity\AssetDocuments::class),
    //         ["id" => $sample[0]->getId()]
    //     );
    //     $this->assertEquals(1, count($sample));
    // }
}
