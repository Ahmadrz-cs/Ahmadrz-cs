<?php

namespace App\Controller\ApiV1;

use App\Dto\Asset\AssetQueryDto;
use App\Dto\TradeOrder\TradeOrderQueryDto;
use App\Entity\Asset;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\Visibility;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\TradeOrderRepository;
use App\Service\Mapper\AssetMapper;
use App\Service\Mapper\TradeOrderMapper;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AssetProductController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
        private TradeOrderRepository $tradeOrderRepository,
        private AssetMapper $assetMapper,
        private TradeOrderMapper $tradeOrderMapper,
        private Security $security,
    ) {}

    /**
     * Note that this is a public route
     */
    #[Route(
        path: '/%api_network_public_path%/featured-products',
        name: 'api_get_featured_asset_products',
        methods: ['GET'],
    )]
    public function listFeaturedProducts(NormalizerInterface $normalizer): Response
    {
        $this->logger->debug('List public featured asset products');

        $assets = $this->assetRepository->findWithAssociations([
            'featured' => 1,
            'visibility' => Visibility::Auto->toInt(),
            'status' => [AssetStatus::Active, AssetStatus::Closing],
        ], ['createdAt' => 'DESC']);
        $dto = $this->assetMapper->mapMultipleToDto($assets);
        return $this->json(['data' => $dto]);
    }

    #[Route(
        path: '/%api_network_path%/asset-products',
        name: 'api_get_asset_products',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    public function listAssetProducts(
        #[MapQueryString]
        AssetQueryDto $dto,
        NormalizerInterface $normalizer,
    ): Response {
        $this->logger->debug('List asset products');

        $filters = $normalizer->normalize($dto);

        // if (
        //     $dto->visibility == Visibility::Admin
        //     && !$this->security->isGranted('ROLE_ANALYST')
        // ) {
        //     $filters['visibility'] = Visibility::Auto->toInt();
        // } else {
        //     $filters['visibility'] = $dto->visibility->toInt();
        // }
        $filters['visibility'] = [Visibility::Auto->toInt(), Visibility::Vip->toInt()];
        if ($this->security->isGranted('ROLE_ANALYST')) {
            $filters['visibility'][] = Visibility::Admin->toInt();
        }

        // $this->logger->debug('Asset product API filters', $filters);

        $assets = $this->assetRepository->findWithAssociations($filters, [
            'createdAt' => 'DESC',
        ]);
        $dto = $this->assetMapper->mapMultipleToDto($assets);
        return $this->json(['data' => $dto]);
    }

    #[Route(
        path: '/%api_network_path%/asset-products/{id}',
        name: 'api_get_asset_product_single',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OAUTH2_ASSET:READ')]
    public function retrieveAssetProduct(#[MapEntity(id: 'id')] Asset $asset): Response
    {
        $this->logger->debug('Retrieve asset product', ['id' => $asset->getId()]);

        if (
            (
                $asset->getVisibility() == Visibility::Admin->toInt()
                || !in_array($asset->getCurrentStatus(), AssetStatus::publicCases())
            )
            && !$this->security->isGranted('ROLE_ANALYST')
        ) {
            throw new AccessDeniedHttpException('Asset not available to investors.');
        }

        $dto = $this->assetMapper->mapToDto($asset);
        return $this->json($dto);
    }

    #[Route(
        path: '/%api_network_path%/asset-products/{id}/sell-orders',
        name: 'api_get_asset_products_sell_orders',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OAUTH2_OFFERING:READ')]
    public function listAssetSellOrders(
        #[CurrentUser]
        User $currentUser,
        #[MapEntity(id: 'id')]
        Asset $asset,
        #[MapQueryString]
        TradeOrderQueryDto $dto,
        NormalizerInterface $normalizer,
    ): Response {
        $this->logger->debug('List asset sell orders');

        $filters = $normalizer->normalize($dto);

        $filters['assetId'] = $asset->getId();
        $filters['direction'] = TradeDirection::Sell;
        $filters['excludeUserId'] = $dto->excludeOwn ? $currentUser->getId() : null;

        $tradeOrders = $this->tradeOrderRepository->findWithAssociations($filters, [
            'createdAt' => 'ASC',
        ]);
        $this->logger->debug('Asset sell orders API filters', [
            'filters' => $filters,
            'results' => count($tradeOrders),
        ]);
        $dto = $this->tradeOrderMapper->mapMultipleToDto($tradeOrders);
        return $this->json(['data' => $dto]);
    }
}
