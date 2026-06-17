<?php

namespace App\Tests\Controller\ApiV1\Document;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Document;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DocumentGetErrorTest extends FixtureWebTestCase
{
    public static function invalidTagsProvider(): \Generator
    {
        yield 'Empty' => ['UntaggedAssetDoc'];
        yield 'Weird and not accepted' => ['WeirdTaggedAssetDoc'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetDoc(string $description): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Document::class,
            [
                'description' => $description,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetDocAsRegUser(string $description): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            Document::class,
            [
                'description' => $description,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testGetDocAsPublic(string $description): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            Document::class,
            [
                'description' => $description,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // $this->assertResponseIsSuccessful();
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }
}
