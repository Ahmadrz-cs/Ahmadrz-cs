<?php

namespace ClientBundle\Service\Yielders;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ApiClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(
        private LoggerInterface $logger,
        ClientInterface $client,
    ) {
        $this->client = $client;
    }

    public function getHttpClient(): Client
    {
        return $this->client;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getContent(ResponseInterface $response): ?array
    {
        return json_decode($response->getBody(), true);
    }

    public function asset(): Asset
    {
        return new Asset($this);
    }

    public function assetProduct(): AssetProduct
    {
        return new AssetProduct($this);
    }

    public function investment(): Investment
    {
        return new Investment($this);
    }

    public function investmentClassic(): InvestmentClassic
    {
        return new InvestmentClassic($this);
    }

    public function offering(): Offering
    {
        return new Offering($this);
    }

    public function offeringClassic(): OfferingClassic
    {
        return new OfferingClassic($this);
    }

    public function onboardingProfile(): OnboardingProfile
    {
        return new OnboardingProfile($this);
    }

    public function userAssessment(): UserAssessment
    {
        return new UserAssessment($this);
    }

    public function userCategorisation(): UserCategorisation
    {
        return new UserCategorisation($this);
    }

    public function generatedAssessment(): GeneratedAssessment
    {
        return new GeneratedAssessment($this);
    }

    public function kycReview(): KycReview
    {
        return new KycReview($this);
    }

    public function authenticatedUser(): AuthenticatedUser
    {
        return new AuthenticatedUser($this);
    }

    public function bankAccount(): BankAccount
    {
        return new BankAccount($this);
    }

    public function mangopayWallet(): MangopayWallet
    {
        return new MangopayWallet($this);
    }

    public function tradeOrder(): TradeOrder
    {
        return new TradeOrder($this);
    }
}
