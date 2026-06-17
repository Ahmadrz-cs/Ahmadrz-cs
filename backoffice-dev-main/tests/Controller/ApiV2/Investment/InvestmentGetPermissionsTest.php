<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentGetPermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string, 1: array{0: 'investment:read', 1?: 'payout:read'}}, mixed, void>
     */
    public static function investmentEndpointScopeProvider(): \Generator
    {
        yield 'investment collection' => ['/investments', ['investment:read']];
        yield 'investment single' => ['/investments/1', ['investment:read']];
        yield 'investment documents' => [
            '/investments/1/documents',
            ['investment:read'],
        ];
        yield 'investment document single' => [
            '/investments/1/documents/1',
            ['investment:read'],
        ];
        yield 'investment payout' => [
            '/investments/1/payouts',
            ['investment:read', 'payout:read'],
        ];
    }

    /**
     * @psalm-return \Generator<'single investment', array{0: ''}, mixed, void>
     */
    public static function investmentEndpointsProvider(): \Generator
    {
        yield 'single investment' => [''];

        // yield "investment documents" => ["/documents"];
        // yield "investment single document" => ["/documents/"];
        // yield "investment payouts" => ["/payouts"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointScopeProvider')]
    public function testGetInvestmentEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetInvestmentsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetInvestmentsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/investments';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointsProvider')]
    public function testGetInvestmentEndpointsOwnAsRegUser($endpoint): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled', 'user' => $userFilter],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
                'investment' => $filter,
            ]);
            $uri =
                self::API_PATH_PREFIX_V2
                . '/investments/'
                . $sample[0]->getInvestment()->getId()
                . '/documents/'
                . $sample[0]->getId();
        } else {
            $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . $endpoint;
        }
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointsProvider')]
    public function testGetInvestmentEndpointsOtherAsPublic($endpoint): void
    {
        $this->loginApiClientPublic();
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled', 'user' => $userFilter],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
                'investment' => $filter,
            ]);
            $uri =
                self::API_PATH_PREFIX_V2
                . '/investments/'
                . $sample[0]->getInvestment()->getId()
                . '/documents/'
                . $sample[0]->getId();
        } else {
            $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . $endpoint;
        }
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentEndpointsProvider')]
    public function testGetInvestmentEndpointsOtherAsRegUser($endpoint): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled', 'user' => $userFilter],
            true,
        );
        if ($endpoint === '/documents/') {
            $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
                'investment' => $filter,
            ]);
            $uri =
                self::API_PATH_PREFIX_V2
                . '/investments/'
                . $sample[0]->getInvestment()->getId()
                . '/documents/'
                . $sample[0]->getId();
        } else {
            $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $filter[0] . $endpoint;
        }
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
