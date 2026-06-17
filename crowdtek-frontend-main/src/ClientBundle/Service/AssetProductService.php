<?php

namespace ClientBundle\Service;

use AppBundle\Entity\AssetProduct;
use AppBundle\Entity\TradeOrder;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AssetProductService
{
    /**
     * Adjust cache ttl as needed
     * - Static 60s is good enough to handle reduce API queries without heavily impacting data freshness
     * - If dynamically clearing the cache (using the tag) after investments, can use longer ttl
     * - Featured ttl can be longer as it's as common to make changes to that
     */
    public const int DEFAULT_CACHE_TTL = 60;
    public const int FEATURED_CACHE_TTL = 600;
    public const string ASSET_PRODUCT_CACHE_TAG = 'asset_products_query';
    public const string ASSET_PRODUCT_PREFIX_CACHE_TAG = 'asset_products_';
    public const string ASSET_PRODUCT_SELL_ORDER_CACHE_TAG = 'asset_products_sell_orders';

    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        // private RequestStack $requestStack, // if session is required
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private TagAwareCacheInterface $defaultAppCache,
    ) {}

    /**
     * @return AssetProduct[]
     */
    public function getAssetProducts(array $filters = [], bool $refresh = false): ?array
    {
        $cacheKey = hash('xxh128', 'asset_products_default' . json_encode($filters));
        $this->logger->debug("Retrieving asset products", [
            'cacheKey' => $cacheKey,
            'query' => $filters,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($filters): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::ASSET_PRODUCT_CACHE_TAG]);

                $response = $this->client->assetProduct()->all(['query' => $filters]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list asset products" . $response->getBody());
                    throw new NotFoundHttpException("Unable to load asset products. Response code: {$response->getStatusCode()}");
                }
                $responseBody = $this->client->getContent($response);
                if (!array_key_exists('data', $responseBody)) {
                    throw new NotFoundHttpException('Unable to load asset products. No "data" property in response.');
                }
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    AssetProduct::class . '[]',
                );
            }
        );
    }

    public function getSingleAssetProduct(int $id, bool $refresh = false): ?AssetProduct
    {
        $cacheKey = hash('xxh128', "asset_products_default_{$id}");
        $this->logger->debug("Retrieving single asset product", [
            'assetId' => $id,
            'cacheKey' => $cacheKey,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($id): ?AssetProduct {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::ASSET_PRODUCT_PREFIX_CACHE_TAG . $id]);

                $response = $this->client->assetProduct()->retrieve($id);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not retrieve asset product" . $response->getBody());
                    throw new NotFoundHttpException("Unable to load asset product. Response code: {$response->getStatusCode()}");
                }
                $responseBody = $this->client->getContent($response);
                return $this->denormalizer->denormalize(
                    $responseBody,
                    AssetProduct::class,
                );
            }
        );
    }

    /**
     * @return AssetProduct[]
     */
    public function getPublicFeaturedProducts(array $filters = [], bool $refresh = false): ?array
    {
        $cacheKey = hash('xxh128', 'asset_products_public_featured' . json_encode($filters));
        $this->logger->debug("Retrieving featured asset products", [
            'cacheKey' => $cacheKey,
            'query' => $filters,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($filters): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::FEATURED_CACHE_TTL);
                $item->tag([self::ASSET_PRODUCT_CACHE_TAG]);

                $response = $this->client->assetProduct()->allPublicFeatured(['query' => $filters]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list asset products" . $response->getBody());
                    throw new NotFoundHttpException("Unable to load public featured asset products. Response code: {$response->getStatusCode()}");
                }
                $responseBody = $this->client->getContent($response);
                if (!array_key_exists('data', $responseBody)) {
                    throw new NotFoundHttpException('Unable to load public featured asset products. No "data" property in response.');
                }
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    AssetProduct::class . '[]',
                );
            }
        );
    }

    /**
     * @return TradeOrder[]
     */
    public function getAssetProductsListings(int $id, array $filters = [], bool $refresh = false): ?array
    {
        $cacheKey = hash('xxh128', "asset_products_{$id}_sell_orders" . json_encode($filters));
        $this->logger->debug("Retrieving asset sell orders", [
            'assetId' => $id,
            'cacheKey' => $cacheKey,
            'query' => $filters,
            'forceRefresh' => $refresh,
        ]);
        if ($refresh) {
            $this->defaultAppCache->delete($cacheKey);
        }
        return $this->defaultAppCache->get(
            $cacheKey,
            function (ItemInterface $item) use ($filters, $id): array {
                $this->logger->debug("Cache miss, retrieving results from API");
                $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                $item->tag([self::ASSET_PRODUCT_PREFIX_CACHE_TAG . $id]);

                $response = $this->client->assetProduct()->retrieveSellOrders($id, ['query' => $filters]);
                if (200 !== $response->getStatusCode()) {
                    $this->logger->debug("Could not list asset products" . $response->getBody());
                    throw new NotFoundHttpException("Unable to load asset products sell orders. Response code: {$response->getStatusCode()}");
                }
                $responseBody = $this->client->getContent($response);
                if (!array_key_exists('data', $responseBody)) {
                    throw new NotFoundHttpException('Unable to load asset products sell orders. No "data" property in response.');
                }
                return $this->denormalizer->denormalize(
                    $responseBody['data'],
                    TradeOrder::class . '[]',
                );
            }
        );
    }

    public function clearSingleAssetProductCache(int $id): void
    {
        $this->defaultAppCache->invalidateTags([self::ASSET_PRODUCT_PREFIX_CACHE_TAG . $id]);
    }
}
