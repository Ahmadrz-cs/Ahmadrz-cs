<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use DateTime;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutCreatePermissionsTest extends FixtureWebTestCase
{
    public function testCreatePayoutAsAdminMissingScope(): void
    {
        $scopes = array_diff($this->permittedScopes, ['payout:write']);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'investmentId' => $sample[0],
            'amount' => 218.70,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreatePayoutAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'investmentId' => $sample[0],
            'amount' => 218.70,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreatePayoutAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => 'settled'],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $date = new DateTime('first day of this month');
        $content = json_encode([
            'investmentId' => $sample[0],
            'amount' => 218.70,
            'dueDate' => $date->format(DateTime::ATOM),
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
