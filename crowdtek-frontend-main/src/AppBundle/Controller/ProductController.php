<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\Enum\Visibility;
use AppBundle\Entity\TradeOrder;
use AppBundle\Form\InvestmentRetailType;
use ClientBundle\Dto\AssetQueryDto;
use AppBundle\Form\QueryProductType;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\InvestmentServiceV2;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Successor to OpportunityController
 */
class ProductController extends AbstractController
{
    private array $user = [];

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private AssetProductService $assetProductService,
        private OnboardingService $onboardingService,
        private InvestmentServiceV2 $investmentService,
        private UserService $userService,
        private NormalizerInterface $normalizer,
    ) {
        // $this->logger->debug("IN ProductController constructor");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
        $this->user = $this->requestStack->getSession()->get('userInfo');
    }

    #[Route(path: '/current-properties', name: 'product_properties_current', methods: ['GET'])]
    public function propertiesCurrent(Request $request): Response
    {
        $this->logger->debug("IN ProductController->propertiesCurrent");

        $filters = new AssetQueryDto();
        // $form = $this->createForm(QueryProductType::class, $filters);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $filters = $form->getData();
        // }

        $assets = [];
        try {
            $assets = $this->assetProductService->getAssetProducts($this->normalizer->normalize($filters, 'json'));
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load assets");
        }

        return $this->render('@AppBundle/Product/list_products.html.twig', [
            'assets' => $assets,
            // 'form' => $form,
        ]);
    }

    #[Route(path: '/archived-properties', name: 'product_properties_archived', methods: ['GET'])]
    public function propertiesArchived(Request $request): Response
    {
        $this->logger->debug("IN ProductController->propertiesArchived");

        $filters = new AssetQueryDto(
            status: [AssetStatus::Archived],
        );

        $assets = [];
        try {
            $assets = $this->assetProductService->getAssetProducts($this->normalizer->normalize($filters, 'json'));
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load assets");
        }

        return $this->render('@AppBundle/Product/list_archived.html.twig', [
            'assets' => $assets,
            // 'form' => $form,
        ]);
    }

    #[Route(path: '/prefunding-properties', name: 'product_properties_prefunding', methods: ['GET'])]
    public function propertiesPrefunding(Request $request): Response
    {
        $this->logger->debug("IN ProductController->propertiesPrefunding");

        $filters = new AssetQueryDto(
            status: [AssetStatus::Acquiring],
            visibility: Visibility::Vip,
        );
        // $form = $this->createForm(QueryProductType::class, $filters);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $filters = $form->getData();
        // }

        $assets = [];
        try {
            $assets = $this->assetProductService->getAssetProducts($this->normalizer->normalize($filters, 'json'));
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load assets");
        }

        return $this->render('@AppBundle/Product/list_prefunding.html.twig', [
            'assets' => $assets,
            // 'form' => $form,
        ]);
    }

    #[Route(path: '/properties/{id}', name: 'product_properties_detail', methods: ['GET'])]
    public function propertyDetail(Request $request, int $id): Response
    {
        $this->logger->debug("IN ProductController->propertiesDetails");

        $asset = null;
        $sellOrders = [];
        try {
            $asset = $this->assetProductService->getSingleAssetProduct($id);
            $filters = [
                'status' => [TradeOrderStatus::Active],
                'type' => [TradeOrderType::Initial, TradeOrderType::Market],
            ];
            $sellOrders = $this->assetProductService->getAssetProductsListings($id, $filters);
            $userId = (\is_array($this->user) && \array_key_exists('id', $this->user))
                ? $this->user['id']
                : 0;
            $sellOrders = array_filter(
                $sellOrders,
                fn(TradeOrder $item): bool => $item->userId != $userId,
            );
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load asset", ['id' => $id]);
            $this->addFlash('error', 'Unable to get requested asset');
            return $this->redirectToRoute('product_properties_current');
        }

        $selectedUuid = $request->query->get('selected', null);
        $selected = array_find($sellOrders, fn(TradeOrder $item): bool => $item->uuid == $selectedUuid);
        if ($selectedUuid === null) {
            $selected = array_first($sellOrders);
        }

        try {
            $isAllowedToInvest = $this->investmentService->checkUserCanInvest();
            $this->logger->debug("can invest");
        } catch (\Throwable $th) {
            $isAllowedToInvest = false;
            $this->logger->debug("cannot invest" . $th->getMessage());
        }

        return $this->render('@AppBundle/Product/view_product.html.twig', [
            'asset' => $asset,
            'sellOrders' => $sellOrders,
            'selectedOrder' => $selected,
            'onboardingProfile' => $this->onboardingService->getOnboardingProfileFromSession(),
            'isAllowedToInvest' => $isAllowedToInvest,
        ]);
    }
}
