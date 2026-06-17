<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class MangopayWallet extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/mangopay';

    protected string $apiPrefix = '/v1/yielders';

    public function retrieveWallet(array $parameters): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/wallet', $parameters);
    }

    public function listWalletTransactions(array $parameters): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/wallet/transactions', $parameters);
    }

    public function retrievePayin(string $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . "/payin/{$id}");
    }
}
