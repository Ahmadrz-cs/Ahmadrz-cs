<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutGetPermissionsTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'payout collection'|'payout single', array{0: '/payouts'|'/payouts/1', 1: array{0: 'payout:read'}}, mixed, void>
     */
    public static function payoutEndpointScopeProvider(): \Generator
    {
        yield 'payout collection' => ['/payouts', ['payout:read']];
        yield 'payout single' => ['/payouts/1', ['payout:read']];
    }

    /**
     * @psalm-return \Generator<'payout collection'|'payout single', array{0: '/payouts'|'/payouts/1'}, mixed, void>
     */
    public static function payoutEndpointProvider(): \Generator
    {
        yield 'payout collection' => ['/payouts'];
        yield 'payout single' => ['/payouts/1'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payoutEndpointScopeProvider')]
    public function testGetPayoutEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetPayoutsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetSinglePayoutOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['user' => $filter[0]],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/' . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::PAYOUT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        // assert payout id matches user client id
        $actualUserId = $apiResponse['userId'];
        $expectedUserId = $filter[0];
        $this->assertEquals($expectedUserId, $actualUserId);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payoutEndpointProvider')]
    public function testGetPayoutEndpointsOtherAsPublic($endpoint): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetSinglePayoutOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $filter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $sample = $this->searchFixtures(
            \App\Entity\Payout::class,
            ['user' => $filter[0]],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/' . $sample[0];
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
