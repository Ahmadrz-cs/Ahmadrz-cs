<?php

namespace AppBundle\Listener;

use ClientBundle\Service\CrowdTekClient;
use ClientBundle\Service\PublicService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Token\RegisteredClaims;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SessionExpirySubscriber implements EventSubscriberInterface
{
    /**
     * REFRESH_WINDOW: time in seconds before/after expiry to refresh
     * We allow refresh AFTER expiry if within this window
     * This represents an idle window
     * We can eventually switch to using garbage collection for idle timeout
     * https://symfony.com/doc/3.4/components/http_foundation/session_configuration.html#session-idle-time-keep-alive
     */
    public const REFRESH_WINDOW = 600; // 10 minute window - access_token ttl is 1 hour set by backoffice


    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private CrowdTekClient $crowdtekClient,
        private PublicService $publicService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        // $request   = $event->getRequest();
        // $this->logger->info("==================IN SessionExpirySubscriber=====================");

        $accessToken = $this->requestStack->getSession()->get('jwt_token');

        /**
         * if access token exists, user was logged in and we can do some session management
         * public users are not affected
         */
        if ($accessToken) {
            /** @var \Lcobucci\JWT\Token\Plain $token */
            $token = (new Parser(new JoseEncoder()))->parse((string) $accessToken);
            $accessTokenExpiry = $token->claims()->get(RegisteredClaims::EXPIRATION_TIME);
            if ($accessTokenExpiry instanceof \DateTimeInterface) {
                $accessTokenExpiry = $accessTokenExpiry->getTimestamp();
            }
            $accessTokenTtl = $accessTokenExpiry - time();


            // prototype extras - set the ttl in session to display in templates
            $this->requestStack->getSession()->set('token_time_remaining', (string) $accessTokenTtl);
            // $this->logger->info("Access token ttl: " . (string) $accessTokenTtl);

            /**
             * attempt refresh if refresh_token exists
             * accept expired access token if it within REFRESH_WINDOW of expiry, e.g. accessTokenTtl == -90
             */
            if ($this->requestStack->getSession()->get('refresh_token') && abs($accessTokenTtl) < self::REFRESH_WINDOW) {
                try {
                    $this->crowdtekClient->refreshAccessToken();
                    $this->logger->info("Access token ttl: " . (string) $accessTokenTtl);
                    // update access token info
                    $accessToken = $this->requestStack->getSession()->get('jwt_token');
                    /** @var \Lcobucci\JWT\Token\Plain $token */
                    $token = (new Parser(new JoseEncoder()))->parse((string) $accessToken);
                    $accessTokenExpiry = $token->claims()->get(RegisteredClaims::EXPIRATION_TIME);
                    if ($accessTokenExpiry instanceof \DateTimeInterface) {
                        $accessTokenExpiry = $accessTokenExpiry->getTimestamp();
                    }
                    $accessTokenTtl = $accessTokenExpiry - time();
                    $this->requestStack->getSession()->set('token_time_remaining', (string) $accessTokenTtl);

                    // // prototype extras
                    // $this->requestStack->getSession()->getFlashBag()->add('info', 'Just refreshed the access token!');
                } catch (\Exception $th) {
                    // Issuing refreshing the token, so set the token time-to-live (TTL) to 0 so user is logged out in next step
                    $accessTokenTtl = 0;
                }
            }

            // if access token not refreshed and now expired, invalidate session
            if ($accessTokenTtl <= 0) {
                // // prototype extras
                // $this->requestStack->getSession()->getFlashBag()->add('info', 'Your session has timed out. Please log back in.');

                $this->requestStack->getSession()->set('authenticated', false);
                $this->tokenStorage->setToken(null);
                $this->requestStack->getSession()->invalidate();

                // send users back to login
                $event->setResponse(new RedirectResponse($this->publicService->getOauthLogoutUrl()));
            }
        }
    }
}
