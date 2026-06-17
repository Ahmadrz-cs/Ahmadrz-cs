<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentUpdatePermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'investment document update'|'investment update', array{0: '/investments/1'|'/investments/1/documents/1', 1: array{0: 'investment:write'}}, mixed, void>
     */
    public static function investmentEndpointScopeProvider(): \Generator
    {
        yield 'investment update' => ['/investments/1', ['investment:write']];
        yield 'investment document update' => [
            '/investments/1/documents/1',
            ['investment:write'],
        ];
    }

    /**
     * @psalm-return \Generator<'investment document update'|'investment update', array{0: '/investments/1'|'/investments/1/documents/1'}, mixed, void>
     */
    public static function investmentEndpointsProvider(): \Generator
    {
        yield 'investment update' => ['/investments/1'];
        yield 'investment document update' => ['/investments/1/documents/1'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointScopeProvider')]
    public function testUpdateInvestmentEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateInvestmentOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0];
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateInvestmentOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0];
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testUpdateInvestmentDocumentOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $filter = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $sample,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $filter[0]->getInvestment()->getId()
            . '/documents/'
            . $filter[0]->getId();
        $content = json_encode([
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateInvestmentDocumentOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $filter = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $sample,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $filter[0]->getInvestment()->getId()
            . '/documents/'
            . $filter[0]->getId();
        $content = json_encode([
            'tag' => 'test',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointsProvider')]
    public function testUpdateInvestmentEndpointsAsPublic($endpoint): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
