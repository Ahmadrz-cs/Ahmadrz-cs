<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class OnboardingProfile extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/onboarding/profile';

    // This is an APIv1 route, but has APIv2 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function retrieve(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }

    public function update(array $parameters): ResponseInterface
    {
        return $this->patch(self::RESOURCE_PREFIX, $parameters);
    }
}
