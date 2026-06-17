<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;

class SessionIdleHandler
{
    public function __construct(
        private RouterInterface $router,
        private LoggerInterface $logger,
        private int $maxIdleTime = 3600,
    ) {}

    public function onKernelRequest(RequestEvent $event): ?Response
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return null;
        }

        if ($this->maxIdleTime > 0) {
            $event->getRequest()->getSession()->start();
            $timeSinceLastUsed =
                time()
                - $event->getRequest()->getSession()->getMetadataBag()->getLastUsed();
            // $this->logger->debug("Last activity {$timeSinceLastUsed}s ago");
            if ($timeSinceLastUsed > $this->maxIdleTime) {
                // Don't need to use invalidate on session directly
                // Will be done as part of the logout process
                // $event->getRequest()->getSession()->invalidate();
                $event->setResponse(new RedirectResponse($this->router->generate(
                    'app_logout',
                )));
            }
        }
        return null;
    }
}
