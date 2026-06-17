<?php

namespace App\Service\Contego;

use GuzzleHttp\Client;

final class ContegoClientFactory
{
    public static function createApiClient(string $baseUri, array $config): Client
    {
        return new Client([
            'base_uri' => $baseUri,
            'headers' => $defaultHeaders ?? [],
            'http_errors' => $config['http_errors'] ?? false,
        ]);
    }
}
