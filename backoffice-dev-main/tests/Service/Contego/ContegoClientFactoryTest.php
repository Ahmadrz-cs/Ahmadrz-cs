<?php

namespace App\Tests\Service\Contego;

use App\Service\Contego\ContegoClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;

final class ContegoClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateApiClientTypical(): void
    {
        $uri = 'https://example.com';
        $factory = new ContegoClientFactory();
        $client = $factory->createApiClient($uri, ['http_errors' => false]);
        $config = $client->getConfig();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertFalse($config['http_errors']);
        $this->assertSame((string) Utils::uriFor($uri), (string) $config['base_uri']);
    }
}
