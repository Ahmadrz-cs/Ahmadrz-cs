<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 18/02/19
 * Time: 17:53
 */

namespace AppBundle\Controller;

use AppBundle\Util\Fees;
use AppBundle\Util\Util;
use ClientBundle\Service\CrowdTekService;
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

class MyInvestmentController extends AbstractController
{
    private $params = [];
    private $user = null;

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private CrowdTekService $crowdTekService,
        private UserService $userService,
        private ScaService $scaService,
        private RelistingService $relistingService,
        private string $network,
    ) {
        $this->containerInitialized();
    }

    //Common checks
    public function containerInitialized()
    {
        $this->logger->info("==================IN containerInitialized=====================");

        $authenticated = $this->requestStack->getSession()->get('authenticated');
        // if (!$authenticated) {
        //     $verifyEmail = $this->_request->query->get('verify_email', 0);
        //     header('Location: ' . $this->generateUrl('login', array('verify_email' => $verifyEmail)));
        //     exit;
        // }
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }

        // get userInfo from session set during login - only call getUserInfo if you need a refresh after making changes
        // Note: profileAction has sync - so any redirects to /my-profile/profile after an update or change will trigger sync
        // $this->user = $this->userService->getUserInfo();
        $this->user = $this->requestStack->getSession()->get('userInfo');
    }

    // #[Route(path: '/my-investments', name: 'my_investments', methods: ['GET'])]
    // public function myInvestmentsAction(Request $request): Response
    // {
    //     $this->logger->info("==================IN myInvestmentsAction=====================");

    //     $authenticated = $this->requestStack->getSession()->get('authenticated');
    //     if (!$authenticated) {
    //         return $this->redirectToRoute('login');
    //     }

    //     // Check if a user has completed their on boarding stages, if not redirect them to onboarding
    //     $ObResponse = $this->crowdTekService->checkUserRegistered();

    //     if ($ObResponse == false) {
    //         return $this->redirect($this->generateUrl('Onboarding'));
    //     }

    //     $filterOptions = $request->query->all();
    //     $investments = $this->legacyInvestmentService->selfInvestments();

    //     $assetIds = [];
    //     $assets = [];
    //     foreach ($investments['data']['list'] as $inv) {
    //         $assetIds[] = (int)$inv['asset_id'];
    //     }
    //     $assetIds = array_unique($assetIds);
    //     if (!empty($assetIds)) {
    //         $assets = $this->organizationService->getAssets(0, 1500, [], $assetIds)['data']['list'];
    //     }

    //     // compile the unique asset names into array
    //     $assetNames = [];
    //     foreach ($investments['data']['list'] as $invItem) {
    //         $assetNames[$invItem['asset_id']] = $invItem['asset_name'];
    //     }
    //     // $assetNames = array_unique($assetNames);

    //     // compile list of asset info in form {asset_id} : {info_array}
    //     $assetInfo = [];
    //     foreach ($assets as $asset) {
    //         if (array_key_exists($asset['id'], $assetNames)) {
    //             $assetInfo[$asset['id']] = $asset;
    //         }
    //     }

    //     // $this->logger->info("asset info: " . json_encode($assetInfo));

    //     // filter out investments that don't match the filter if any
    //     $investmentsList = [];
    //     if (empty($filterOptions) || empty($filterOptions['filter_asset_name'])) {
    //         $investmentsList = $investments['data']['list'];
    //     } else {
    //         foreach ($investments['data']['list'] as $inv) {
    //             if ($inv['asset_name'] == $filterOptions['filter_asset_name']) {
    //                 $investmentsList[] = $inv;
    //             }
    //         }
    //     }

    //     $inv_count_active = 0;
    //     foreach ($investmentsList as $inv) {
    //         if ($inv['life_cycle_stage'] != 3) {
    //             if ($inv['divested_amount'] < $inv['investment_amount']) {
    //                 $inv_count_active++;
    //             }
    //         }
    //     }

    //     $this->params['menu_item'] = 'my-investments';
    //     $this->params['user_info'] = $this->user;
    //     $this->params['inv_count_total'] = count($investments['data']['list']);
    //     $this->params['inv_count_active'] = $inv_count_active;
    //     $this->params['raw_investments'] = array_reverse($investmentsList);
    //     $this->params['asset_names'] = $assetNames;
    //     $this->params['asset_info'] = $assetInfo;
    //     $this->params['filter_options'] = $filterOptions;
    //     $this->params['active'] = "myinvestments";
    //     $this->params['pageinfo'] = "My Investments";

    //     return $this->render('@AppBundle/Profile/my_investments.html.twig', $this->params);
    // }

    // #[Route(path: '/investment-history', name: 'investment_history', methods: ['GET'])]
    // public function investmentHistoryAction(Request $request): Response
    // {
    //     $this->logger->info("==================IN investmentHistoryAction=====================");

    //     // Check if a user has completed their on boarding stages, if not redirect them to onboarding
    //     $ObResponse = $this->crowdTekService->checkUserRegistered();

    //     if ($ObResponse == false) {
    //         return $this->redirect($this->generateUrl('Onboarding'));
    //     }

    //     $investments = $this->legacyInvestmentService->selfInvestments();
    //     $investments = $investments['data']['list'];

    //     $filename = "export_investment_history_" . date("Y_m_d_His") . ".csv";
    //     $response = $this->render('@AppBundle/Profile/investment_history.html.twig', [
    //         'investments' => $investments,
    //     ]);
    //     $response->setStatusCode(200);
    //     echo "\xEF\xBB\xBF"; // UTF-8 BOM
    //     $response->headers->set('Content-Type', 'text/csv');
    //     $response->headers->set('Content-Description', 'Submissions Export');
    //     $response->headers->set('Content-Disposition', 'attachment; filename=' . $filename);
    //     $response->headers->set('Content-Transfer-Encoding', 'binary');
    //     $response->headers->set('Pragma', 'no-cache');
    //     $response->headers->set('Expires', '0');

    //     return $response;
    // }


    // #[Route(path: '/my-investments/sell-my-investment/{inv_id}', name: 'sell_my_investment', methods: ['GET', 'POST'])]
    // public function sellMyInvestmentAction(Request $request, $inv_id): Response
    // {
    //     $this->logger->info("==================IN sellMyInvestmentAction=====================");

    //     // Check if a user has completed their on boarding stages, if not redirect them to onboarding
    //     $ObResponse = $this->crowdTekService->checkUserRegistered();

    //     if ($ObResponse == false) {
    //         return $this->redirect($this->generateUrl('Onboarding'));
    //     }

    //     $user = $this->requestStack->getSession()->get('userInfo');
    //     $this->user = $user;
    //     $inv_resp = $this->legacyInvestmentService->getInvestment($inv_id);
    //     $inv = $inv_resp['data']['investment'];

    //     if ('prefunding' == $inv['type']) {
    //         $this->addFlash('warning', 'Prefunding investments cannot be sold');
    //         return $this->redirect($this->generateUrl('my_investments'));
    //     }

    //     $this->logger->info('Looking up Offering: ' . $inv['offering_id']);
    //     $offering_resp = $this->offeringService->get($inv['offering_id']);
    //     $offering = $offering_resp['data']['offering'];

    //     $this->logger->info('Looking up Asset: ' . $inv['asset_id']);
    //     $organization_resp = $this->organizationService->getOne($inv['asset_id']);
    //     $organization = $organization_resp['data']['organization'];

    //     $this->params['isAllowedToRelist'] = $this->relistingService->isAllowedToRelist($user);
    //     $this->params['organization'] = $organization;
    //     $this->params['offering'] = $offering;
    //     $this->params['investment'] = $inv;
    //     $this->params['investment_amount'] = Util::getInfo($inv, 'share_amount', false);
    //     $this->params['divested_shares'] = $inv['divested_shares'];
    //     $this->params['current_holding'] = $this->params['investment_amount'] - $inv['offered_shares'];
    //     $this->params['holding_amount'] = ($this->params['investment_amount'] - $inv['divested_shares']) * Util::getInfo($inv, 'org_price_per_share', false);
    //     $this->params['offered_amount'] = $inv['offered_shares'] * Util::getInfo($inv, 'org_price_per_share', false);


    //     if (Util::getInfo($inv, 'share_amount', false) == $inv['divested_shares']) {
    //         $this->addFlash('errors', 'Nothing to sell, you have already sold all your holding.');
    //     }


    //     // Get wallet balance
    //     $this->params['wallet_balance'] = $this->userService->getBalance();
    //     if ((!isset($this->user['is_vip']) || !$this->user['is_vip']) && (!isset($this->user['is_admin']) || $this->user['is_admin'])) {
    //         $this->params['is_normal_user'] = true;
    //     }

    //     if ($inv['life_cycle_stage_name'] != 'settled') {
    //         $this->addFlash('errors', 'The investment cannot be sold as its not in a settled state.');
    //         return $this->redirectToRoute('my_investments');
    //     }

    //     /**
    //      * Check if exemption applies
    //      *
    //      * Get all (published) offerings for an asset
    //      * Find any offering that is by the user in the same calendar month
    //      */
    //     $assetOfferingsResp = $this->offeringService->selfOfferings();
    //     $userAssetOfferings = $assetOfferingsResp['data']['list'];
    //     $this->params['monthly_relistings'] = Fees::getMonthlyRelistingAmount(
    //         $userAssetOfferings,
    //         $offering['asset_id'],
    //         $user['id']
    //     );

    //     // fee exempt if monthly relistings is already the highest band
    //     $relistingFeeBands = $organization['fees']['relisting'];
    //     ksort($relistingFeeBands);
    //     $feeExempt = $this->params['monthly_relistings'] >= (int) array_key_last($relistingFeeBands);

    //     // Relisting fee exemption if VIP
    //     if (isset($this->user['is_vip']) && $this->user['is_vip']) {
    //         $feeExempt = true;
    //     }

    //     $this->params['fee_exempt'] = $feeExempt;

    //     // $this->logger->notice("share price involved: " . Util::getInfo($inv, 'org_price_per_share', false));

    //     if ($request->isMethod('POST')) {
    //         $params = $request->request->all();

    //         $this->logger->info('Offered details' . json_encode($params));


    //         if (isset($params['investment-input']) && $params['investment-input'] > 0) {
    //             /**
    //              * Determine fee by amount relisted
    //              * In the asset in the current calendar month
    //              * Update fee exemption check
    //              */
    //             $adminFee = Fees::getRelistingFeeDue(
    //                 $relistingFeeBands,
    //                 $this->params['monthly_relistings'],
    //                 $params['offered_shares'] * Util::getInfo($inv, 'org_price_per_share', false)
    //             );
    //             $feeExempt = empty($adminFee);

    //             //determine if the user has enough equity/shares to fullfill the relisting offering
    //             //get the latest investment details
    //             $inv_resp = $this->legacyInvestmentService->getInvestment($inv_id);
    //             $inv_latest = $inv_resp['data']['investment'];
    //             $holding = Util::getInfo($inv, 'share_amount', false);
    //             $available_for_offering = $holding - $inv_latest['offered_shares'];

    //             $offered_shares = $inv_latest['offered_shares'] + $params['offered_shares'];
    //             if ($offered_shares > $holding) {
    //                 $this->addFlash('errors', "Can not sell your investment. Your available holding to sell is: " . $available_for_offering . " . You tried to sell: " . $params['offered_shares'] . " shares");
    //                 return $this->redirectToRoute('my_investments');
    //             }

    //             /**
    //              * Fee applies if
    //              * - Not VIP
    //              * - Not admin
    //              * - Not fee exempt (i.e. does not meet fee exemption business rules)
    //              */
    //             if ((!isset($this->user['is_vip']) || !$this->user['is_vip']) && (!isset($this->user['is_admin']) || $this->user['is_admin']) && !$feeExempt) {
    //                 $offeringResponse = $this->_cloneOffering($inv_id, $this->user, $params);
    //                 if ((isset($offeringResponse['outcome']) && $offeringResponse['outcome'] == 'success')) {
    //                     try {
    //                         $offeringId = $offeringResponse['data']['offering_id'];
    //                         // Attempt to take the fee
    //                         $feeTransfer = $this->relistingService->takeRelistingFee(
    //                             $offeringId,
    //                             $adminFee,
    //                             true,
    //                         );
    //                         // Check if SCA is required, low balances, e.g. the £10 lowest fee may not need SCA
    //                         if (!empty($feeTransfer['transfer']['pending_user_action'])) {
    //                             $returnUrl = $this->router->generate(
    //                                 name: 'sell_my_investment_sca_callback',
    //                                 parameters: ['offeringId' => $offeringId],
    //                                 referenceType: UrlGeneratorInterface::ABSOLUTE_URL
    //                             );
    //                             $queryParams = http_build_query([
    //                                 'returnUrl' => $returnUrl
    //                             ]);
    //                             $scaSessionUrl = $feeTransfer['transfer']['pending_user_action']['RedirectUrl'] . "&{$queryParams}";
    //                             // Sanity check the SCA session url
    //                             if (
    //                                 str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['sandbox'])
    //                                 || str_contains($scaSessionUrl, ScaController::MANGOPAY_SCA_URLS['prod'])
    //                             ) {
    //                                 return $this->redirect($scaSessionUrl);
    //                             } else {
    //                                 // Invalid SCA session url, so we'll cancel the relisting
    //                                 $this->relistingService->processScaTransferResult(
    //                                     $offeringId,
    //                                     false,
    //                                     false
    //                                 );
    //                                 $this->addFlash('error', 'Unable to start SCA verification session for payment.');
    //                             }
    //                         } else {
    //                             $this->logger->debug("SCA not required for this relisting.");
    //                             $this->addFlash(
    //                                 'success',
    //                                 'Your relisting was successfully submitted.'
    //                             );
    //                             $this->userService->setBalance();
    //                             return $this->redirectToRoute('my_investments');
    //                         }
    //                     } catch (\Exception $e) {
    //                         $this->logger->error("Error taking relisting fee. " . $e->getMessage());
    //                         if (!empty($e->getMessage())) {
    //                             $this->addFlash('warning', $e->getMessage());
    //                         }
    //                         try {
    //                             // Failed to take fee, cancel offering
    //                             $this->relistingService->processScaTransferResult(
    //                                 $offeringId,
    //                                 false,
    //                                 false
    //                             );
    //                         } catch (\Throwable $th) {
    //                             $this->logger->error('Issue updating relisting after failure taking fee', [$th->getMessage()]);
    //                         }
    //                         return $this->redirectToRoute('my_investments');
    //                     }

    //                     $this->userService->setBalance();
    //                 } else {
    //                     $res = null;
    //                     $this->logger->error('Unable to create relisting: ' . json_encode($offeringResponse));
    //                 }
    //             } else {
    //                 // No relisting fee to pay, so create offering in "paid" state
    //                 $res = $this->_cloneOffering($inv_id, $this->user, $params, true);
    //             }

    //             if (!empty($res['outcome']) && $res['outcome'] == 'success') {
    //                 $this->addFlash('info', "Thank you for your submission, Yielders team will process your request.");
    //             } else {
    //                 $this->addFlash('info', 'An error occurred while trying to sell your shares. Ensure that there are sufficient funds in your wallet to cover any relisting fees. Please contact us if problems persist.');
    //                 $this->logger->error('Unable to make relisting: ' . json_encode($res));
    //             }
    //             return $this->redirectToRoute('my_investments');
    //         } else {
    //             $this->addFlash('errors', 'Relisting amount must be larger than zero.');
    //         }
    //     }
    //     return $this->render('@AppBundle/Profile/sell_my_investment.html.twig', $this->params);
    // }

    // #[Route(path: '/offerings/{offeringId}/sca-callback', name: 'sell_my_investment_sca_callback', methods: ['GET'])]
    // public function sellMyInvestmentScaCallback(
    //     int $offeringId,
    //     #[MapQueryParameter]
    //     ?string $controlStatus = null,
    // ): Response {
    //     $this->logger->info("IN SCA retail investment callback");
    //     $scaOutcome = $this->scaService->isScaSuccess($controlStatus);
    //     try {
    //         $response = $this->relistingService->processScaTransferResult(
    //             $offeringId,
    //             $scaOutcome,
    //             $this->scaService->shouldVerify($controlStatus)
    //         );
    //         if (isset($response['data']['payment_outcome'])) {
    //             $scaOutcome = $response['data']['payment_outcome'];
    //             $this->logger->debug("Verified SCA result:", [$scaOutcome]);
    //         }
    //         if ($scaOutcome) {
    //             $this->logger->debug('SCA verification successful');
    //             $this->addFlash(
    //                 'success',
    //                 'SCA verification completed, your relisting was successfully submitted.'
    //             );
    //         } else {
    //             $this->logger->info(
    //                 'SCA verification failed',
    //                 ['controlStatus' => $controlStatus]
    //             );
    //             $this->addFlash('error', 'SCA verification failed. Please try again or contact support if issue persists.');
    //         }
    //     } catch (\Throwable $th) {
    //         $this->logger->error('Issue updating relisting after SCA verification', [$th->getMessage()]);
    //         $this->addFlash(
    //             'error',
    //             'Error encountered when processing SCA verification results. Please try again or contact support.'
    //         );
    //     }
    //     $this->userService->setBalance();
    //     return $this->redirectToRoute('my_investments');
    // }


    // private function _cloneOffering($investmentId, $userInfo, $params, bool $paid = false)
    // {
    //     $this->logger->info("==================IN _cloneOffering=====================");

    //     // Clone offering
    //     $newOfferingParams = $this->params['offering'];
    //     $orgId = $newOfferingParams['organization_id'];

    //     $status = $paid ? "submitted" : "draft";
    //     /**
    //      * Set new minimum commit
    //      * If new offering is < 2* minimum commit
    //      * Such that you're unable to make a minimum offering without putting offering into limbo
    //      * Limbo being, it is no longer possible to invest due to funding goal remaining < minimum commit
    //      * Set minimum commit to the entire funding goal
    //      */
    //     if ($params['investment-input'] < $newOfferingParams['min_commit_user']) {
    //         $newOfferingParams['min_commit_user'] = $params['investment-input'];
    //     }
    //     $newOfferingParams['funding_goal'] = $params['investment-input'];
    //     $newOfferingParams['equity_offered'] = $params['offered_shares'];
    //     $newOfferingParams['is_secondary_offering'] = 1;
    //     $newOfferingParams['life_cycle_stage'] = $status;
    //     $newOfferingParams['user_id'] = $userInfo['id'];
    //     $newOfferingParams['primary_offering_id'] = $newOfferingParams['id'];
    //     $newOfferingParams['sell_investment'] = $investmentId;
    //     $newOfferingParams['created_at'] = null; // force backoffice to give new offering created_at date
    //     $tempInfo = [];
    //     if (isset($newOfferingParams['info'])) {
    //         foreach ($newOfferingParams['info'] as $info) {
    //             $tempInfo[$info['type']] = $info['value'];
    //         }
    //     }
    //     $newOfferingParams['info'] = $tempInfo;
    //     // Unset var not use
    //     $paramsUnset = ['id', 'organization', 'organization_id', 'file', 'open_date', 'close_date'];
    //     foreach ($paramsUnset as $value) {
    //         unset($newOfferingParams[$value]);
    //     }
    //     $res = $this->organizationService->addOffering($orgId, $newOfferingParams);

    //     $this->logger->info("Cloned the Offering:" . json_encode($res));


    //     if (isset($res['data']['offering_id'])) {
    //         // Update investments
    //         $temp = ['info' => ['for_sale' => 1]];

    //         $this->legacyInvestmentService->update($investmentId, $temp);
    //     }

    //     return $res;
    // }
}
