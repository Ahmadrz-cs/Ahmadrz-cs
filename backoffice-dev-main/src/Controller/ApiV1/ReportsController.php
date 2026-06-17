<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\InvestmentDocuments;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;
use App\Service\Manager\PayoutManager;
use App\Service\ReportsService;
use App\Service\Util\Helper;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\IsNull;

class ReportsController extends AbstractFOSRestController
{
    public function __construct(
        private PayoutManager $payoutManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'userid', requirements: '\d+')]
    #[Rest\QueryParam(name: 'report_type', requirements: '[a-z_]+')]
    #[Rest\View]
    #[Get('/%api_network_path%/reports/getUserReport', name: 'api_get_reports_user')]
    public function getUserReport(ParamFetcherInterface $paramFetcher)
    {
        $this->logger->info('IN getUserReport');

        //We must have parameters
        if (!isset($paramFetcher) || empty($paramFetcher)) {
            return $this->errorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS, null);
        }

        $userid = $paramFetcher->get('userid');
        $report_type = $paramFetcher->get('report_type');

        //check parms have values
        if (!isset($userid) || !isset($report_type)) {
            return $this->errorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS, null);
        }

        /** @var User $user */
        $user = $this->getUser();
        switch ($report_type) {
            case 'payment_history':
                if ($this->isGranted('ROLE_ADMIN') || $user->getId() == $userid) {
                    //get data
                    return $this->_getPayoutsReport($user);
                } else {
                    return $this->errorResponse(
                        ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                        null,
                    );
                }

                break;

            case 'test_report':
                if ($this->isGranted('ROLE_ADMIN') || $user->getId() == $userid) {
                    //get data

                    return $this->_getTestReport($userid);
                } else {
                    return $this->errorResponse(
                        ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                        null,
                    );
                }

                break;

            default:
                return $this->errorResponse(
                    ErrorResponse::ERROR_INSUFFICIENT_PARAMS,
                    null,
                );

                break;
        }
    }

    /**
     * test response for development of stub, this can be removed later
     *
     * @param $userid
     * @return \App\Controller\ApiV1\Response\SuccessResponse
     */
    private function _getTestReport($userid)
    {
        return new SuccessResponse();
    }

    private function errorResponse($errorCode, $message = null)
    {
        $trace = debug_backtrace();
        $this->logger->debug(
            'API error response - method trace: ' . $trace[1]['function'],
        );

        $this->logger->debug('API error response: '
        . new ErrorResponse($errorCode, $message));

        return new ErrorResponse($errorCode, $message);
    }

    /**
     *
     * @param $user
     */
    private function _getPayoutsReport($user, $cumulative = true)
    {
        $this->logger->info('IN _getPayoutsReport');

        $invOfferingMap = []; // maps inv_id      : offering_id
        $offeringMap = []; // maps asset_id : offering_name (not asset name)

        $aggregateInvestments = 0;
        $aggregatePayouts = 0;
        $processedPayouts = [];
        $totalProfitShare = 0;
        $totalRentalEarnings = 0;

        // compile maps (dictionaries) and aggregate investment totals
        $allUserInvestments = $user->getInvestments();
        $userInvestments = [];

        // filter out investments that are neither approved (2) or settled (4)
        foreach ($allUserInvestments as $inv) {
            if (in_array($inv->getStatus()->getLifecycleStatusAsInt(), [2, 4])) {
                $userInvestments[] = $inv;
            }
        }

        foreach ($userInvestments as $inv) {
            $invOfferingMap[$inv->getId()] = $inv->getOffering()->getId();
            $heldInvestment = $inv->getInvestmentValue() - $inv->getDivestedAmount(); // exclude divested amount
            if ($heldInvestment < 0) {
                $heldInvestment = 0; // clean up FP arithmetic
            }
            $aggregateInvestments += $heldInvestment;
            $offeringMap[$inv->getAssetId()]['name'] = $inv->getOffering()->getName();
        }

        // generate keys for the monthly payouts array in form: YYYY-MM
        $months = Helper::generatePastMonthsStrings();

        /**
         * Construct summary array
         * - Done this way to simplify the summing by allowing usage of +=
         * - Bonus benefit of readability - able to see neatly what keys are in the summary (assoc) array
         * - Can define ordering of keys - note property_name is the first key - for readability of the end JSON!
         */
        $processedPayouts = array_fill_keys(array_keys($offeringMap), [
            'number_of_investments' => 0,
            'property_name' => '',
            'term_remaining' => null,
            'total_invested' => 0,
            'total_payouts' => 0,
            'monthly_payouts' => array_fill_keys($months, 0),
        ]);

        // fill in offering specific data - e.g. asset name
        foreach (array_keys($processedPayouts) as $offId) {
            $processedPayouts[$offId]['property_name'] = $offeringMap[$offId]['name'];
        }

        // breakdown investments by offering as wel
        foreach ($userInvestments as $inv) {
            $heldInvestment = $inv->getInvestmentValue() - $inv->getDivestedAmount(); // exclude divested amount
            if ($heldInvestment < 0) {
                $heldInvestment = 0;
            } else {
                $processedPayouts[$inv->getAssetId()]['number_of_investments'] += 1;
            }
            $processedPayouts[$inv->getAssetId()]['total_invested'] += $heldInvestment;

            if (!$processedPayouts[$inv->getAssetId()]['term_remaining']) {
                $asset = $inv->getOffering()->getAsset();
                $invTerm = $asset->getInvestmentTerm();

                if ($invTerm && $invTerm > 0) {
                    $termPassed = ceil(
                        (time() - strtotime(Helper::formatDate($asset->getCreatedAt())))
                        / (30 * 24 * 3600),
                    );
                    $invTerm = intval($invTerm) - $termPassed;
                    if ($invTerm < 0) {
                        $invTerm = 0;
                    }
                }

                $processedPayouts[$inv->getAssetId()]['term_remaining'] = $invTerm ?? 0;
            }
        }

        //Get payouts via investment id's
        $userPayoutsLegacy = $this->payoutManager->findAllValue('', '', ['investment' =>
            array_keys($invOfferingMap)]);
        //Get payouts via user object
        $userPayoutsNew = $this->payoutManager->findAllValue('', '', [
            'creditedUser' => $user,
        ]);

        if (!empty($userPayoutsNew)) {
            $ids = [];
            $mergedPayouts = array_merge($userPayoutsLegacy, $userPayoutsNew);
            $userPayouts = [];
            //add unique payouts to $userPayouts
            foreach ($mergedPayouts as $payout) {
                $id = $payout->getId();
                if (!isset($ids[$id])) {
                    $ids[$id] = $id;
                    $userPayouts[] = $payout;
                }
            }
        } else {
            $userPayouts = $userPayoutsLegacy;
        }

        // put payouts in aggregate and by month buckets
        foreach ($userPayouts as $payout) {
            $amount = $payout->getPayoutAmount();

            if ($payout->getInvestment()) {
                $payoutAssetId = $payout->getInvestment()->getAssetId();
                // determine if profit share, if so, deduct the remaining held investment from that payout
                // most reliable is by payout_type being 1, otherwise, fuzzy filter by whether payout is unusually large relative to held investment
                $remainingInvestment =
                    $payout->getInvestment()->getInvestmentValue()
                    - $payout->getInvestment()->getDivestedAmount();
                if (
                    $payout->getPayoutType() == 1
                    or $amount >= (0.75 * $remainingInvestment)
                ) {
                    $payoutAmount = $amount - $remainingInvestment;
                } else {
                    $payoutAmount = $amount;
                }
            } else {
                $payoutAmount = $amount;
                $payoutAssetId = $payout->getAsset()->getId();
            }

            // per offering total
            $processedPayouts[$payoutAssetId]['total_payouts'] += $payoutAmount;
            $aggregatePayouts += $payoutAmount;

            /**
             * Determine whether we need to include in last 12 months summary - done by string comparison
             * - convert dates in YYYY-MM format, and see if it exists in our $months array
             * - if not, we can ignore the payout for the monthly breakdown (it's already in the totals)
             */
            $payoutDate = date(
                'Y-m',
                strtotime(Helper::formatDate($payout->getDueDate())),
            );
            if (in_array($payoutDate, $months)) {
                $processedPayouts[$payoutAssetId]['monthly_payouts'][$payoutDate] +=
                    $payoutAmount;
            }

            if ($payout->getPayoutType() == 1) {
                $totalProfitShare += $amount;
            } else {
                $totalRentalEarnings += $amount;
            }
        }

        if ($cumulative) {
            foreach ($processedPayouts as $id => $property) {
                $monthlyPayoutValues = array_values($property['monthly_payouts']);
                $previousPayouts =
                    $property['total_payouts'] - array_sum($monthlyPayoutValues);

                $monthlyPayoutValues = Helper::convertArrayToCumulative(
                    $monthlyPayoutValues,
                    $previousPayouts,
                );

                $monthlyPayoutValues = array_combine(
                    array_keys($property['monthly_payouts']),
                    $monthlyPayoutValues,
                );
                $processedPayouts[$id]['monthly_payouts'] = $monthlyPayoutValues;
            }
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'aggregate_invested' => $aggregateInvestments,
                'aggregate_earnings' => $aggregatePayouts,
                'payouts_summary' => $processedPayouts,
                'total_profit_share' => $totalProfitShare,
                'total_rental_earnings' => $totalRentalEarnings,
            ],
            'status' => 200,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Rest\QueryParam(
        name: 'mode',
        requirements: '(cumulative|unit)',
        nullable: true,
        default: '',
    )]
    #[Get(
        '/%api_network_path%/reports/account/{userId}',
        name: 'api_get_user_account_report',
    )]
    public function getUserAccountReportAction(
        ParamFetcherInterface $paramFetcher,
        ReportsService $reportsService,
        UserRepository $userRepository,
        int $userId,
    ) {
        //check user permission
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $loggedUser */
            $loggedUser = $this->getUser();
            if ($loggedUser) {
                if ($loggedUser->getId() != $userId) {
                    throw $this->createAccessDeniedException('Not permitted to view user with id: '
                    . $userId);
                }
            }
        }

        $context = new Context();
        $context->addGroups(['standard']);
        $user = $userRepository->find($userId);

        if ($user) {
            $investments = $reportsService->getUserInvestments($user) ?? [];
            $payouts = $reportsService->getAllUserPayouts($user) ?? [];
            $report = $reportsService->getAccountSummary(
                $investments,
                $payouts,
                $paramFetcher->get('mode') ?? null,
            );
            $view = View::create()->setData($report)->setContext($context);

            return $this->getViewHandler()->handle($view);
        }

        if (!$user) {
            throw $this->createNotFoundException('User with id: '
            . $userId
            . ' does not exist.');
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Rest\QueryParam(
        name: 'mode',
        requirements: '(cumulative|unit)',
        nullable: true,
        default: '',
    )]
    #[Get(
        '/%api_network_path%/reports/assets/{userId}',
        name: 'api_get_user_assets_report',
    )]
    public function getUserAssetsReportAction(
        ParamFetcherInterface $paramFetcher,
        ReportsService $reportsService,
        UserRepository $userRepository,
        int $userId,
    ) {
        //check user permission
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $loggedUser */
            $loggedUser = $this->getUser();
            if ($loggedUser) {
                if ($loggedUser->getId() != $userId) {
                    throw $this->createAccessDeniedException('Not permitted to view user with id: '
                    . $userId);
                }
            }
        }

        $context = new Context();
        $context->addGroups(['standard']);
        $user = $userRepository->find($userId);

        if ($user) {
            $investments = $reportsService->getUserInvestments($user) ?? [];
            $payouts = $reportsService->getAllUserPayouts($user) ?? [];
            $report = $reportsService->getAssetSummaries(
                $investments,
                $payouts,
                $paramFetcher->get('mode') ?? null,
            );
            $view = View::create()->setData($report)->setContext($context);

            return $this->getViewHandler()->handle($view);
        }

        if (!$user) {
            throw $this->createNotFoundException('User with id: '
            . $userId
            . ' does not exist.');
        }
    }
}
