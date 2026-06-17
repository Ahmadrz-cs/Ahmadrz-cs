<?php

namespace App\Tests\Controller\ApiV2\Scenarios\User;

use App\Entity\Lifecycle\UserLifecycle;
use App\Test\ExternalServiceWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class UserUpdateStatusTest extends ExternalServiceWebTestCase
{
    public function testScenarioUserRegComplete(): void
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

        //create new user with minimum requirement
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //get new user object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add all fields required for mangopay registration
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId;
        $content = json_encode([
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'phone' => '07785555107',
            'gender' => 'male',
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

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();

        //get user object following patch request
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check email still unverified
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //check mangopay id and wallet have been created
        $this->assertNotEmpty($user->getMangoPayUserId());
        $this->assertNotEmpty($user->getMangoPayWalletId());

        //verify email address
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/email-verification';
        $this->client->request('POST', $uri, [], []);

        //get user object following email verification
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check status is not complete as missing docs - proof of address and proof of id
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add proof of id and proof of address docs
        //proof of id file
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

        //proof of address file
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfAdress.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_address',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);

        //get user object following docs upload
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];
        $state = $user->getStatus();

        //check user state isRegCompleted is true
        $this->assertTrue($state->getIsRegCompleted());
        $this->assertEquals(UserLifecycle::STATE_APPROVED, $user->getLifecycleStatus());
        $this->assertEquals(5, $user->getOBStep());
    }

    public function testScenarioUserOnboardingCompleteWithRedRag(): void
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
                    'score' => self::KYC_TEST_SCORE,
                    'rag' => 'RED',
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

        //create new user with minimum requirement
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //get new user object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add all fields required for mangopay registration
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId;
        $content = json_encode([
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'phone' => '07785555107',
            'gender' => 'male',
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

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();

        //get user object following patch request
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check email still unverified
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //check mangopay id and wallet have been created
        $this->assertNotEmpty($user->getMangoPayUserId());
        $this->assertNotEmpty($user->getMangoPayWalletId());

        //verify email address
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/email-verification';
        $this->client->request('POST', $uri, [], []);

        //get user object following email verification
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check status is not complete as missing docs - proof of address and proof of id
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add proof of id and proof of address docs
        //proof of id file
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

        //proof of address file
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfAdress.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_address',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);

        //get user object following docs upload
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->assertEquals(5, $user->getOBStep());
    }

    public function testScenarioUserOnboardingCompleteWithAmberRag(): void
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
                    'score' => self::KYC_TEST_SCORE,
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

        //create new user with minimum requirement
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users';
        $content = json_encode([
            'email' => 'john.doe1@test2.com',
            'firstName' => 'John',
            'lastName' => 'doe',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $apiResponse['id'];

        //get new user object
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add all fields required for mangopay registration
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId;
        $content = json_encode([
            'dateOfBirth' => '01-01-1980',
            'nationality' => 'GB',
            'phone' => '07785555107',
            'gender' => 'male',
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

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();

        //get user object following patch request
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check email still unverified
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //check mangopay id and wallet have been created
        $this->assertNotEmpty($user->getMangoPayUserId());
        $this->assertNotEmpty($user->getMangoPayWalletId());

        //verify email address
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/email-verification';
        $this->client->request('POST', $uri, [], []);

        //get user object following email verification
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        //check status is not complete as missing docs - proof of address and proof of id
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );

        //add proof of id and proof of address docs
        //proof of id file
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

        //proof of address file
        $uri = self::API_PATH_PREFIX_V2 . '/users/' . $userId . '/documents';
        $content = json_encode([
            'fileName' => 'proofOfAdress.pdf',
            'documentContent' => ApiBase64Files::TEST_PDF,
            'tag' => 'proof_of_address',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);

        //get user object following docs upload
        $user = $this->searchFixtures(\App\Entity\User::class, ['id' => $userId])[0];

        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->assertEquals(5, $user->getOBStep());
    }
}
