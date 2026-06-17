<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class BankAccount extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/bank-accounts';

    // This is an APIv1 route, but has APIv3 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function all(array $parameters): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX, $parameters);
    }

    public function retrieve(string $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function create(array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX, $parameters);
    }

    public function mangopaySync(array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/mangopay-sync", $parameters);
    }

    public function activate(string $id): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$id}/activation");
    }

    public function activationOutcome(string $id, array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$id}/activation-outcome", $parameters);
    }

    public function actionCompletion(string $id, array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX . "/{$id}/action-completion", $parameters);
    }

    public function deactivate(string $id): ResponseInterface
    {
        return $this->delete(self::RESOURCE_PREFIX . "/{$id}");
    }
}
