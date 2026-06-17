<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class OfferingClassic extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/offerings';
    protected string $apiPrefix = '/v1/yielders';

    public function all(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }

    public function retrieve(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function createPayment(int $offeringId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$offeringId}/payments", $parameters);
    }

    public function createPaymentOutcome(int $offeringId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$offeringId}/payment-outcome", $parameters);
    }
}
