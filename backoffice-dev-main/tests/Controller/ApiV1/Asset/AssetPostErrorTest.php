<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetPostErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateAssetFieldMin(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode(['display_name' => '']);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateAssetDocumentFieldNameMissing(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets/1/documents';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'tag' => '',
            'file_name' => '',
            'file_type' => 'xlsx',
            'document_content' => base64_encode('something'),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateAssetDocumentFieldContentMissing(): void
    {
        $this->loginApiClientUser(self::USER_SUPER_ADMIN);
        $uri = self::API_PATH_PREFIX_V1 . '/assets/1/documents';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'tag' => '',
            'file_name' => 'Test_File.txt',
            'file_type' => 'txt',
            'document_content' => '',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseIsSuccessful();
        // Slightly unconventional, since you still get a 200 status code
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $apiResponse['status']);
    }

    #[\PHPUnit\Framework\Attributes\Group('error')]
    public function testCreateAssetWalletFieldMangopayIdMissing(): void
    {
        //@todo check with Sohail on whether this should be enabled again
        return;

        // $this->client = static::createAuthenticatedApiClient();
        // /* @var Asset $randomAsset */
        // $randomAsset = $this->em->getRepository(Asset::class)->findOneById(3);
        // //Lets make sure this asset has no mangoPayId
        // $randomAsset->setMangoPayUserId(null);
        // $this->client->request('POST', $this->getAPINetworkPath().'/assets/' . $randomAsset->getId() . '/mangopayWallet');
        // $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        // $responseArray = json_decode($this->client->getResponse()->getContent(), true);
        // $this->assertValidApiResponse($responseArray);
        // $this->assertArrayHasKey( 'outcome',$responseArray );
        // $this->assertArrayHasKey( 'status',$responseArray );
        // $this->assertEquals( 'fail',$responseArray['outcome'] );
        // $this->assertEquals( '400',$responseArray['status'] );
    }
}
