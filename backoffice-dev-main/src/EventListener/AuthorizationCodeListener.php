<?php

namespace App\EventListener;

use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthorizationCodeListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
    ) {}

    public function onAuthorizationRequestResolve(AuthorizationRequestResolveEvent $event): void
    {
        $this->logger->debug('Resolving authorization code request');

        if (null !== $event->getUser()) {
            $this->logger->debug('Found user');
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
        } else {
            $this->logger->debug('User not authenticated');
            $event->setResponse(new Response('', 401));

            /**
             * Use request stack and manually redirect if doing authentication handling ourselves
             */
            // $event->setResponse(
            //     new Response(
            //         302,
            //         [
            //             'Location' => $this->urlGenerator->generate(
            //                 'oauth2_login',
            //                 [
            //                     'returnUrl' => $this->requestStack->getMasterRequest()->getUri(),
            //                 ]
            //             ),
            //         ]
            //     )
            // );
        }
    }
}
