<?php

namespace App\Tests\Controller\ApiV2\Asset;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class AssetUpdateResponseTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'basic-core fields'|'financial fields', array{0: array{numberOfShares?: '1000000', pricePerShare?: '1.25', setupFee?: '0', adminFee?: '50', managementFee?: '10', profitShare?: '15', name?: 'asset name updated', type?: 'Residential', companyNumber?: 'SPVU1008', displayName?: 'asset name display updated'}}, mixed, void>
     */
    public static function fieldsetProvider(): \Generator
    {
        yield 'basic-core fields' => [
            [
                'name' => 'asset name updated',
                'type' => 'Residential',
                'companyNumber' => 'SPVU1008',
                'displayName' => 'asset name display updated',
            ],
        ];

        yield 'financial fields' => [
            [
                'numberOfShares' => '1000000',
                'pricePerShare' => '1.25',
                'setupFee' => '0',
                'adminFee' => '50',
                'managementFee' => '10',
                'profitShare' => '15',
                // 'mangoPayUserId' => '11115555',
                // 'mangoPayWalletId' => '55559999',
                // 'additionalWallet' => '22224444',
            ],
        ];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: string}, mixed, void>
     */
    public function assetStatusTransitionProvider(): \Generator
    {
        yield 'draft to submitted' => ['draft', 'submitted'];
        yield 'draft to approved' => ['draft', 'approved'];
        yield 'draft to published' => ['draft', 'published'];
        yield 'draft to cancelled' => ['draft', 'cancelled'];
        yield 'submitted to approved' => ['submitted', 'approved'];
        yield 'approved to published' => ['approved', 'published'];
        yield 'published to cancelled' => ['published', 'cancelled'];
        yield 'published to archived' => ['published', 'archived'];
    }

    public function testUpdateAsset(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::ASSET_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fieldsetProvider')]
    public function testUpdateAssetFieldsSets($fieldset): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/assets/1';
        $content = json_encode($fieldset);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($fieldset as $key => $expected) {
            $this->assertEquals($expected, $apiResponse[$key]);
        }
    }

    // /**
    //  * @dataProvider assetStatusTransitionProvider
    //  */
    // public function testUpdateAssetFieldsStatus($initialState, $expected): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Asset::class,
    //         ["status" => $initialState],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0];
    //     $content = json_encode([
    //         "status" => $expected
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $actual = $apiResponse["status"];
    //     $this->assertEquals($expected, $actual);
    // }
    // public function testUpdateAssetDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\AssetDocuments::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/assets/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test this, test that",
    //         "tag" => "some other tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     foreach ($fieldset as $key => $expected) {
    //         $this->assertEquals($expected, $apiResponse[$key]);
    //     }
    // }
}
