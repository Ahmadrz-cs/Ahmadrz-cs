<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class KycReview extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/kyc-reviews';

    // This is an APIv1 route, but has APIv2 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function update(int $id, array $parameters): ResponseInterface
    {
        return $this->patch(self::RESOURCE_PREFIX . '/' . $id, $parameters);
    }
}
