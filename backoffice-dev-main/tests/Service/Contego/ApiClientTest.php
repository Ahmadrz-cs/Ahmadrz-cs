<?php

namespace App\Tests\Service\Contego;

use App\Service\Contego\ApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Nyholm\Psr7\Response;

final class ApiClientTest extends \PHPUnit\Framework\TestCase
{
    private const HEADER_JSON = ['content-type' => 'application/json'];
    private const ORG_ID = 'abc';
    private const SIGNATURE = 'xyz';

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var array
     */
    private $history = [];

    /**
     * @var MockHandler
     */
    private $mockHandler;

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

        $this->apiClient = new ApiClient($httpClient, self::ORG_ID, self::SIGNATURE);
    }

    public function testGetContent(): void
    {
        $apiClient = new ApiClient(new Client(), 'abc', 'xyz');

        $expected = [
            'id' => 1,
            'nested' => [
                'key' => 'value',
            ],
        ];
        $response = new Response(200, [], json_encode($expected));
        $this->assertEqualsCanonicalizing($expected, $apiClient->getContent($response));
    }

    public function testRetrieve(): void
    {
        $this->mockHandler->append(new Response(200, self::HEADER_JSON));

        $this->apiClient->retrieve(uniqid());
        $this->assertEquals('POST', $this->history[0]['request']->getMethod());
        $this->assertEquals(
            '/rest/v2/getresponse',
            $this->history[0]['request']->getRequestTarget(),
        );
    }

    public function testPrepareRetrieve(): void
    {
        $reference = uniqid();
        $expected = [
            'json' => [
                'credentials' => [
                    'organisationUID' => self::ORG_ID,
                    'md5Signature' => self::SIGNATURE,
                ],
                'checkInfo' => [
                    'requestRef' => $reference,
                ],
            ],
        ];
        $actual = $this->apiClient->prepareRetrieve($reference);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }
}
