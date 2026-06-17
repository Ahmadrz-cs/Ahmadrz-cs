<?php

declare(strict_types=1);

namespace Tests\ClientBundle\Service\YieldersApi;

use ClientBundle\Service\Yielders\YieldersClientFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class YieldersClientFactoryTest extends TestCase
{
    /**
     * @var RequestStack $requestStack
     */
    protected $requestStack;

    protected function setUp(): void
    {
        // Create a simple request stack with a single request that has a session
        // Intended as a placeholder so we can set session parameters like the jwt_token
        $this->requestStack = new RequestStack();
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        $this->requestStack->push($request);
    }

    public function testCreateApiClientTypical()
    {
        $hex = bin2hex(openssl_random_pseudo_bytes(16));
        $uri = 'https://example.com';
        $this->requestStack->getSession()->set('jwt_token', $hex);

        $factory = new YieldersClientFactory();
        $client = $factory->createApiClient(
            $uri,
            ['http_errors' => false],
            $this->requestStack
        );
        $config = $client->getConfig();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertFalse($config['http_errors']);
        $this->assertSame((string) Utils::uriFor($uri), (string) $config['base_uri']);
        $this->assertSame('Bearer ' . $hex, $config['headers']['Authorization']);
    }

    public function testCreateApiClientNoToken()
    {
        $factory = new YieldersClientFactory();
        $client = $factory->createApiClient(
            'https://example.com',
            [],
            $this->requestStack
        );
        $config = $client->getConfig();
        $this->assertNotContains('Authorization', array_keys($config['headers']));
    }
}
