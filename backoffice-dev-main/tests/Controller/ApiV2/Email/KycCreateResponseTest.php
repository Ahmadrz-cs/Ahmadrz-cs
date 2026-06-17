<?php

namespace App\Tests\Controller\ApiV2\Email;

use App\Test\MailcatcherTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class KycCreateResponseTest extends MailcatcherTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testScenarioKycTrigger(): void
    {
        $this->markTestSkipped('APIv2 user KYC deprecated');

        $this->useContegoServiceMock([
            [
                'contegoScore' => [
                    'score' => '0',
                    'rag' => 'AMBER',
                    'alert' => [
                        ['message' => 'alert1'],
                        ['message' => 'alert2'],
                    ],
                ],
                'header' => [
                    'requestRef' => 'xyz123',
                    'pdfreport' => 'http://abc',
                ],
            ],
            [
                'contegoScore' => [
                    'score' => '100',
                    'rag' => 'GREEN',
                    'alert' => [
                        ['message' => 'alert1'],
                        ['message' => 'alert2'],
                    ],
                ],
                'header' => [
                    'requestRef' => 'xyz123',
                    'pdfreport' => 'http://abc',
                ],
            ],
        ]);

        //create new user
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'address' => [
                'address1' => 'Number 20',
                'address2' => 'Block A',
                'address3' => '1 Quiet Lane',
                'region' => 'Camden',
                'city' => 'London',
                'postCode' => 'NW1 0JA',
                'country' => 'GB',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        $this->cleanMessages();

        //add POI file
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.jpg',
            'documentContent' => ApiBase64Files::TEST_JPG_32K,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        //check contego score object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals('GREEN', $user->getContegoScore()->getRAG());
        $this->assertEquals(100, $user->getContegoScore()->getKycScore());

        //check contego log is updated
        $log = $this->searchFixtures(\App\Entity\ContegoLog::class, ['username' =>
            $user->getUsername()])[1];

        $this->assertEquals('GREEN', $log->getRAG());
        $this->assertEquals(100, $log->getKycScore());

        //check doc has been uploaded to mangopay
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];
        $mangopayService = static::getContainer()->get(\App\Service\MangoPay::class);
        $kycDocs = $mangopayService->getKycDocuments($user);

        $this->assertEquals(1, count($kycDocs));
        $this->assertEquals('IDENTITY_PROOF', $kycDocs[0]->Type);
        $this->assertEquals('VALIDATION_ASKED', $kycDocs[0]->Status);

        $messages = $this->getMessages();
        $this->assertEquals(
            "Congratulations - You're a Yielder!",
            $messages[0]->subject,
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testScenarioKycNotGreen(): void
    {
        $this->markTestSkipped('APIv2 user KYC deprecated');

        $this->useContegoServiceMock([
            [
                'contegoScore' => [
                    'score' => '0',
                    'rag' => 'AMBER',
                    'alert' => [
                        ['message' => 'alert1'],
                        ['message' => 'alert2'],
                    ],
                ],
                'header' => [
                    'requestRef' => 'xyz123',
                    'pdfreport' => 'http://abc',
                ],
            ],
            [
                'contegoScore' => [
                    'score' => '100',
                    'rag' => 'AMBER',
                    'alert' => [
                        ['message' => 'alert1'],
                        ['message' => 'alert2'],
                    ],
                ],
                'header' => [
                    'requestRef' => 'xyz123',
                    'pdfreport' => 'http://abc',
                ],
            ],
        ]);

        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
            'password' => 'vsqks1Zqm',
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'address' => [
                'address1' => 'Number 20',
                'address2' => 'Block A',
                'address3' => '1 Quiet Lane',
                'region' => 'Camden',
                'city' => 'London',
                'postCode' => 'NW1 0JA',
                'country' => 'GB',
            ],
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        $this->cleanMessages();

        //add POI file
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfId.jpg',
            'documentContent' => ApiBase64Files::TEST_JPG_32K,
            'tag' => 'proof_of_identity',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $messages = $this->getMessages();
        $this->assertEquals('Contact Admin', $messages[0]->subject);
    }
}
