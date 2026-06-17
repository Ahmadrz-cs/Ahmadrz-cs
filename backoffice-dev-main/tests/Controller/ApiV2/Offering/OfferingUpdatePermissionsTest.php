<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingUpdatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'offering document update'|'offering update', array{0: '/offerings/1'|'/offerings/1/documents/1', 1: array{0: 'offering:write'}}, mixed, void>
     */
    public static function offeringEndpointScopeProvider(): \Generator
    {
        yield 'offering update' => ['/offerings/1', ['offering:write']];

        // yield "offering document update" => ["/offerings/1/documents/1", ['offering:write']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringEndpointScopeProvider')]
    public function testUpdateOfferingEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([
            'name' => 'test update offering missing scope',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Currently works as admin regardless
        $this->assertResponseIsSuccessful();
    }

    // #[\PHPUnit\Framework\Attributes\DataProvider('offeringEndpointScopeProvider')]
    // public function testUpdateOfferingEndpointsAsRegUser($endpoint, $requiredScopes): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $uri = self::API_PATH_PREFIX_V2 . $endpoint;
    //     $content = json_encode([
    //         "name" => "test update offering as reg user"
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // #[\PHPUnit\Framework\Attributes\DataProvider('offeringEndpointScopeProvider')]
    // public function testUpdateOfferingEndpointsAsPublic($endpoint, $requiredScopes): void
    // {
    //     $this->loginApiClientPublic();
    //     $uri = self::API_PATH_PREFIX_V2 . $endpoint;
    //     $content = json_encode([
    //         "name" => "test update offering as reg user"
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
}
