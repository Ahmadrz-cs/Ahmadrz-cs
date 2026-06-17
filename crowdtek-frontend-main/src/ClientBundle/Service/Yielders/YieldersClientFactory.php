<?php

namespace ClientBundle\Service\Yielders;

use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RequestStack;

final class YieldersClientFactory
{
    public static function createApiClient(
        string $baseUri,
        array $config,
        RequestStack $requestStack,
    ): Client {
        // Only attempt to get the session if there is a request to retrieve it from
        if ($requestStack->getCurrentRequest() && $requestStack->getSession()->get('jwt_token')) {
            $defaultHeaders = [
                'Authorization' => 'Bearer ' . $requestStack->getSession()->get('jwt_token')
            ];
        }
        $httpClient = new Client([
            'base_uri' => $baseUri,
            'headers' => $defaultHeaders ?? [],
            'http_errors' => $config['http_errors'] ?? false
        ]);

        return $httpClient;
    }
}
