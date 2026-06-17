<?php

namespace App\Tests\Controller\ApiV1\Document;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Document;
use App\Entity\InvestmentDocuments;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DocumentGetPermissionTest extends FixtureWebTestCase
{
    public static function userProofDocProvider(): \Generator
    {
        yield 'poc' => ['proof_of_company'];
        yield 'poa' => ['proof_of_address'];
        yield 'poi' => ['proof_of_identity'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userProofDocProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocUserOtherAsAdmin(string $tag): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(UserDocument::class, [
            'tag' => $tag,
            'user' => $filter,
        ])[0]
            ->getDocument()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userProofDocProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocUserOtherAsRegUser(string $tag): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(UserDocument::class, [
            'tag' => $tag,
            'user' => $filter,
        ])[0]
            ->getDocument()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userProofDocProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetDocUserOtherAsPublic(string $tag): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(UserDocument::class, [
            'tag' => $tag,
            'user' => $filter,
        ])[0]
            ->getDocument()
            ->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/documents/$sample";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // $this->assertResponseIsSuccessful();
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetDocShareCertOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
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
        $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testGetDocShareCertOtherAsPublic(): void
    {
        $this->loginApiClientPublic();
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
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // $this->assertResponseIsSuccessful();
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertEquals(Response::HTTP_NOT_FOUND, $apiResponse['status']);
    }
}
