<?php

namespace ClientBundle\Service;

use AppBundle\Entity\Enum\PayoutType;
use AppBundle\Entity\Enum\TradeStatus;
use AppBundle\Entity\Payout;
use AppBundle\Entity\Portfolio;
use AppBundle\Entity\ShareTrade;
use AppBundle\Entity\TradeOrder;
use ClientBundle\Dto\PayoutQueryDto;
use ClientBundle\Dto\ShareTradeQueryDto;
use ClientBundle\Dto\TradeOrderQueryDto;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PortfolioService
{
    public const int DEFAULT_CACHE_TTL = 300; // Not too long as monthend activity or cancellations
    public const string PORTFOLIO_CACHE_TAG = 'portfolios_front';
    public const string PORTFOLIO_PREFIX_CACHE_TAG = 'portfolio_';

    private ?string $userId = null;

    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private TagAwareCacheInterface $defaultAppCache,
    ) {
        // We'll use the JWT as the identifier for the user
        // This is used for caching
        $userInfo = $this->requestStack->getSession()->get('userInfo', null);
        if (\is_array($userInfo) && \array_key_exists('id', $userInfo)) {
            $this->userId = (string)$userInfo['id'];
        }

        if ($this->userId === null) {
            // Use a placeholder user id that doesn't exist
            $this->userId = '0';
        }
    }

    public function retrievePortfolio(bool $refresh = false): Portfolio
    {
        $cacheKey = hash('xxh128', "portfolio_{$this->userId}");
        $this->logger->debug("Retrieving portfolio", [
            'cacheKey' => $cacheKey,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item): Portfolio {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $response = $this->client->authenticatedUser()->retrievePortfolio();
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not retrieve portfolio" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - summary", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody,
                    Portfolio::class,
                );
            }
        );
    }

    /**
     * @throws NotFoundHttpException
     * @return ShareTrade[]
     */
    public function retrievePortfolioUnsettled(
        bool $currentMonthOnly = false,
        bool $refresh = false,
    ): array {
        $cacheKey = hash('xxh128', "portfolio_unsettled_{$this->userId}");
        $this->logger->debug("Retrieving portfolio unsettled", [
            'cacheKey' => $cacheKey,
            'currentMonthOnly' => $currentMonthOnly,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($currentMonthOnly): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $query = [];
                if ($currentMonthOnly) {
                    $query['currentMonthOnly'] = $currentMonthOnly;
                }
                $response = $this->client->authenticatedUser()->retrievePortfolioUnsettled(['query' => $query]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list portfolio unsettled investments" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - unsettled", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    ShareTrade::class . '[]',
                );
            }
        );
    }

    /**
     * @throws NotFoundHttpException
     * @return TradeOrder[]
     */
    public function retrievePortfolioTradeOrders(
        TradeOrderQueryDto $query = new TradeOrderQueryDto(),
        bool $refresh = false,
    ): array {
        $query->userId = null; // This will get overriden in backoffice with current user
        $query = $this->normalizer->normalize(
            $query,
            context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true],
        );

        $cacheKey = hash('xxh128', "portfolio_trade_orders_{$this->userId}" . json_encode($query));
        $this->logger->debug("Retrieving portfolio trade orders", [
            'cacheKey' => $cacheKey,
            'query' => $query,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($query): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $response = $this->client->authenticatedUser()->retrievePortfolioTradeOrders([
                    'query' => $query,
                ]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list portfolio trade orders" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - trade orders", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    TradeOrder::class . '[]',
                );
            }
        );
    }

    /**
     * @throws NotFoundHttpException
     * @return ShareTrade[]
     */
    public function retrievePortfolioShareTrades(
        ShareTradeQueryDto $query = new ShareTradeQueryDto(),
        bool $refresh = false,
    ): array {
        $query->userId = null; // This will get overriden in backoffice with current user
        $query = $this->normalizer->normalize(
            $query,
            context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true],
        );

        $cacheKey = hash('xxh128', "portfolio_share_trades_{$this->userId}" . json_encode($query));
        $this->logger->debug("Retrieving portfolio share trades", [
            'cacheKey' => $cacheKey,
            'query' => $query,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($query): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $response = $this->client->authenticatedUser()->retrievePortfolioShareTrades([
                    'query' => $query,
                ]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list portfolio share trades" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - share trades", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    ShareTrade::class . '[]',
                );
            }
        );
    }

    public function retrievePortfolioPrefunding(bool $refresh = false): Portfolio
    {
        $cacheKey = hash('xxh128', "portfolio_prefunding_{$this->userId}");
        $this->logger->debug("Retrieving portfolio prefunding orders", [
            'cacheKey' => $cacheKey,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item): Portfolio {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $response = $this->client->authenticatedUser()->retrievePortfolioPrefunding();
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not retrieve portfolio prefunding" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio prefunding');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - prefunding", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody,
                    Portfolio::class,
                );
            }
        );
    }

    /**
     * @throws NotFoundHttpException
     * @return Payout[]
     */
    public function retrievePortfolioDividends(
        PayoutQueryDto $query = new PayoutQueryDto(),
        bool $refresh = false,
    ): array {
        $query->userId = null; // This will get overriden in backoffice with current user
        $query->payoutType = PayoutType::Dividend;
        $query = $this->normalizer->normalize(
            $query,
            context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true],
        );

        $cacheKey = hash('xxh128', "portfolio_dividends_{$this->userId}" . json_encode($query));
        $this->logger->debug("Retrieving portfolio dividends", [
            'cacheKey' => $cacheKey,
            'query' => $query,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($query): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);

                $response = $this->client->authenticatedUser()->retrievePortfolioPayouts([
                    'query' => $query,
                ]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list portfolio trade orders" . $response->getBody());
                    throw new NotFoundHttpException('Unable to load portfolio');
                }
                $responseBody = $this->client->getContent($response);
                // $this->logger->notice("Portfolio - dividends", $responseBody);
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    Payout::class . '[]',
                );
            }
        );
    }

    /**
     * Call this if you expect the portfolio to be updated, e.g. after making an investment
     * Only clears the current user
     */
    public function clearAuthenticatedUserPortfolioCache(): void
    {
        $this->defaultAppCache->invalidateTags([self::PORTFOLIO_PREFIX_CACHE_TAG . $this->userId]);
    }

}
