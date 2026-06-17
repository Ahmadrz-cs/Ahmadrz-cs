<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service\YieldersApi;

use ClientBundle\Service\Yielders\ApiClient;
use ClientBundle\Service\Yielders\Asset;
use ClientBundle\Service\Yielders\Investment;
use ClientBundle\Service\Yielders\Offering;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Log\Logger;

final class ApiClientTest extends TestCase
{
    public function testCreateApiClient()
    {
        $apiClient = new ApiClient(new Logger(), new Client());

        $this->assertInstanceOf(ApiClient::class, $apiClient);
        $this->assertInstanceOf(Client::class, $apiClient->getHttpClient());
    }

    public function testGetContent()
    {
        $apiClient = new ApiClient(new Logger(), new Client());

        $expected = [
            'id' => 1,
            'nested' => [
                'key' => 'value'
            ]
        ];
        $response = new Response(200, [], json_encode($expected));
        $this->assertEqualsCanonicalizing($expected, $apiClient->getContent($response));
    }

    public function testResources()
    {
        $apiClient = new ApiClient(new Logger(), new Client());
        $this->assertInstanceOf(Asset::class, $apiClient->asset());
        $this->assertInstanceOf(Investment::class, $apiClient->investment());
        $this->assertInstanceOf(Offering::class, $apiClient->offering());
    }
}
