<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class TradeOrder extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/trade-orders';
    protected string $apiPrefix = '/v1/yielders';


    public function create(array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX, $parameters);
    }

    public function createPayment(int $orderId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$orderId}/payments", $parameters);
    }

    public function createPaymentOutcome(int $orderId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$orderId}/payment-outcome", $parameters);
    }
}
