<?php

namespace ClientBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PublicService
{
    public function __construct(
        private LoggerInterface $logger,
        private UrlGeneratorInterface $router,
        private CrowdTekClient $crowdTekClient,
        private string $clientId,
        private string $clientSecret,
        private string $accountUrl,
        private string $emailCheckKey,
        private string $company,
    ) {
    }

    /**
     * Get JWT for user
     */
    public function authenticate($network, $username, $password)
    {
        $this->logger->info("==================IN authenticate=====================");

        $data = [
            'grant_type' => 'password',
            // 'scope' => 'read',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'username' => $username,
            'password' => $password
        ];

        $uri = '/oauth2/token';
        $options = [
            'json' => $data
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getAccessTokenWithAuthCode(string $code)
    {
        $this->logger->info("==================IN getAccessTokenWithAuthCode=====================");

        $parameters = [
            'grant_type' => 'authorization_code',
            // 'scope' => 'read',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->router->generate('auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'code' => $code
        ];

        $uri = '/oauth2/token';
        $options = [
            'json' => $parameters
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function getAuthCodeUrl(string $csrf, string $scope = 'read')
    {
        $this->logger->info("==================IN getAuthCode=====================");

        $parameters = [
            'response_type' => 'code',
            // 'scope' => 'read',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->router->generate('auth_callback', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'state' => $csrf
        ];

        return $this->accountUrl . '/oauth2/authorize?' . http_build_query($parameters);
    }

    public function getOauthLogoutUrl()
    {
        $this->logger->info("==================IN getOauthLogoutUrl=====================");

        $parameters = [
            'continue_url' => $this->router->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_URL)
        ];
        return $this->accountUrl . '/oauth2/logout?' . http_build_query($parameters);
    }

    public function resetPwd($network, $parameters)
    {
        $this->logger->info("==================IN resetPwd=====================");

        $uri = 'v1/' . $network . '/public/resetPassword';
        $options = [
            'json' => $parameters
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    public function forgotPwd($network, $parameters)
    {
        $this->logger->info("==================IN forgotPwd=====================");

        $uri = 'v1/' . $network . '/public/forgotPassword';
        $options = [
            'json' => $parameters
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    /**
     * @param $network
     * @param $parameters
     * @return mixed
     */
    public function verifyEmail($network, $parameters)
    {
        $this->logger->info("==================IN verifyEmail=====================");

        $uri = 'v1/' . $network . '/public/verifyEmail';
        $options = [
            'json' => $parameters
        ];

        return $this->crowdTekClient->sendRequest('POST', $uri, $options);
    }

    /** Is this ever used? - talking about the route in Public Controller */
    public function getS3URL($network, $parameters)
    {
        $this->logger->info("==================IN getS3URL=====================");

        $uri = 'v1/' . $network . '/s3';
        $options = [
            'json' => $parameters
        ];

        return $this->crowdTekClient->sendRequest('PUT', $uri, $options);
    }

    public function getFeaturedOfferings($network, $limit, $offset, $filter = [])
    {
        $this->logger->info("==================IN getFeaturedOfferings=====================");

        $params = http_build_query(['limit' => $limit, 'offset' => $offset, 'filter' => $filter]);
        $uri = 'v1/' . $network . '/public/featuredOfferings?' . $params;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function getOrganizations($network, $params = [])
    {
        $this->logger->info("==================IN getOrganizations=====================");

        $params = http_build_query($params);
        $uri = 'v1/' . $network . '/public/organizations?' . $params;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function getOrganization($network, $id)
    {
        $this->logger->info("==================IN getOrganization=====================");

        if ($this->company == 'CROWDTEK') {
            $uri = 'v1/' . $network . '/public/assets/' . $id;
        } else {
            $uri = 'v1/' . $network . '/public/organizations/' . $id;
        }
        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function getOffering($network, $id)
    {
        $this->logger->info("==================IN getOffering=====================");

        $uri = 'v1/' . $network . '/public/offerings/' . $id;

        return $this->crowdTekClient->sendRequest('GET', $uri);
    }

    public function checkEmailAddress($network, $email)
    {
        $this->logger->info("==================In checkEmailAddresses=====================");

        $secret = hash('sha256', $this->emailCheckKey);

        $uri = 'v1/' . $network . '/public/checkEmailAddress';
        $options = [
            'headers' => [
                'Auth' => $secret,
            ],
            'json' => ['email' => $email],
        ];

        return $this->crowdTekClient->sendRequest('GET', $uri, $options);
    }
}
