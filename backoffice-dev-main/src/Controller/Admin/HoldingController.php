<?php

namespace App\Controller\Admin;

use App\Form\Type\QueryShareholdingType;
use App\Form\Type\QueryShareTradeType;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Repository\OfferingRepository;
use App\Repository\ShareTradeRepository;
use App\Service\Manager\AssetManager;
use App\Service\Manager\AssetManagerV2;
use App\Service\Manager\HoldingManager;
use App\Service\Manager\InvestmentManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\PayoutManagerV2;
use App\Service\Manager\UserManager;
use App\Service\PaymentService;
use App\Service\Util\Helper;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sonata\Exporter\Exporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/holding')]
class HoldingController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private FormFactoryInterface $formFactory,
        private AssetManagerV2 $assetManager,
        private HoldingManager $holdingManager,
        private UserManager $userManager,
        private HoldingRepository $holdingRepository,
        private InvestmentRepository $investmentRepository,
        private OfferingRepository $offeringRepository,
        private PaymentService $paymentService,
        private Exporter $exporter,
        private PayoutManagerV2 $payoutManager,
        private InvestmentManager $investmentManagerLegacy,
    ) {}

    #[Route(path: '', name: 'admin_holding_current')]
    public function current(Request $request)
    {
        $defaults = [
            'currentHolding' => 1,
            'capitalRepayments' => false,
        ];
        $form = $this->createForm(QueryShareholdingType::class, $defaults);
        $form->handleRequest($request);
        $filters = array_merge($defaults, $request->query->all());
        // $this->logger->notice("query params: ", $filters);

        if ($filters['currentHolding'] && $filters['capitalRepayments']) {
            $this->addFlash(
                'info',
                'Don\'t see expected divested shareholders? Use shareholder type "Any" or "Historical"',
            );
        }

        $queryResponse = $this->holdingRepository->getShareHoldings($filters);

        return $this->render('admin/pages/holdings/shareholding.html.twig', [
            'form' => $form->createView(),
            'shareholdings' => $queryResponse,
        ]);
    }

    #[Route(path: '/trades', name: 'admin_holding_trades')]
    public function tradesAction(Request $request)
    {
        $this->logger->info('IN tradesAction');

        $filters = [
            // 'settledFrom' => new \DateTime('first day of this month'),
            'settledTo' => new \DateTime('last day of this month'),
        ];
        $form = $this->createForm(QueryShareTradeType::class, $filters);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData(); // default filters fallback if not valid
        }
        $queryResponse = $this->holdingRepository->getShareTrades($filters);

        $template = 'admin/pages/holdings/sharetrades.html.twig';
        if (!empty($filters['aggregate'])) {
            $template = 'admin/pages/holdings/sharetrades_aggregate.html.twig';
        }
        return $this->render($template, [
            'sharetrades' => $queryResponse,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @deprecated
     */
    #[Route(path: '/trades-custom', name: 'admin_holding_trades_custom')]
    public function tradesCustomAction(Request $request)
    {
        $investments = $this->investmentManagerLegacy->filterShareTrades($request->query->all());

        return $this->render('admin/pages/holdings/sharetrades_custom.html.twig', [
            'sharetrades' => $investments,
        ]);
    }

    #[Route(path: '/summary', name: 'admin_administration_shares_circulation')]
    public function sharesInCirculation(
        AssetManager $assetManager,
        HoldingManager $holdingManager,
        OfferingManager $offeringManager,
        ShareTradeRepository $shareTradeRepository,
    ): Response {
        $this->logger->info('Getting asset shares in circulation summary');
        $shareholdings = $holdingManager->getAssetShareholdings();
        $shareholdings = array_combine(
            array_column($shareholdings, 'assetId'),
            $shareholdings,
        );
        return $this->render('admin/pages/holdings/assetshareholding.html.twig', [
            'assets' => Helper::convertArrayKeysAsIds($assetManager->findBy()),
            'shareholdings' => $shareholdings,
            'externalCommits' => $offeringManager->getAssetsWithExternalCommits(),
            'tradeHoldings' => $shareTradeRepository->aggregateSharesInCirculation(),
        ]);
    }
}
