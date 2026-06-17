<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class AbstractApiServiceTest extends KernelTestCase
{
    /**
     * @var array
     */
    protected $history = [];

    /**
     * @var MockHandler
     */
    protected $mockHandler;

    protected function setUp(): void
    {
        // Setup the Guzzle handler stack with the mock handler
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add the history middleware
        $history = Middleware::history($this->history);
        $handlerStack->push($history);

        $httpClient = new Client([
            // 'base_uri' => 'https://example.com',
            'handler' => $handlerStack,
            'http_errors' => false,
        ]);
        self::bootKernel();
        static::getContainer()->set('http.client', $httpClient);
    }
}
