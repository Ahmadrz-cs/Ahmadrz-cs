<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutUpdatePermissionsTest extends FixtureWebTestCase
{
    public function testUpdatePayoutAsAdminMissingScope(): void
    {
        $scopes = array_diff($this->permittedScopes, ['payout:write']);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'type' => 'profit share',
            'amount' => 21.87,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdatePayoutOwnAsRegUser(): void
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

        $date = new DateTime('first day of this month');
        $content = json_encode([
            'type' => 'profit share',
            'amount' => 21.87,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdatePayoutOtherAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/payouts/1';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'type' => 'profit share',
            'amount' => 21.87,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdatePayoutOtherAsRegUser(): void
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

        $date = new DateTime('first day of this month');
        $content = json_encode([
            'type' => 'profit share',
            'amount' => 21.87,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
