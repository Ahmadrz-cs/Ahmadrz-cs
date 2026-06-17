<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingPostPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testCreateOfferingRelistedAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            User::class,
            [
                'username' => self::USER_REGULAR,
            ],
            true,
        )[0];
        $sample = $this->searchFixtures(Investment::class, [
            'status' => InvestmentLifecycle::STATE_SETTLED,
            'user' => $filter,
        ])[0];
        $uri =
            self::API_PATH_PREFIX_V1
            . "/assets/{$sample->getOffering()->getAsset()->getId()}/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'User offering create test',
            'funding_goal' => '200',
            'is_secondary_offering' => 1,
            'num_of_shares' => 0,
            'pricePerShare' => '0.00',
            'sell_investment' => $sample->getId(),
            // All the other fields in the old test
            // "term" => 3,
            // "min_commit_user" => "0.00",
            // "max_commit_user" => "20000.00",
            // "max_over_funding" => "2000000.00",
            // "comments" => null,
            // "visibility" => 0,
            // "currency" => null,
            // "investor_count" => "4",
            // "investment_count" => "7",
            // "amount_raised" => 2800,
            // "amount_percent" => 0.14,
            // "raised_percent" => 0.14,
            // "capital_outstanding" => null,
            // "primary_offering_id" => 5,
            // "documents" => [],
            // "custom" => [
            //     "net_rent_projected" => "7.00",
            //     "gross_rent_projected_return" => null,
            //     "gross_projected_return" => "7.00",
            // ],
            // "info" => [
            //     "net_rent_projected" => "7.00",
            //     "gross_rent_projected_return" => null,
            //     "gross_projected_return" => "7.00",
            // ],
            // "created_at" => "2017-04-05T09:16:25+01:00",
            // "submitted_at" => "2017-04-05T09:16:37+01:00",
            // "published_at" => "2017-04-05T09:16:42+01:00",
            // "settled_at" => null,
            // "updated_at" => "2017-04-05T09:16:42+01:00",
            // "user_id" => 11,
            // "gcen_client_id" => "missing field",
            // "loanbook_id" => "missing field",
            // "mangopay_wallet_id" => "missing field",
            // "max_commitment" => "20000.00",
            // "max_overfunding_amount" => "2000000.00",
            // "min_commitment" => "0.00",
            // "price_per_share" => "0.00",
            // "repayments_remaining" => "missing field",
            // "service_charge" => "missing field",
            // "sum_outstanding_payouts" => [
            //     "capitalgains" => "missing field",
            //     "loanrepayments" => "missing field",
            //     "dividends" => "missing field"
            // ]
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Verify offering has been created
        // Need to be admin to access unpublished offerings though!
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $object = $apiResponse['data']['offering'];
        $this->assertEquals('User offering create test', $object['name']);
        $this->assertEquals($sample->getId(), $object['sell_investment']);
        $this->assertEquals(
            OfferingLifecycle::STATE_DRAFT_INT,
            $object['life_cycle_stage'],
        );

        // Publish the offering as admin
        $content = json_encode([
            'life_cycle_stage' => OfferingLifecycle::STATE_PUBLISHED_INT,
        ]);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        // Verify the now published relisted offering is available to regular users
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $object = $apiResponse['data']['offering'];
        $this->assertEquals('User offering create test', $object['name']);
        $this->assertEquals($sample->getId(), $object['sell_investment']);
        $this->assertEquals(
            OfferingLifecycle::STATE_PUBLISHED_INT,
            $object['life_cycle_stage'],
        );
    }
}
