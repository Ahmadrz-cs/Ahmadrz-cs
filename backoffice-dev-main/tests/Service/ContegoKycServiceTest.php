<?php

namespace App\Tests\Service;

use App\Entity\ContegoLog;
use App\Entity\User;
use App\Service\ContegoKycService;
use App\Test\FixtureTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Nyholm\Psr7\Response;

final class ContegoKycServiceTest extends FixtureTestCase
{
    private const HEADER_JSON = ['content-type' => 'application/json'];

    /** @var ContegoKycService */
    private $service;

    /** @var array */
    private $history = [];

    /** @var MockHandler */
    private $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup the Guzzle handler stack with the mock handler
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add the history middleware
        $history = Middleware::history($this->history);
        $handlerStack->push($history);

        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            static::getContainer()->set('App\Service\Contego\ContegoClient', new Client([
                'handler' => $handlerStack,
            ]));
        }

        $this->service = static::getContainer()->get('App\Service\ContegoKycService');
    }

    public function testViewReport(): void
    {
        $rag = 'GREEN';
        $score = 0;
        $mockResponse = [
            'contegoResponse' => [
                'contegoScore' => [
                    'rag' => $rag,
                    'score' => $score,
                ],
            ],
        ];
        $this->mockHandler->append(
            new Response(200, self::HEADER_JSON, json_encode($mockResponse)),
        );
        /** @var ContegoLog $sample */
        $sample = $this->searchFixtures(ContegoLog::class, [
            'rag' => $rag,
            'score' => (string) $score,
        ])[0];
        /** @var User $sampleUser */
        $sampleUser = $this->searchFixtures(User::class, ['username' =>
            $sample->getUser()])[0];
        $actual = $this->service->viewReport($sampleUser, $sample->getExtReferenceId());
        $this->assertEquals(ContegoKycService::PROVIDER_NAME, $actual->providerName);
        $this->assertEquals($rag, $actual->result);
        $this->assertEquals($score, $actual->score);
        $this->assertTrue($actual->verified);
    }
}
