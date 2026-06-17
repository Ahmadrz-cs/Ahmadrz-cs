<?php

namespace App\Controller\Admin;

use App\Entity\Asset;
use App\Entity\Document;
use App\Entity\Enum\ProductDocumentType;
use App\Entity\Enum\ProductMode;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\User;
use App\Form\Type\AddressCreateType;
use App\Form\Type\AssetFinancialType;
use App\Form\Type\AssetTradingControlType;
use App\Form\Type\ProductAboutType;
use App\Form\Type\ProductDocumentCreateType;
use App\Form\Type\ProductLaunchType;
use App\Form\Type\ProductWalletType;
use App\Repository\TradeOrderRepository;
use App\Service\AssetService;
use App\Service\DivestmentService;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\OfferingManagerV2;
use App\Service\ProductService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/products/{id}/editor')]
#[IsGranted('ROLE_OPERATIONS')]
class ProductEditorController extends AbstractController
{
    public const SETUP_FLOW = [
        'admin_product_edit_about' => 'admin_product_edit_location',
        'admin_product_edit_location' => 'admin_product_edit_financials',
        'admin_product_edit_financials' => 'admin_product_edit_investment_rules',
        'admin_product_edit_investment_rules' => 'admin_product_edit_wallets',
        'admin_product_edit_wallets' => 'admin_product_edit_documents',
        'admin_product_edit_documents_create' => 'admin_product_edit_documents',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private ProductService $productService,
        private OfferingManagerV2 $offeringManager,
        private DocumentManager $documentManager,
        private AssetService $assetService,
        private DivestmentService $divestmentService,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    #[Route('/wallets', name: 'admin_product_edit_wallets', methods: ['GET', 'POST'])]
    public function wallets(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(ProductWalletType::class, $asset);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Product wallets successfully updated');
            if ($request->query->get('setup')) {
                // This looks redundant, but it accommodates changes to the setup flow
                // Although would still need to change the id parameter if necessary
                return $this->redirectToRoute(
                    $this::SETUP_FLOW[$request->attributes->get('_route')],
                    [
                        'id' => $asset->getId(),
                        'setup' => 1,
                    ],
                );
            }
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/wallets.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/about', name: 'admin_product_edit_about', methods: ['GET', 'POST'])]
    public function about(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(ProductAboutType::class, $asset);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->setCommonFields($asset);
            $this->addFlash('success', 'Product info successfully updated.');
            if ($request->query->get('setup')) {
                // if redoing setup from scratch, setup defaults again to fill in any gaps
                $this->productService->fillDefaults($asset);
                $this->doctrine->getManager()->flush();
                return $this->redirectToRoute(
                    $this::SETUP_FLOW[$request->attributes->get('_route')],
                    [
                        'id' => $asset->getId(),
                        'setup' => 1,
                    ],
                );
            }
            $this->doctrine->getManager()->flush();
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/about.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/location', name: 'admin_product_edit_location', methods: ['GET', 'POST'])]
    public function location(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(AddressCreateType::class, $asset->getMainAddress(), [
            'required' => false,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->setCommonFields($asset);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Product location successfully updated.');
            if ($request->query->get('setup')) {
                return $this->redirectToRoute(
                    $this::SETUP_FLOW[$request->attributes->get('_route')],
                    [
                        'id' => $asset->getId(),
                        'setup' => 1,
                    ],
                );
            }
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/location.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/financials',
        name: 'admin_product_edit_financials',
        methods: ['GET', 'POST'],
    )]
    public function financials(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(AssetFinancialType::class, $asset);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->productService->setCommonFields($asset);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Product financials successfully updated.');
            if ($request->query->get('setup')) {
                return $this->redirectToRoute(
                    $this::SETUP_FLOW[$request->attributes->get('_route')],
                    [
                        'id' => $asset->getId(),
                        'setup' => 1,
                    ],
                );
            }
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/financials.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/rules',
        name: 'admin_product_edit_investment_rules',
        methods: ['GET', 'POST'],
    )]
    public function rules(Request $request, Asset $asset): Response
    {
        // $form = $this->createForm(ProductRulesType::class, $asset);
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     // $this->offeringManager->roundMinMaxCommit($offering);
        //     $this->doctrine->getManager()->flush();
        //     $this->addFlash('success', 'Product trading rules successfully updated');
        //     if ($request->query->get('setup')) {
        //         return $this->redirectToRoute(
        //             $this::SETUP_FLOW[$request->attributes->get('_route')],
        //             [
        //                 'id' => $asset->getId(),
        //                 'setup' => 1,
        //             ],
        //         );
        //     }
        //     return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        // }

        // return $this->render('admin/pages/products/editor/rules.html.twig', [
        //     'asset' => $asset,
        //     'form' => $form->createView(),
        // ]);
        $this->addFlash('info', 'Investment rules are now configured at launch');
        if ($request->query->get('setup')) {
            return $this->redirectToRoute(
                $this::SETUP_FLOW[$request->attributes->get('_route')],
                [
                    'id' => $asset->getId(),
                    'setup' => 1,
                ],
            );
        }
        return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
    }

    #[Route('/status', name: 'admin_product_edit_status', methods: ['GET', 'POST'])]
    public function status(Request $request, Asset $asset): Response
    {
        $form = $this->createForm(AssetTradingControlType::class, $asset);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash(
                'success',
                'Product trading controls successfully updated.',
            );
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/status.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'alreadyLaunched' => $this->productService->isAlreadyLaunched($asset),
            'launchReady' => $this->productService->isLaunchReady($asset),
        ]);
    }

    #[Route(
        '/status/toggle-buying',
        name: 'admin_product_edit_toggle_buying',
        methods: ['GET'],
    )]
    public function toggleBuying(Asset $asset): Response
    {
        $asset->setBuyRestricted(!$asset->isBuyRestricted());
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', 'Successfully toggled trading controls for buying');
        $this->addFlash(
            'notice',
            'Consider sending out a comunication to investors about this change and why.',
        );
        return $this->redirectToRoute('admin_product_edit_status', ['id' => $asset->getId()]);
    }

    #[Route(
        '/status/toggle-selling',
        name: 'admin_product_edit_toggle_selling',
        methods: ['GET'],
    )]
    public function toggleSelling(Asset $asset): Response
    {
        $asset->setSellRestricted(!$asset->isSellRestricted());
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', 'Successfully toggled trading controls for selling');
        $this->addFlash(
            'notice',
            'Consider sending out a comunication to investors about this change and why.',
        );
        return $this->redirectToRoute('admin_product_edit_status', ['id' => $asset->getId()]);
    }

    #[Route(
        '/status/toggle-featured',
        name: 'admin_product_edit_toggle_featured',
        methods: ['GET'],
    )]
    public function toggleFeatured(Asset $asset): Response
    {
        $asset->setFeatured((int) !$asset->getFeatured());
        $this->doctrine->getManager()->flush();
        $this->addFlash(
            'success',
            'Successfully toggled featured. To specify the weighting, edit the asset directly.',
        );
        return $this->redirectToRoute('admin_product_edit_status', ['id' => $asset->getId()]);
    }

    #[Route(
        '/status/toggle-visibility',
        name: 'admin_product_edit_toggle_visibility',
        methods: ['GET'],
    )]
    public function toggleVisibility(Asset $asset): Response
    {
        $this->productService->toggleVisibility($asset);
        $this->doctrine->getManager()->flush();
        $this->addFlash('success', 'Successfully toggled visibility status');
        return $this->redirectToRoute('admin_product_edit_status', ['id' => $asset->getId()]);
    }

    #[Route('/documents', name: 'admin_product_edit_documents', methods: ['GET'])]
    public function documents(Asset $asset): Response
    {
        return $this->render('admin/pages/products/editor/documents.html.twig', [
            'asset' => $asset,
            'sortedDocs' => $this->productService->sortDocuments($asset),
        ]);
    }

    #[Route(
        '/documents/create',
        name: 'admin_product_edit_documents_create',
        methods: ['GET', 'POST'],
    )]
    public function addDocument(Request $request, Asset $asset): Response
    {
        if (!ProductDocumentType::tryFrom($request->query->get('type'))) {
            $this->addFlash(
                'warning',
                'Unknown document type. Try one of the links below',
            );
            return $this->redirectToRoute('admin_product_edit_documents', ['id' => $asset->getId()]);
        }
        $form = $this->createForm(ProductDocumentCreateType::class, [
            'document' => new Document(),
            'type' => ProductDocumentType::from($request->query->get('type')),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $relationalDoc = $this->productService->createRelationalDocument(
                $form->get('type')->getData(),
                $asset,
                $form->get('document')->getData(),
            );
            $document = $this->documentManager->linkDocument(
                $relationalDoc->getDocument(),
                $relationalDoc->getDocument()->getFile(),
                'public',
                "asset/{$asset->getId()}",
            );
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $document->setCreatedById($currentUser->getId());
            $this->logger->notice('form contents', $form->getData());
            $this->doctrine->getManager()->persist($relationalDoc);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Product document successfully created');
            if ($request->query->get('setup')) {
                return $this->redirectToRoute(
                    $this::SETUP_FLOW[$request->attributes->get('_route')],
                    [
                        'id' => $asset->getId(),
                        'setup' => 1,
                    ],
                );
            }
            return $this->redirectToRoute('admin_product_edit_documents', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/add_document.html.twig', [
            'asset' => $asset,
            'sortedDocs' => $this->productService->sortDocuments($asset),
            'form' => $form->createView(),
        ]);
    }

    #[Route('/launch', name: 'admin_product_edit_launch', methods: ['GET'])]
    public function launchCentre(Asset $asset): Response
    {
        return $this->render('admin/pages/products/editor/launch.html.twig', [
            'asset' => $asset,
            'alreadyLaunched' => $this->productService->isAlreadyLaunched($asset),
            'launchReady' => $this->productService->isLaunchReady($asset),
        ]);
    }

    #[Route(
        '/launch-prefunding',
        name: 'admin_product_edit_launch_prefunding',
        methods: ['GET', 'POST'],
    )]
    public function launchPrefunding(Request $request, Asset $asset): Response
    {
        if ($this->productService->isAlreadyLaunched($asset)) {
            $this->addFlash(
                'warning',
                'Product has already launched and cannot be launched for prefunding again',
            );
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        $initialTradeOrders = $this->tradeOrderRepository->findWithAssociations([
            'direction' => TradeDirection::Sell,
            'type' => TradeOrderType::Initial,
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Draft, TradeOrderStatus::Submitted],
        ], ['createdAt' => 'DESC']);
        $this->logger->debug(
            'Count existing sell orders ' . count($initialTradeOrders),
        );
        $form = $this->createForm(ProductLaunchType::class, [
            'pricePerShare' => $asset->getPricePerShare(),
            'numberOfShares' => $asset->getAmountOfShares(),
            'minimumInvestment' => (string) min('25000', $asset->getFundingGoal()),
            'maximumInvestment' => $asset->getFundingGoal(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->productService->isLaunchReady($asset)) {
                $tradeOrder = $this->productService->prepareLaunchTradeOrder(
                    asset: $asset,
                    pricePerShare: $form->getData()['pricePerShare'],
                    numberOfShares: $form->getData()['numberOfShares'],
                    minCommit: $form->getData()['minimumInvestment'],
                    maxCommit: $form->getData()['maximumInvestment'],
                    tradeOrder: empty($initialTradeOrders)
                        ? null
                        : $initialTradeOrders[0],
                    mode: ProductMode::Prefunding,
                );
                $this->productService->launchProduct($asset, ProductMode::Prefunding);
                $this->doctrine->getManager()->persist($tradeOrder);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Product successfully launched for prefunding',
                );
            } else {
                $this->addFlash(
                    'warning',
                    'Product is not ready for launch. Ensure all missing data points are completed',
                );
            }
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/launch_config.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'alreadyLaunched' => $this->productService->isAlreadyLaunched($asset),
            'launchReady' => $this->productService->isLaunchReady($asset),
            'tradeOrders' => $initialTradeOrders,
            'mode' => ProductMode::Prefunding->value,
        ]);
    }

    #[Route(
        '/launch-retail',
        name: 'admin_product_edit_launch_retail',
        methods: ['GET', 'POST'],
    )]
    public function launchRetail(Request $request, Asset $asset): Response
    {
        if ($this->productService->isAlreadyLaunched($asset, ProductMode::Retail)) {
            $this->addFlash(
                'warning',
                'Product has already launched to retail and cannot be launched again',
            );
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        $initialTradeOrders = $this->tradeOrderRepository->findWithAssociations([
            'direction' => TradeDirection::Sell,
            'type' => TradeOrderType::Initial,
            'assetId' => $asset->getId(),
            'status' => [TradeOrderStatus::Draft, TradeOrderStatus::Submitted],
        ], ['createdAt' => 'DESC']);
        $prefunderSellOrders = $this->tradeOrderRepository
            ->buildQueryWithAssociations([
                'assetId' => $asset->getId(),
                'status' => [TradeOrderStatus::Active],
                'type' => TradeOrderType::Prefunding,
                'direction' => TradeDirection::Sell,
            ], ['numberOfShares' => 'ASC'])
            ->getResult();
        $repaymentSummary =
            $this->divestmentService->compileRepaymentProgress($prefunderSellOrders);
        $sharesToSell = array_sum(array_column($repaymentSummary, 'shares'));
        if ($sharesToSell <= 0) {
            $sharesToSell = $asset->getAmountOfShares();
        }
        $form = $this->createForm(ProductLaunchType::class, [
            'pricePerShare' => $asset->getPricePerShare(),
            'numberOfShares' => $sharesToSell,
            'minimumInvestment' => $asset->getMinimumInvestment() ?? '100',
            'maximumInvestment' => $asset->getFundingGoal(),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->productService->isLaunchReady($asset)) {
                $tradeOrder = $this->productService->prepareLaunchTradeOrder(
                    asset: $asset,
                    pricePerShare: $form->getData()['pricePerShare'],
                    numberOfShares: $form->getData()['numberOfShares'],
                    minCommit: $form->getData()['minimumInvestment'],
                    maxCommit: $form->getData()['maximumInvestment'],
                    tradeOrder: empty($initialTradeOrders)
                        ? null
                        : $initialTradeOrders[0],
                    mode: ProductMode::Retail,
                );
                $this->productService->launchProduct($asset, ProductMode::Retail);

                $this->doctrine->getManager()->persist($tradeOrder);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Product successfully launched for prefunding',
                );
            } else {
                $this->addFlash(
                    'warning',
                    'Product is not ready for launch. Ensure all missing data points are completed',
                );
            }
            return $this->redirectToRoute('admin_product_dashboard', ['id' => $asset->getId()]);
        }
        return $this->render('admin/pages/products/editor/launch_config.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'alreadyLaunched' => $this->productService->isAlreadyLaunched($asset),
            'launchReady' => $this->productService->isLaunchReady($asset),
            'tradeOrders' => $initialTradeOrders,
            'repaymentSummary' => $repaymentSummary,
            'mode' => ProductMode::Retail->value,
        ]);
    }
}
