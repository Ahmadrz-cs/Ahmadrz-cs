<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class UserCategorisation extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/onboarding/categorisation';

    // This is an APIv1 route, but has APIv2 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function create(array $parameters): ResponseInterface
    {
        return $this->post(self::RESOURCE_PREFIX, $parameters);
    }
}
