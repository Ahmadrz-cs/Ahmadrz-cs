<?php

namespace App\Tests\Service;

use App\Entity\ContegoLog;
use App\Entity\KycReport;
use App\Entity\User;
use App\Service\ContegoKycService;
use App\Service\ContegoService;
use App\Test\ExternalServiceWebTestCase;

class ContegoServiceTest extends ExternalServiceWebTestCase
{
    public function testUserKYCNoNationalityThrowsException(): void
    {
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);
        // throws a UnexpectedValueException as we have not set the nationality for the user passport
        $this->expectException(\UnexpectedValueException::class);

        //Get a contego api test user
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user->setNationality(null);
        $service->createUserKYC($user);
    }

    public function testCreateUserKYC(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock();
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        // Get a contego api test user

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user->setPassportCountry('GB');

        //Contego score is null before request but should not be null after
        // $this->assertEquals(null, $user->getContegoScore());

        $response = $service->createUserKYC($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        //Check contegoLog has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['user' => self::USER_REGULAR]);
        $this->assertEquals(self::USER_REGULAR, $contegoLog->getUser());

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    public function testCreateOrganisationKYC(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock();
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        //Get a contego api test user that has a company address

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_VENDOR]);

        //Contego score is null before request but should not be null after
        // $this->assertEquals(null, $user->getContegoScore());

        $response = $service->createOrganisationKYC($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        //Check contegoLog has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['user' => self::USER_VENDOR]);
        $this->assertEquals(self::USER_VENDOR, $contegoLog->getUser());

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    public function testCreateUserKYCWithDoc(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock();
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        // Get a contego api test user who has poi docs

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        $response = $service->createUserKYCWithDoc($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        //Check contegoLog has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['user' => self::USER_REGULAR]);
        $this->assertEquals(self::USER_REGULAR, $contegoLog->getUser());

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    /*
     * See issue https://gitlab.helpmewithit.com:7055/yielders2/Phase2/issues/234
     * When contego returns a response of 0 for score doing a createUserKYCWithDoc
     * The ContegoService->validateContegoResponse was throwing an Contego API call did not contain a valid response for [score] exception
     */
    public function testCreateUserKYCWithDocWithReturnScoreOfZero(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock([
                [
                    'contegoScore' => [
                        'alert' => [
                            0 => [
                                'score' => 0,
                                'contegoCode' => '1252',
                                'contegoRuleId' => 1252,
                                'ruleLogic' => '',
                                'entityList' => [
                                    0 => 'Person',
                                ],
                                'report' => true,
                                'data' => [],
                                'category' => 'Person',
                                'status' => null,
                                'message' => 'No related addresses found for Someone else',
                                'type' => 'ALERT',
                            ],
                        ],
                        'score' => 0,
                        'rag' => 'GREEN',
                    ],
                    'header' => [
                        'checkID' => '3217402',
                        'errorMessages' => null,
                        'profileName' => 'Yielders',
                        'requestRef' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
                        'pdfreport' => null,
                        'balance' => -111.0,
                        'profileDesc' => null,
                        'responseCode' => null,
                        'limit' => 2000.0,
                    ],
                    'loadTime' => [],
                ],
            ]);
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        //Get a contego api test user who has a poi doc

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);

        $response = $service->createUserKYCWithDoc($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        // Check contegoLog and kyc report has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['ext_reference_id' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc']);
        $this->assertEquals(self::USER_REGULAR, $contegoLog?->getUser());
        $this->assertEquals('GREEN', $contegoLog?->getRAG());

        /** @var KycReport $kycReport */
        $kycReport = $this->entityManager
            ->getRepository(KycReport::class)
            ->findOneBy([
                'providerReferenceId' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
            ]);
        $this->assertEquals(
            self::USER_REGULAR,
            $kycReport->subject?->getUserIdentifier(),
        );
        $this->assertEquals(ContegoKycService::PROVIDER_NAME, $kycReport->providerName);
        $this->assertEquals($contegoLog->getKycType(), $kycReport->checkType);
        $this->assertEquals('GREEN', $kycReport->result);
        $this->assertEquals(0, $kycReport->score);
        $this->assertTrue($kycReport->verified);

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    public function testCreateUserKYCWithReturnScoreOfZero(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock([
                [
                    'contegoScore' => [
                        'alert' => [
                            0 => [
                                'score' => 0,
                                'contegoCode' => '1252',
                                'contegoRuleId' => 1252,
                                'ruleLogic' => '',
                                'entityList' => [
                                    0 => 'Person',
                                ],
                                'report' => true,
                                'data' => [],
                                'category' => 'Person',
                                'status' => null,
                                'message' => 'No related addresses found for Someone else',
                                'type' => 'ALERT',
                            ],
                        ],
                        'score' => 0,
                        'rag' => 'GREEN',
                    ],
                    'header' => [
                        'checkID' => '3217402',
                        'errorMessages' => null,
                        'profileName' => 'Yielders',
                        'requestRef' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
                        'pdfreport' => null,
                        'balance' => -111.0,
                        'profileDesc' => null,
                        'responseCode' => null,
                        'limit' => 2000.0,
                    ],
                    'loadTime' => [],
                ],
            ]);
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        //Get a contego api test user
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user->setPassportCountry('GB');

        $response = $service->createUserKYC($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        // Check contegoLog and kyc report has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['ext_reference_id' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc']);
        $this->assertEquals(self::USER_REGULAR, $contegoLog?->getUser());
        $this->assertEquals('GREEN', $contegoLog?->getRAG());

        /** @var KycReport $kycReport */
        $kycReport = $this->entityManager
            ->getRepository(KycReport::class)
            ->findOneBy([
                'providerReferenceId' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
            ]);
        $this->assertEquals(
            self::USER_REGULAR,
            $kycReport->subject?->getUserIdentifier(),
        );
        $this->assertEquals(ContegoKycService::PROVIDER_NAME, $kycReport->providerName);
        $this->assertEquals($contegoLog->getKycType(), $kycReport->checkType);
        $this->assertEquals('GREEN', $kycReport->result);
        $this->assertEquals(0, $kycReport->score);
        $this->assertTrue($kycReport->verified);

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    public function testCreateUserKYCWithReturnScoreOfAmber(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock([
                [
                    'contegoScore' => [
                        'alert' => [
                            0 => [
                                'score' => 0,
                                'contegoCode' => '1252',
                                'contegoRuleId' => 1252,
                                'ruleLogic' => '',
                                'entityList' => [
                                    0 => 'Person',
                                ],
                                'report' => true,
                                'data' => [],
                                'category' => 'Person',
                                'status' => null,
                                'message' => 'No related addresses found for Someone else',
                                'type' => 'ALERT',
                            ],
                        ],
                        'score' => 125,
                        'rag' => 'AMBER',
                    ],
                    'header' => [
                        'checkID' => '3217402',
                        'errorMessages' => null,
                        'profileName' => 'Yielders',
                        'requestRef' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
                        'pdfreport' => 'test report url',
                        'balance' => -111.0,
                        'profileDesc' => null,
                        'responseCode' => null,
                        'limit' => 2000.0,
                    ],
                    'loadTime' => [],
                ],
            ]);
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        //Get a contego api test user
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user->setPassportCountry('GB');

        $response = $service->createUserKYC($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        // Check contegoLog and kyc report has been updated
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['ext_reference_id' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc']);
        $this->assertEquals(self::USER_REGULAR, $contegoLog?->getUser());
        $this->assertEquals('AMBER', $contegoLog?->getRAG());

        /** @var KycReport $kycReport */
        $kycReport = $this->entityManager
            ->getRepository(KycReport::class)
            ->findOneBy([
                'providerReferenceId' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
            ]);
        $this->assertEquals(
            self::USER_REGULAR,
            $kycReport->subject?->getUserIdentifier(),
        );
        $this->assertEquals(ContegoKycService::PROVIDER_NAME, $kycReport->providerName);
        $this->assertEquals($contegoLog->getKycType(), $kycReport->checkType);
        $this->assertEquals('AMBER', $kycReport->result);
        $this->assertEquals(125, $kycReport->score);
        $this->assertEquals('test report url', $kycReport->note);
        $this->assertFalse($kycReport->verified);

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }

    public function testCreateUserKYCWithRagWaiting(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useContegoServiceMock([
                [
                    'contegoScore' => [
                        'alert' => [
                            0 => [
                                'score' => 0,
                                'contegoCode' => '1252',
                                'contegoRuleId' => 1252,
                                'ruleLogic' => '',
                                'entityList' => [
                                    0 => 'Person',
                                ],
                                'report' => true,
                                'data' => [],
                                'category' => 'Person',
                                'status' => null,
                                'message' => 'No related addresses found for Someone else',
                                'type' => 'ALERT',
                            ],
                        ],
                        'score' => 0,
                        'rag' => 'WAITING',
                    ],
                    'header' => [
                        'checkID' => '3217402',
                        'errorMessages' => null,
                        'profileName' => 'Yielders',
                        'requestRef' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc',
                        'pdfreport' => null,
                        'balance' => -111.0,
                        'profileDesc' => null,
                        'responseCode' => null,
                        'limit' => 2000.0,
                    ],
                    'loadTime' => [],
                ],
            ]);
        }
        /** @var ContegoService $service */
        $service = static::getContainer()->get(ContegoService::class);

        //Get a contego api test user
        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user->setPassportCountry('GB');

        $response = $service->createUserKYC($user);

        $this->assertArrayHasKey('outcome', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('ContegoScore', $response['data']);
        $this->assertArrayHasKey('score', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('rag', $response['data']['ContegoScore']);
        $this->assertArrayHasKey('alerts', $response['data']['ContegoScore']);

        $this->assertEquals('success', $response['outcome']);
        $this->assertEquals('200', $response['status']);

        // No contego log and kyc report should be made if WAITING is the rag score
        /** @var ContegoLog $contegoLog */
        $contegoLog = $this->entityManager
            ->getRepository(ContegoLog::class)
            ->findOneBy(['ext_reference_id' => 'eda8bec4-ce55-4da6-9b98-cbfe5bf024fc']);
        $this->assertEmpty($contegoLog);

        //Check contegoScore has been updated
        $this->assertNotNull($user->getContegoScore());
    }
}
