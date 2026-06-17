<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class AssetProduct extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/asset-products';

    // This is an APIv1 route, but has APIv3 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function all(array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX, $parameters);
    }

    /**
     * Does not require an authenticated user
     */
    public function allPublicFeatured(array $parameters = []): ResponseInterface
    {
        return $this->get('/public/featured-products', $parameters);
    }

    public function retrieve(int $id): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id);
    }

    public function retrieveSellOrders(int $id, array $parameters = []): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX . '/' . $id . '/sell-orders', $parameters);
    }
}
