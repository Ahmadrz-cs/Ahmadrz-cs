<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\TradeDirection;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\PortfolioPosition;
use AppBundle\Entity\ScaAction;
use AppBundle\Entity\TradeOrder;
use AppBundle\Form\InvestmentRetailType;
use AppBundle\Form\PrefundingType;
use AppBundle\Form\RelistingType;
use AppBundle\Util\Fees;
use ClientBundle\Dto\TradeOrderQueryDto;
use ClientBundle\Exception\InvestmentNotAllowedException;
use ClientBundle\Exception\RelistingNotAllowedException;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\InvestmentServiceV2;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PortfolioService;
use ClientBundle\Service\RelistingService;
use ClientBundle\Service\ScaService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RelistingController extends AbstractController
{
    private array $user = [];
    private bool $isVip = false;

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private AssetProductService $assetProductService,
        private OnboardingService $onboardingService,
        private InvestmentServiceV2 $investmentService,
        private ScaService $scaService,
        private UserService $userService,
        private PortfolioService $portfolioService,
        private RelistingService $relistingService,
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
        $this->user = $this->requestStack->getSession()->get('userInfo');
        $this->isVip = \array_key_exists('is_vip', $this->user) && $this->user['is_vip'];
    }

    #[Route(path: '/properties/{id}/sell', name: 'product_properties_sell', methods: ['GET', 'POST'])]
    public function propertySell(Request $request, int|string $id): Response
    {
        $this->logger->debug("IN ProductController->propertySell");

        $asset = null;
        $sellOrders = [];
        try {
            // Check 1: Are there any shares to sell?
            $portfolioSummary = $this->portfolioService->retrievePortfolio();
            $position = array_find(
                $portfolioSummary->positions,
                fn(PortfolioPosition|array $item): bool => ($item instanceof PortfolioPosition ? $item->assetId : $item['assetId']) == $id,
            );
            $this->logger->debug("position type: " . gettype($position));
            $sharesAvailable = 0;
            if ($position instanceof PortfolioPosition) {
                $sharesAvailable = $position->sharesAvailable;
            } elseif (is_array($position) && array_key_exists("sharesAvailable", $position)) {
                $sharesAvailable = $position["sharesAvailable"];
            }

            if ($sharesAvailable <= 0) {
                $this->logger->debug("User has no shares to sell in asset", ["assetId" => $id]);
                $this->addFlash("error", "No shares to sell in this asset");
                return $this->redirectToRoute('my_portfolio');
            }

            // Gather info for further checks
            $asset = $this->assetProductService->getSingleAssetProduct($id);
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            $userId = (\is_array($userInfo) && \array_key_exists('id', $userInfo))
                ? $userInfo['id']
                : 0;
            $sellOrders = array_filter(
                $this->portfolioService->retrievePortfolioTradeOrders(new TradeOrderQueryDto(
                    direction: TradeDirection::Sell,
                    status: TradeOrderStatus::openStates(),
                    type: [TradeOrderType::Market],
                    createdAt_gte: new \DateTime("midnight first day of this month"),
                    createdAt_lt: new \DateTime("midnight first day of next month"),
                )),
                fn(TradeOrder $item): bool => $item->assetId == $id,
            );
            $valueListedThisMonth = (string)array_reduce(
                $sellOrders,
                fn(?float $total, TradeOrder $item) => $total +=
                ($item->pricePerShare * $item->numberOfShares),
                0,
            );
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load asset and portfolio info for relisting", ['assetId' => $id]);
            $this->addFlash('error', 'Unable to get asset and portfolio info');
            return $this->redirectToRoute('my_portfolio');
        }

        // Check 2: Is the asset open for selling
        if ($asset->sellRestricted) {
            $this->logger->info("Selling suspended for asset", ['assetId' => $id]);
            $this->addFlash('error', 'This asset is currently closed to secondary market listings.');
            return $this->redirectToRoute('my_portfolio_asset_position', ['id' => $id]);
        }

        // Check 3: Is the user allowed to trade (same requirements as investing)?
        try {
            $isAllowedToInvest = $this->investmentService->checkUserCanInvest();
            // $this->logger->debug("can invest");
        } catch (\Throwable $th) {
            $isAllowedToInvest = false;
            $this->logger->debug("cannot invest" . $th->getMessage());
        }

        $minShares = $this->relistingService->calculateMinShares($asset, $sharesAvailable);
        $feeExempt = $this->relistingService->isFeeExempt(
            (bool)($userInfo['is_vip'] ?? false),
            array_last(array_keys($asset->fees['relisting'])),
            $valueListedThisMonth,
        );

        $form = $this->createForm(RelistingType::class, [
            'numberOfShares' => $minShares,
        ], ['minimum' => $minShares, 'available' => $sharesAvailable]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->logger->debug("form data", $formData);
            try {
                $relistingFee = $feeExempt ? 0 : Fees::getRelistingFeeDue(
                    $asset->fees['relisting'],
                    $valueListedThisMonth,
                    round($formData['numberOfShares'] * $asset->pricePerShare, 2),
                );
                $scaResponse = $this->relistingService->createRelisting(
                    $asset,
                    $formData['numberOfShares'],
                    $sharesAvailable,
                    $relistingFee,
                );
                if ($scaResponse instanceof ScaAction && !empty($scaResponse->pendingUserAction)) {
                    $returnUrl = $this->router->generate(
                        name: 'relisting_sca_callback',
                        parameters: ['orderId' => $scaResponse->id],
                        referenceType: UrlGeneratorInterface::ABSOLUTE_URL,
                    );
                    $queryParams = http_build_query([
                        'returnUrl' => $returnUrl,
                    ]);
                    $scaSessionUrl = $scaResponse->pendingUserAction['redirectUrl'] . "&{$queryParams}";
                    if (
                        str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['sandbox'])
                        || str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['prod'])
                    ) {
                        return $this->redirect($scaSessionUrl);
                    } else {
                        // Invalid SCA session url, so we'll cancel the sell order
                        $this->investmentService->processOrderPaymentOutcome(
                            $scaResponse->id,
                            false,
                            false,
                        );
                        $this->addFlash('error', 'Unable to start SCA verification session for payment.');
                    }
                } else {
                    $this->logger->debug("SCA not required for this relisting.");
                    $this->addFlash(
                        'success',
                        'Your sell order was successfully submitted.',
                    );
                    if ($relistingFee > 0) {
                        $this->userService->setBalance();
                    }
                    $this->portfolioService->clearAuthenticatedUserPortfolioCache();
                    return $this->redirectToRoute('my_portfolio_asset_position', ['id' => $id]);
                }
            } catch (InvestmentNotAllowedException | RelistingNotAllowedException $e) {
                $this->logger->error("RelistingNotAllowed: ", [
                    'asset' => $id,
                    'user' => $userId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('my_portfolio_asset_position', ['id' => $id]);
            } catch (\Exception $e) {
                $this->logger->error("Unable to create sell order", [
                    'asset' => $id,
                    'user' => $userId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Unable to process your sell order. Please try again or contact us if problem persists.');
                return $this->redirectToRoute('my_portfolio_asset_position', ['id' => $id]);
            }
        }

        // $this->logger->debug("Relisting metadata", [
        //     'isAllowedToInvest' => $isAllowedToInvest,
        //     'sharesAvailable' => $sharesAvailable,
        //     'valueListedThisMonth' => $valueListedThisMonth,
        //     'feeExempt' => $feeExempt,
        // ]);

        return $this->render('@AppBundle/Product/sell_product.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'sellOrders' => $sellOrders,
            'onboardingProfile' => $this->onboardingService->getOnboardingProfileFromSession(),
            'isAllowedToInvest' => $isAllowedToInvest,
            'sharesAvailable' => $sharesAvailable,
            'valueListedThisMonth' => $valueListedThisMonth,
            'minShares' => $minShares,
            'feeExempt' => $feeExempt,
        ]);
    }

    #[Route(path: '/relisting/{orderId}/sca-callback', name: 'relisting_sca_callback', methods: ['GET'])]
    public function relistingScaCallback(
        int $orderId,
        #[MapQueryParameter]
        ?string $controlStatus = null,
    ): Response {
        $this->logger->info("IN SCA relisting callback");
        // This is only indicative for now, should ask backoffice to verify
        $scaOutcome = $this->scaService->isScaSuccess($controlStatus);
        try {
            $scaResponse = $this->investmentService->processOrderPaymentOutcome(
                $orderId,
                $scaOutcome,
                $this->scaService->shouldVerify($controlStatus),
            );
            if ($scaResponse->success != null) {
                $scaOutcome = $scaResponse->success;
                $this->logger->debug("Verified SCA result:", [$scaOutcome]);
            }
            if ($scaOutcome) {
                $this->logger->debug('SCA verification successful');
                $this->addFlash(
                    'success',
                    'SCA verification completed, your sell order was successfully submitted.',
                );
            } else {
                $this->logger->info(
                    'SCA verification failed',
                    ['controlStatus' => $controlStatus],
                );
                $this->addFlash('error', 'SCA verification failed. Please try again or contact support if issue persists.');
            }
        } catch (\Throwable $th) {
            $this->logger->error('Issue updating sell order after SCA verification', [$th->getMessage()]);
            $this->addFlash(
                'error',
                'Error encountered when processing SCA verification results. Please try again or contact support.',
            );
        }
        $this->userService->setBalance();
        $this->portfolioService->clearAuthenticatedUserPortfolioCache();
        return $this->redirectToRoute('my_portfolio');
    }
}
