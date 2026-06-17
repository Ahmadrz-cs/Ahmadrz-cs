<?php

namespace App\Tests\Controller\ApiV1\Investment;

use App\Entity\Investment;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentPatchResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(
            Investment::class,
            [
                'user' => $filter,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/investments/{$sample}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'Testing investment name change',
            'number_of_shares' => 3,
            'visibility' => '2',
        ]);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $sample = $this->searchFixtures(Investment::class, [
            'id' => $sample,
        ])[0];

        $this->assertEquals(3, $sample->getNumberOfShares());
        $this->assertEquals('Testing investment name change', $sample->getName());
        $this->assertEquals('2', $sample->getVisibility());
    }
}
