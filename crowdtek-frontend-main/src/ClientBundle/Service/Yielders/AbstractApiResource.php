<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractApiResource
{
    /**
     * @var \GuzzleHttp\Client;
     */
    private $client;
    private LoggerInterface $logger;

    protected string $apiPrefix = '/v2/yielders';

    public function __construct(ApiClient $client)
    {
        $this->client = $client->getHttpClient();
        $this->logger = $client->getLogger();
    }

    protected function get(string $uri, array $options = []): ResponseInterface
    {
        $response = $this->client->get($this->prepareUri($uri), $options);
        return $response;
    }

    protected function post(string $uri, array $options = []): ResponseInterface
    {
        $response = $this->client->post($this->prepareUri($uri), $options);
        return $response;
    }

    protected function patch(string $uri, array $options = []): ResponseInterface
    {
        $response = $this->client->patch($this->prepareUri($uri), $options);
        return $response;
    }

    protected function delete(string $uri, array $options = []): ResponseInterface
    {
        $response = $this->client->delete($this->prepareUri($uri), $options);
        return $response;
    }

    private function prepareUri(string $uri): string
    {
        return (string) $this->apiPrefix . $uri;
    }
}
