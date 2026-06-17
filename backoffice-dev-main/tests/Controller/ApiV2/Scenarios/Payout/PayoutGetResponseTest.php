<?php

namespace App\Tests\Controller\ApiV2\Scenarios\Payout;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class PayoutGetResponseTest extends FixtureWebTestCase
{
    public function testGetPayoutScenarioInvalidIntegrity(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $payouts = $this->searchFixtures(\App\Entity\Payout::class, []);

        $payout = null;
        foreach ($payouts as $payout) {
            if (!$payout->getUserId() and !$payout->getAssetId()) {
                $payout = $payout;
            }
        }

        $uri = self::API_PATH_PREFIX_V2 . '/payouts/' . $payout->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
