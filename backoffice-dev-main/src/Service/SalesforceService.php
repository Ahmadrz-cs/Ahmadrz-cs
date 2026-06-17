<?php

namespace App\Service;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class SalesforceService
{
    public function __construct(
        private string $salesforce_id,
        private string $salesforce_secret,
        private string $salesforce_refresh_token,
    ) {}

    public function create(string $object, array $data): ResponseInterface|bool
    {
        $client = $this->loginToSalesforce();

        if ($client) {
            $response = $this->requestSalesforceUserCreate($client, $object, $data);
        } else {
            return false;
        }
        return $response;
    }

    public function update(
        string $object,
        string $id,
        array $data,
    ): ResponseInterface|bool {
        $client = $this->loginToSalesforce();

        if ($client) {
            $response = $this->requestSalesforceUserUpdate(
                $client,
                $object,
                $id,
                $data,
            );
        } else {
            return false;
        }
        return $response;
    }

    public function retrieve(string $object, string $id): ResponseInterface|bool
    {
        $client = $this->loginToSalesforce();

        if ($client) {
            $response = $this->requestSalesforceUserRetrieve($client, $object, $id);
        } else {
            return false;
        }
        return $response;
    }

    public function delete(string $object, string $id): ResponseInterface|bool
    {
        $client = $this->loginToSalesforce();

        if ($client) {
            $response = $this->requestSalesforceUserDelete($client, $object, $id);
        } else {
            return false;
        }
        return $response;
    }

    protected function loginToSalesforce(): Client|bool
    {
        // if we ever decide to do caching, you'd do a check here
        // otherwise get a new token
        // return an array with "access_token" and "instance_url" as keys
        $token_response = $this->requestSalesforceAuthentication();

        if ($token_response->getStatusCode() == 200) {
            $body = json_decode($token_response->getBody(), true);
            $salesforce_client = $this->createSalesforceClient($body);
            return $salesforce_client;
        } else {
            return false;
        }
    }

    protected function createSalesforceClient(array $auth_info): Client
    {
        $client = new Client([
            'base_uri' => $auth_info['instance_url'],
            'headers' => [
                'Authorization' => 'Bearer ' . $auth_info['access_token'],
            ],
        ]);
        return $client;
    }

    // 3rd party request functions - mockable response
    protected function requestSalesforceAuthentication(): ResponseInterface
    {
        $client = new Client([
            'base_uri' => 'https://login.salesforce.com',
        ]);

        $response = $client->request('POST', '/services/oauth2/token', [
            'auth' => [
                $this->salesforce_id,
                $this->salesforce_secret,
            ],
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->salesforce_refresh_token,
                'format' => 'json',
            ],
        ]);
        return $response; // this is a HTTP response, not an array of values
    }

    protected function requestSalesforceUserCreate(
        Client $client,
        string $object,
        array $data,
    ): ResponseInterface {
        $response = $client->request(
            'POST',
            '/services/data/v45.0/sobjects/' . $object,
            [
                'json' => $data,
            ],
        );
        return $response;
    }

    protected function requestSalesforceUserUpdate(
        Client $client,
        string $object,
        string $user_id,
        array $data,
    ): ResponseInterface {
        $response = $client->request(
            'PATCH',
            '/services/data/v45.0/sobjects/' . $object . '/' . $user_id,
            [
                'json' => $data,
            ],
        );
        return $response;
    }

    protected function requestSalesforceUserRetrieve(
        Client $client,
        string $object,
        string $user_id,
    ): ResponseInterface {
        $response = $client->request(
            'GET',
            '/services/data/v45.0/sobjects/' . $object . '/' . $user_id,
        );
        return $response;
    }

    protected function requestSalesforceUserDelete(
        Client $client,
        string $object,
        string $user_id,
    ): ResponseInterface {
        $response = $client->request(
            'DELETE',
            '/services/data/v45.0/sobjects/' . $object . '/' . $user_id,
        );
        return $response;
    }
}
