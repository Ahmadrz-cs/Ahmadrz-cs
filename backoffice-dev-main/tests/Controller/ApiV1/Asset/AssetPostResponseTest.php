<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetPostResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateAssetFieldsMinimum(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['display_name' => 'example']);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }

    public function testCreateAssetOffering(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets/1/offerings';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'is_featured' => '1',
            'is_secondary_offering' => '1',
            'name' => 'kirtiman offering',
            'valuation' => '23.32',
            'equity_offered' => '10.35',
            'num_of_shares' => '10',
            'price_per_shares' => '2300',
            'net_rent_projected' => '1236.0',
            'gross_project_return' => '1245',
            'open_date' => '12-12-2017',
            'close_date' => '12-12-2019',
            'min_commit_user' => '12.36',
            'max_commit_user' => '123.56',
            'max_overfunding_amount' => '123.56',
            'category' => 'ram',
            'visibility' => '0',
            'funding_goal' => '40000',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
    }
}
