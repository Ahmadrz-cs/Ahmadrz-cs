<?php

namespace ClientBundle\Service\Yielders;

use Psr\Http\Message\ResponseInterface;

final class GeneratedAssessment extends AbstractApiResource
{
    private const RESOURCE_PREFIX = '/self/onboarding/generated-assessment';

    // This is an APIv1 route, but has APIv2 trappings
    protected string $apiPrefix = '/v1/yielders';

    public function retrieve(): ResponseInterface
    {
        return $this->get(self::RESOURCE_PREFIX);
    }
}
