<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\AssetStatus;
use AppBundle\Entity\Enum\TradeOrderStatus;
use AppBundle\Entity\Enum\TradeOrderType;
use AppBundle\Entity\ScaAction;
use AppBundle\Entity\TradeOrder;
use AppBundle\Form\InvestmentRetailType;
use AppBundle\Form\PrefundingType;
use ClientBundle\Exception\InvestmentNotAllowedException;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\InvestmentServiceV2;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\PortfolioService;
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

class InvestmentController extends AbstractController
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
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
        $this->user = $this->requestStack->getSession()->get('userInfo');
        $this->isVip = \array_key_exists('is_vip', $this->user) && $this->user['is_vip'];
    }

    #[Route(path: '/properties/{id}/prefund/{orderId}', name: 'product_properties_prefund', methods: ['GET', 'POST'])]
    public function propertyPrefund(Request $request, int $id, string $orderId): Response
    {
        $this->logger->debug("IN ProductController->propertyPrefund");

        $asset = null;
        $sellOrders = [];
        try {
            $asset = $this->assetProductService->getSingleAssetProduct($id);
            $filters = [
                'status' => [TradeOrderStatus::Active],
                'type' => [TradeOrderType::Initial, TradeOrderType::Market],
            ];
            $sellOrders = $this->assetProductService->getAssetProductsListings($id, $filters);
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            $userId = (\is_array($userInfo) && \array_key_exists('id', $userInfo))
                ? $userInfo['id']
                : 0;
            $sellOrders = array_filter(
                $sellOrders,
                fn(TradeOrder $item): bool => $item->userId != $userId,
            );
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load asset listing", ['id' => $id]);
            $this->addFlash('error', 'Unable to get requested asset listing');
            return $this->redirectToRoute('product_properties_current');
        }

        if ($asset->buyRestricted) {
            $this->logger->info("Buying suspended for asset", ['id' => $id]);
            $this->addFlash('error', 'This asset is currently closed for new investments.');
            return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
        }

        if ($asset->status == AssetStatus::Acquiring && !$this->isVip) {
            $this->addFlash('warning', 'Prefunding properties are only available to Top Yielders');
            return $this->redirectToRoute('product_properties_prefunding');
        }

        $selected = array_find($sellOrders, fn(TradeOrder $item): bool => $item->uuid == $orderId);
        if (empty($selected)) {
            $this->logger->error("Unable to select order for investment", ['asset' => $id, 'order' => $orderId]);
            $this->addFlash('error', 'The selected offer is no longer available for investment. Please select another offer.');
            return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
        }

        try {
            $isAllowedToInvest = $this->investmentService->checkUserCanInvest();
            // $this->logger->debug("can invest");
        } catch (\Throwable $th) {
            $isAllowedToInvest = false;
            $this->logger->debug("cannot invest" . $th->getMessage());
        }

        $form = $this->createForm(PrefundingType::class, [
            'numberOfShares' => $selected->minimumShares ?: 0,
            'sharesToKeep' => 0,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->logger->debug("form data", $formData);
            try {
                $scaResponse = $this->investmentService->prefundInvestAsset(
                    $asset,
                    $formData['numberOfShares'],
                    $formData['sharesToKeep'],
                    $selected,
                );
                if ($scaResponse instanceof ScaAction && !empty($scaResponse->pendingUserAction)) {
                    $returnUrl = $this->router->generate(
                        name: 'invest_sca_callback',
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
                        // Invalid SCA session url, so we'll cancel the investment
                        $this->investmentService->processOrderPaymentOutcome(
                            $scaResponse->id,
                            false,
                            false,
                        );
                        $this->addFlash('error', 'Unable to start SCA verification session for payment.');
                    }
                } else {
                    $this->logger->debug("SCA not required for this investment.");
                    $this->addFlash(
                        'success',
                        'Your investment was successfully submitted.',
                    );
                    $this->userService->setBalance();
                    $this->portfolioService->clearAuthenticatedUserPortfolioCache();
                    return $this->redirectToRoute('my_portfolio');
                }
            } catch (InvestmentNotAllowedException $e) {
                $this->logger->error("InvestmentNotAllowedException: ", [
                    'asset' => $id,
                    'order' => $orderId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
            } catch (\Exception $e) {
                $this->logger->error("Unable to create investment", [
                    'asset' => $id,
                    'order' => $orderId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Unable to process your investment. Please try again or contact us if problem persists.');
                return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
            }
        }

        return $this->render('@AppBundle/Product/prefund_product.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'sellOrders' => $sellOrders,
            'selectedOrder' => $selected,
            'onboardingProfile' => $this->onboardingService->getOnboardingProfileFromSession(),
            'isAllowedToInvest' => $isAllowedToInvest,
        ]);
    }

    #[Route(path: '/properties/{id}/invest/{orderId}', name: 'product_properties_invest', methods: ['GET', 'POST'])]
    public function propertyInvest(Request $request, int $id, string $orderId): Response
    {
        $this->logger->debug("IN ProductController->propertyInvest");

        $asset = null;
        $sellOrders = [];
        try {
            $asset = $this->assetProductService->getSingleAssetProduct($id);
            $filters = [
                'status' => [TradeOrderStatus::Active],
                'type' => [TradeOrderType::Initial, TradeOrderType::Market],
            ];
            $sellOrders = $this->assetProductService->getAssetProductsListings($id, $filters);
            $userInfo = $this->requestStack->getSession()->get('userInfo');
            $userId = (\is_array($userInfo) && \array_key_exists('id', $userInfo))
                ? $userInfo['id']
                : 0;
            $sellOrders = array_filter(
                $sellOrders,
                fn(TradeOrder $item): bool => $item->userId != $userId,
            );
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load asset listing", ['id' => $id]);
            $this->addFlash('error', 'Unable to get requested asset listing');
            return $this->redirectToRoute('product_properties_current');
        }

        if ($asset->buyRestricted) {
            $this->logger->info("Buying suspended for asset", ['id' => $id]);
            $this->addFlash('error', 'This asset is currently closed for new investments.');
            return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
        }

        $selected = array_find($sellOrders, fn(TradeOrder $item): bool => $item->uuid == $orderId);
        if (empty($selected)) {
            $this->logger->error("Unable to select order for investment", ['asset' => $id, 'order' => $orderId]);
            $this->addFlash('error', 'The selected offer is no longer available for investment. Please select another offer.');
            return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
        }

        try {
            $isAllowedToInvest = $this->investmentService->checkUserCanInvest();
            // $this->logger->debug("can invest");
        } catch (\Throwable $th) {
            $isAllowedToInvest = false;
            $this->logger->debug("cannot invest" . $th->getMessage());
        }

        $monthlyInvestmentsInAsset = $this->investmentService->sumUnsettledMonthlyShareTrades($id);

        $form = $this->createForm(InvestmentRetailType::class, [
            'numberOfShares' => $selected->minimumShares,
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $this->logger->debug("form data", $formData);
            try {
                // $this->addFlash('info', 'Trading is currently suspended and we are unable to process your investment at this time.');
                // return $this->redirectToRoute('product_properties_invest', ['id' => $id, 'orderId' => $orderId]);
                $scaResponse = $this->investmentService->retailInvestAsset(
                    $asset,
                    $formData['numberOfShares'],
                    $selected,
                );
                if ($scaResponse instanceof ScaAction && !empty($scaResponse->pendingUserAction)) {
                    $returnUrl = $this->router->generate(
                        name: 'invest_sca_callback',
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
                        // Invalid SCA session url, so we'll cancel the investment
                        $this->investmentService->processOrderPaymentOutcome(
                            $scaResponse->id,
                            false,
                            false,
                        );
                        $this->addFlash('error', 'Unable to start SCA verification session for payment.');
                    }
                } else {
                    $this->logger->debug("SCA not required for this investment.");
                    $this->addFlash(
                        'success',
                        'Your investment was successfully submitted.',
                    );
                    $this->userService->setBalance();
                    $this->portfolioService->clearAuthenticatedUserPortfolioCache();
                    return $this->redirectToRoute('my_portfolio');
                }
            } catch (InvestmentNotAllowedException $e) {
                $this->logger->error("InvestmentNotAllowedException: ", [
                    'asset' => $id,
                    'order' => $orderId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', $e->getMessage());
                return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
            } catch (\Exception $e) {
                $this->logger->error("Unable to create investment", [
                    'asset' => $id,
                    'order' => $orderId,
                    'message' => $e->getMessage(),
                ]);
                $this->addFlash('error', 'Unable to process your investment. Please try again or contact us if problem persists.');
                return $this->redirectToRoute('product_properties_detail', ['id' => $id]);
            }
        }

        return $this->render('@AppBundle/Product/invest_product.html.twig', [
            'form' => $form,
            'asset' => $asset,
            'sellOrders' => $sellOrders,
            'selectedOrder' => $selected,
            'onboardingProfile' => $this->onboardingService->getOnboardingProfileFromSession(),
            'isAllowedToInvest' => $isAllowedToInvest,
            'invested_this_month' => $monthlyInvestmentsInAsset ?? 0,
        ]);
    }

    #[Route(path: '/investments/{orderId}/sca-callback', name: 'invest_sca_callback', methods: ['GET'])]
    public function investmentScaCallback(
        int $orderId,
        #[MapQueryParameter]
        ?string $controlStatus = null,
    ): Response {
        $this->logger->info("IN SCA investment callback");
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
                    'SCA verification completed, your investment was successfully submitted.',
                );
            } else {
                $this->logger->info(
                    'SCA verification failed',
                    ['controlStatus' => $controlStatus],
                );
                $this->addFlash('error', 'SCA verification failed. Please try again or contact support if issue persists.');
            }
        } catch (\Throwable $th) {
            $this->logger->error('Issue updating investment after SCA verification', [$th->getMessage()]);
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
