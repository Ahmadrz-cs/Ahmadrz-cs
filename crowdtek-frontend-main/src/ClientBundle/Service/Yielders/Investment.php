<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class Investment extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/investments';

    public function all(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }

    public function retrieve(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function create(array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX, $parameters);
    }

    public function update(int $id, array $parameters): ResponseInterface
    {
        return $this->patch(self::RESOURCE_PREFIX . '/' . $id, $parameters);
    }

    /**
     * can add similar ones for sub-fields, e.g. documents
     * they have slightly different urls
     */
}
