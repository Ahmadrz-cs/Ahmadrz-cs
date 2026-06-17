<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service\YieldersApi;

use ClientBundle\Service\Yielders\ApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Log\Logger;

abstract class AbstractApiResourceTest extends TestCase
{
    protected const HEADER_JSON = ['content-type' => 'application/json'];

    /**
     * @var ApiClient
     */
    protected $apiClient;

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
            'handler' => $handlerStack,
        ]);

        $this->apiClient = new ApiClient(new Logger(), $httpClient);
    }
}
