<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class Asset extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/assets';

    public function all(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }

    public function retrieve(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function retrieveOfferings(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id . '/offerings');
    }

    public function create(array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX, $parameters);
    }
}
