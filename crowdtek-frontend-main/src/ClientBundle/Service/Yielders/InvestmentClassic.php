<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class InvestmentClassic extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/investments';
    protected string $apiPrefix = '/v1/yielders';



    public function retrieve(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function createPayment(int $investmentId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$investmentId}/payments", $parameters);
    }

    public function createPaymentOutcome(int $investmentId, array $parameters = ['json' => []]): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$investmentId}/payment-outcome", $parameters);
    }
}
