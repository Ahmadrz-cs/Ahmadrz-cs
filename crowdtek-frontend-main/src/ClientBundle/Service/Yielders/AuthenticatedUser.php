<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class AuthenticatedUser extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self';
    protected string $apiPrefix = '/v1/yielders';

    public function retrieve(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }

    public function retrieveInvestments(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/investments');
    }

    public function salesforceSync(array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . '/salesforce-sync', $parameters);
    }

    public function createScaEnrollment(): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . '/sca/enroll');
    }

    public function updateScaStatus(array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->patch(self::RESOURCE_PREFIX . '/sca/status', $parameters);
    }

    public function retrievePortfolio(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio');
    }

    public function retrievePortfolioUnsettled(array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio/unsettled', $parameters);
    }

    public function retrievePortfolioShareTrades(array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio/share-trades', $parameters);
    }

    public function retrievePortfolioTradeOrders(array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio/trade-orders', $parameters);
    }

    public function retrievePortfolioPayouts(array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio/payouts', $parameters);
    }

    public function retrievePortfolioPrefunding(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/portfolio/prefunding');
    }
}
