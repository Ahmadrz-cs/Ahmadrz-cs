<?php

namespace App\Tests\Controller\ApiV1\Document;

use App\Entity\Document;
use App\Entity\InvestmentDocuments;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;
use Symfony\Component\HttpFoundation\Response;

class DocumentGetResponseTest extends FixtureWebTestCase
{
    public static function userProofDocProvider(): \Generator
    {
        yield 'poc' => ['proof_of_company'];
        yield 'poa' => ['proof_of_address'];
        yield 'poi' => ['proof_of_identity'];
    }

    public static function publicDocTagsProvider(): \Generator
    {
        yield 'calculations' => ['calculations'];
        yield 'asset log' => ['logo'];
        yield 'asset property photos' => ['property_photos'];

        // yield 'asset floor plan' => ['floor_plan']; // Skip as we just use property photos
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfDocuments(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/self/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $objects = $apiResponse['data']['list'];
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::DOCUMENT_USER,
            array_keys($objects[0]),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userProofDocProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocUserOwn(string $tag): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        // Ownership of docs is based on createdById()
        // So must create a new doc in order to retrieve it
        $uri = self::API_PATH_PREFIX_V1 . '/self/documents';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'tag' => $tag,
            'file_name' => 'test',
            'file_type' => 'txt',
            'document_content' => base64_encode('something'),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $sample = $apiResponse['data']['document_id'];

        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($tag, $apiResponse['data']['document']['tag']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocShareCertOwn(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(InvestmentDocuments::class, [
            'tag' => 'share_certificate',
            'user' => $filter,
        ])[0]
            ->getDocument()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(
            'share_certificate',
            $apiResponse['data']['document']['tag'],
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicDocTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetDoc(string $tag): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Document::class,
            [
                'tag' => $tag,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($tag, $apiResponse['data']['document']['tag']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicDocTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocAsRegUser(string $tag): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            Document::class,
            [
                'tag' => $tag,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($tag, $apiResponse['data']['document']['tag']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicDocTagsProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocAsPublic(string $tag): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            Document::class,
            [
                'tag' => $tag,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // $this->assertResponseIsSuccessful();
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertEquals($tag, $apiResponse['data']['document']['tag']);
    }
}
