<?php

namespace App\Service\Contego;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

final class ApiClient
{
    private const API_PREFIX = '/rest/v2';

    public function __construct(
        private ClientInterface $client,
        private string $orgId,
        private string $signature,
    ) {}

    // public function getHttpClient(): Client
    // {
    //     return $this->client;
    // }

    public function getContent(ResponseInterface $response): ?array
    {
        return json_decode($response->getBody(), true);
    }

    // public function check(array $parameters): ResponseInterface
    // {
    //     $options = $this->prepareCheck($parameters);
    //     return $this->client->post(self::prepareUri('/check'), $options);
    // }

    public function retrieve(string $reference): ResponseInterface
    {
        $options = $this->prepareRetrieve($reference);
        return $this->client->post(self::prepareUri('/getresponse'), $options);
    }

    // public function prepareCheck(array $parameters): array
    // {
    //     // need to understand why we have 2 different profileIds for user and company
    //     $parameters['credentials'] = [
    //         'organisationUID' => $this->orgId,
    //         'profileUID' => $this->profileId,
    //         'md5Signature' => $this->signature,
    //     ];
    //     return [
    //         'json' => $parameters
    //     ];
    // }

    public function prepareRetrieve(string $reference): array
    {
        return [
            'json' => [
                'credentials' => [
                    'organisationUID' => $this->orgId,
                    'md5Signature' => $this->signature,
                ],
                'checkInfo' => [
                    'requestRef' => $reference,
                ],
            ],
        ];
    }

    private static function prepareUri(string $uri): string
    {
        return self::API_PREFIX . $uri;
    }
}
