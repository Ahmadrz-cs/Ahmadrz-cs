<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Entity\Investment;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class InvestmentDeleteResponseTest extends FixtureWebTestCase
{
    public function testDeleteInvestmentDocument(): void
    {
        // To avoid deleting our fixtures, upload a new file that will be deleted instead
        // $this->loginApiClientUser(self::USER_ADMIN);
        // $filter = $this->searchFixtures(Investment::class, [
        //     "status" => 'settled'
        // ], true)[0];
        // // Create the new doc
        // $uri = self::API_PATH_PREFIX_V2 . "/investments/$filter/documents";
        // $content = json_encode([
        //     'type' => 'image/jpg',
        //     'fileName' => 'deleteInvestmentDocTest.jpg',
        //     'documentContent' => ApiBase64Files::TEST_JPG,
        //     'tag' => 'share_certificate'
        // ]);
        // $headers = [
        //     'CONTENT_TYPE' => 'application/json'
        // ];
        // $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $documentId = $apiResponse['id'];
        // // Delete the new doc
        // $uri = self::API_PATH_PREFIX_V2 . "/investments/$filter/documents/$documentId";
        // $this->client->request('DELETE', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        // // Try to get the doc again (not found)
        // $this->client->request('GET', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
