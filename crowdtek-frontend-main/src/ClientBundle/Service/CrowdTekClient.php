<?php

namespace ClientBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CrowdTekClient
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private string $endPoint,
        private string $clientId,
        private string $clientSecret,
    ) {
    }

    /**
     * Left as public to allow manual requests
     */
    public function createCtClient()
    {
        $defaultHeaders = [];
        $jwtAuth = $this->requestStack->getSession()->get('jwt_token');

        if ($jwtAuth) {
            $defaultHeaders = ['Authorization' => 'Bearer ' . $jwtAuth];
        }

        return new Client(
            [
                'base_uri' => $this->endPoint,
                'headers' => $defaultHeaders,
                'http_errors' => false
            ]
        );
    }

    public function refreshAccessToken()
    {
        $this->logger->info("==================IN refreshAccessToken=====================");

        $client = new Client([
            'base_uri' => $this->endPoint,
            'http_errors' => false
        ]);

        $data = [
            'grant_type' => 'refresh_token',
            // 'scope' => 'read',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->requestStack->getSession()->get('refresh_token')
        ];

        $uri = '/oauth2/token';
        $options = [
            'json' => $data
        ];

        try {
            $response = $client->request('POST', $uri, $options);
            $response = json_decode($response->getBody(), true);
            $this->requestStack->getSession()->set('jwt_token', $response['access_token']);
            $this->requestStack->getSession()->set('refresh_token', $response['refresh_token']);

            $this->logger->debug("Access token refreshed");
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->logger->notice("Refresh token failure: " . $response->getStatusCode() . " rsp body:" . $response->getBody());
        } catch (TransferException $e) {
            $this->logger->notice("Refresh token failure: " . $e->getMessage());
        }
    }


    /**
     * Takes in 3 arguments
     * - http verb as a string: GET, DELETE, HEAD, OPTIONS, PATCH, POST, PUT
     * - uri as a string: this will be appended to the base uri set in client
     * - optional Guzzle Request options in an array: http://docs.guzzlephp.org/en/stable/request-options.html
     */
    public function sendRequest($verb, $uri, $options = [])
    {
        $this->logger->info("==================IN sendRequest=====================");

        $client = $this->createCtClient();

        /**
         * If http_errors is set to false, you won't get any logs for 400x 5xx status codes
         * We do not handle any other exception type, only Guzzle Transfer exceptions for logging purposes
         *
         * Having http_errors enabled causes "JWT not found" spam in logs when visiting homepage
         * But valuable for debugging other real errors
         */
        try {
            $response = $client->request($verb, $uri, $options);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $this->logger->error("sendRequest error response: " . $response->getBody());
            $this->logger->debug("sendRequest response status: " . $response->getStatusCode());
        } catch (TransferException $e) {
            $this->logger->notice("sendRequest error: " . $e->getMessage());
        }
        return json_decode($response->getBody(), true);
    }
}
