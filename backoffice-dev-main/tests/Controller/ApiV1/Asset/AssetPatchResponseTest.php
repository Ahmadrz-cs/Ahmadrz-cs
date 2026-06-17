<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Entity\Asset;
use App\Test\FixtureWebTestCase;

class AssetPatchResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateAsset(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $sample = $this->searchFixtures(Asset::class, ['id' => 1], true)[0];
        $sampleName = bin2hex(random_bytes(8));
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample";
        $content = json_encode([
            'display_name' => $sampleName,
            'address' => [
                'street_address' => '1 London Drive',
                'city' => 'Cheshire',
                'postcode' => 'J0K SR1',
                'country' => 'United Kingdom',
                'longitude' => '123.111111',
                'latitude' => '888.111111',
            ],
        ]);

        $headers = ['CONTENT_TYPE' => 'application/json'];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($sample, $apiResponse['data']['organization_id']);

        $expected = $this->searchFixtures(Asset::class, ['id' => 1])[0];
        $this->assertEquals($sampleName, $expected->getDisplayName());
        $this->assertEquals('Cheshire', $expected->getMainAddress()->getCity());
        $this->assertEquals(
            '1 London Drive',
            $expected->getMainAddress()->getAddress1(),
        );
        $this->assertEquals('J0K SR1', $expected->getMainAddress()->getPostCode());
        $this->assertEquals('GB', $expected->getMainAddress()->getCountry());
        $this->assertEquals('123.111111', $expected->getMainAddress()->getLongitude());
        $this->assertEquals('888.111111', $expected->getMainAddress()->getLatitude());
    }
}
