<?php

namespace App\Controller\Webhooks;

use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\ReportStatus;
use App\Entity\Enum\ScaStatus;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\WalletUserVersion;
use App\Event\Kyc\KycReportCreatedEvent;
use App\Repository\BankAccountRepository;
use App\Repository\KycReportRepository;
use App\Repository\ReportRepository;
use App\Repository\TradeOrderRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Service\BankAccountService;
use App\Service\MangopayCardService;
use App\Service\MangopayKycService;
use App\Service\MangopayReportService;
use App\Service\MangopayWalletService;
use App\Service\TradingService;
use App\Service\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\EventType;
use MangoPay\TransactionStatus;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/webhooks')]
class MangopayController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TagAwareCacheInterface $defaultAppCache,
        private EventDispatcherInterface $eventDispatcher,
        private WebhookService $webhookService,
        private MangopayKycService $mangopayKycService,
        private MangopayWalletService $mangopayWalletService,
        private MangopayReportService $mangopayReportService,
        private TradingService $tradingService,
        private ReportRepository $reportRepository,
        private UserRepository $userRepository,
        private TransactionRepository $transactionRepository,
        private BankAccountRepository $bankAccountRepository,
        private KycReportRepository $kycReportRepository,
        private TradeOrderRepository $tradeOrderRepository,
    ) {}

    #[Route(path: '/mangopay/kyc', name: 'webhooks_mangopay_kyc')]
    public function kyc(Request $request): Response
    {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::UserKycLight,
                EventType::UserKycRegular,
            ])) {
                $user = $this->userRepository->findBy([
                    'mangoPayUserId' => $resourceId,
                ]);
                if (1 == count($user)) {
                    try {
                        // Can crash if unable to get Mangopay user
                        $kycReport = $this->mangopayKycService->viewReport(
                            $user[0],
                            $resourceId,
                            notes: $eventType,
                        );
                        // Ignore similar reports from the last 5 cooldown windows (~5 minutes) to avoid duplicates
                        $reportingWindow = 5 * WebhookService::COOLDOWN;
                        $similarKycReports = $this->kycReportRepository->findByWithAssociations([
                            'subjectId' => $kycReport->subject->getId(),
                            'providerName' => $kycReport->providerName,
                            'providerReference' => $kycReport->providerReferenceId,
                            'checkType' => $kycReport->checkType,
                            'result' => $kycReport->result,
                            'score' => $kycReport->score,
                            'verified' => $kycReport->verified,
                            'checkedAt_gte' => new \DateTime(
                                "-{$reportingWindow} seconds",
                            ),
                        ]);
                        if ($similarKycReports->getNbResults() == 0) {
                            $this->entityManager->persist($kycReport);
                            $this->entityManager->flush();
                            $this->logger->debug('Saved kyc status change as KycReport', [
                                'id' => $kycReport->id,
                            ]);
                            $this->eventDispatcher->dispatch(
                                new KycReportCreatedEvent($kycReport),
                            );
                        } else {
                            $this->logger->debug('Existing Kyc report found');
                        }
                    } catch (\Throwable $th) {
                        $this->logger->error(
                            "Issue processing user KYC event {$user[0]->getId()}",
                        );
                    }
                }
            }

            // match ($eventType) {
            //     'KYC_SUCCEEDED' => $this->kycService->markAsVerified(),
            //     'KYC_FAILED' => $this->kycService->markAsFailed(),
            //     'KYC_VALIDATION_ASKED' => $this->kycService->requestMoreInfo(),
            //     'KYC_OUTDATED' => $this->kycService->requestMoreInfo(),
            //     default => '',
            // };
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(
        path: '/mangopay/kyc/regulatory-flows',
        name: 'webhooks_mangopay_kyc_regulatory_flows',
    )]
    public function kycRegulatoryFlows(Request $request): Response
    {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::UserInflowsBlocked,
                EventType::UserInflowsUnblocked,
                EventType::UserOutflowsBlocked,
                EventType::UserOutflowsUnblocked,
            ])) {
                // $this->logger->debug('Finding user by id', ['id' => $resourceId]);
                $user = $this->userRepository->findBy([
                    'mangoPayUserId' => $resourceId,
                ]);
                if (1 == count($user)) {
                    try {
                        /**
                         * -Can crash if unable to get Mangopay user
                         * - If flows are blocked and the action code is for expired docs
                         * - Fire off an event for ID doc expiry
                         * - An event listener/subscriber should then use this event to decide whether to open a recurring KYC review
                         */
                        $userRegulatoryStatus = $this->defaultAppCache->get(
                            "mangopayUserRegulatory_{$resourceId}",
                            function (ItemInterface $item) use (
                                $resourceId,
                            ): ?\Mangopay\UserBlockStatus {
                                $item->expiresAfter(WebhookService::COOLDOWN);
                                $item->tag(['mangopay', 'webhook']);
                                return $this->mangopayWalletService->getUserRegulatory(
                                    $resourceId,
                                );
                            },
                        );
                        $kycReport = $this->mangopayKycService->createUserRegulatoryReport(
                            $user[0],
                            $eventType,
                            $userRegulatoryStatus,
                        );
                        // Ignore similar reports from the last 5 cooldown windows (~5 minutes) to avoid duplicates
                        $reportingWindow = 5 * WebhookService::COOLDOWN;
                        $similarKycReports = $this->kycReportRepository->findByWithAssociations([
                            'subjectId' => $kycReport->subject->getId(),
                            'providerName' => $kycReport->providerName,
                            'providerReference' => $kycReport->providerReferenceId,
                            'checkType' => $kycReport->checkType,
                            'result' => $kycReport->result,
                            'score' => $kycReport->score,
                            'verified' => $kycReport->verified,
                            'checkedAt_gte' => new \DateTime(
                                "-{$reportingWindow} seconds",
                            ),
                        ]);
                        if ($similarKycReports->getNbResults() == 0) {
                            $this->entityManager->persist($kycReport);
                            $this->entityManager->flush();
                            $this->logger->debug('Saved user regulatory status change as KycReport', [
                                'id' => $kycReport->id,
                            ]);
                            $this->eventDispatcher->dispatch(
                                new KycReportCreatedEvent($kycReport),
                            );
                        } else {
                            $this->logger->debug('Existing Kyc report found');
                        }
                    } catch (\Throwable $th) {
                        $this->logger->error(
                            "Issue processing user KYC regulatory event {$user[0]->getId()}",
                        );
                    }
                }
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(path: '/mangopay/sca', name: 'webhooks_mangopay_sca')]
    public function sca(Request $request): Response
    {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::UserAccountActivated,
                EventType::ScaEnrollmentSucceeded,
            ])) {
                // Could alternatively implement with a findOneBy and try-catch
                // to handle rare cases where there are 2 users with the same mangoPayUserId
                $user = $this->userRepository->findBy([
                    'mangoPayUserId' => $resourceId,
                ]);
                if (1 == count($user)) {
                    // Ensure WalletUserVersion is set to at least UserScaEnrollment
                    if (
                        $user[0]->getWalletUserVersion()->value
                        < WalletUserVersion::UserScaEnrollment->value
                    ) {
                        $user[0]->setWalletUserVersion(WalletUserVersion::UserScaEnrollment);
                        $user[0]->setScaStatus(ScaStatus::Active);
                        $this->entityManager->flush();
                    }
                }
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(path: '/mangopay/report', name: 'webhooks_mangopay_report')]
    public function reports(Request $request): Response
    {
        $eventType = $request->query->get('EventType');
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Report callback valid', [$eventType, $resourceId]);
            $report = $this->reportRepository->findOneBy([
                'referenceId' => $resourceId,
            ]);
            if (!is_null($report)) {
                try {
                    $mangopayReport =
                        $this->mangopayWalletService->getReport($resourceId);
                    $this->mangopayReportService->storeReport($report, $mangopayReport);
                    $report->setStatus(ReportStatus::Available);
                    $this->entityManager->flush();
                } catch (\MangoPay\Libraries\ResponseException $e) {
                    $this->logger->error('Error retrieving Mangopay report', [
                        $e->GetCode(),
                        $e->getMessage(),
                        $e->GetErrorDetails(),
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Unable to store report', [$e->getMessage()]);
                }
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(path: '/mangopay/transfers', name: 'webhooks_mangopay_transfers')]
    public function transfers(Request $request): Response
    {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::TransferNormalSucceeded,
                EventType::TransferNormalFailed,
            ])) {
                // See if there is a trade order update
                $tradeOrders = $this->tradeOrderRepository->findBy([
                    'transactionReference' => $resourceId,
                ]);
                if (
                    1 == count($tradeOrders)
                    && in_array($tradeOrders[0]->getStatus(), [
                        TradeOrderStatus::Draft,
                        TradeOrderStatus::Submitted,
                    ])
                ) {
                    // $this->logger->debug("TradeOrder {$tradeOrder[0]->getId()} to update");
                    $status = match ($eventType) {
                        EventType::TransferNormalSucceeded => true,
                        EventType::TransferNormalFailed => false,
                        default => null,
                    };
                    if ($status !== null) {
                        $this->tradingService->processPaymentOutcome(
                            $tradeOrders[0],
                            $status,
                        );
                    }
                }
                // See if there is a transaction to update
                // Note that processPaymentOutcome already updates a linked transaction
                // so this will overwrite that if it's the same transaction
                $transaction = $this->transactionRepository->findBy([
                    'external_id' => $resourceId,
                ]);
                if (
                    1 == count($transaction)
                    && $transaction[0]->getPaymentStatus() == TransactionStatus::Created
                ) {
                    // $this->logger->debug("Transaction {$transaction[0]->getId()} to update");
                    $status = match ($eventType) {
                        EventType::TransferNormalSucceeded
                            => TransactionStatus::Succeeded,
                        EventType::TransferNormalFailed => TransactionStatus::Failed,
                        default => TransactionStatus::Created, // leave unchanged
                    };
                    $transaction[0]->setPaymentStatus($status);
                }

                $this->entityManager->flush();
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(path: '/mangopay/recipients', name: 'webhooks_mangopay_recipients')]
    public function recipients(
        Request $request,
        BankAccountService $bankAccountService,
    ): Response {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::RecipientActive,
                EventType::RecipientCanceled,
                EventType::RecipientDeactivated,
            ])) {
                // See if there is a bank account registration to update (for relistings)
                $bankAccount = $this->bankAccountRepository->findBy([
                    'providerId' => $resourceId,
                ]);
                if (
                    1 == count($bankAccount)
                    && in_array($bankAccount[0]->getStatus(), [
                        BankAccountStatus::Approved,
                        BankAccountStatus::Active,
                    ])
                ) {
                    $this->logger->debug(
                        "Bank account #{$bankAccount[0]->getId()} to update",
                    );
                    // Deactivation can crash if Mangopay has issues
                    try {
                        // Only disable on deactivation, if cancelled due to SCA fail, allow user to create new and do SCA again, so don't disable
                        match ($eventType) {
                            EventType::RecipientActive
                                => $bankAccountService->processActivationOutcome(
                                $bankAccount[0],
                                true,
                            ),
                            EventType::RecipientCanceled
                                => $this->logger->notice('Bank account SCA verification not completed', [$bankAccount[0]->getId()]),
                            EventType::RecipientDeactivated
                                => $bankAccountService->disableBankAccount(
                                $bankAccount[0],
                            ),
                            default => null, // Do nothing
                        };
                        $this->entityManager->flush();
                    } catch (\Throwable $th) {
                        $this->logger->error(
                            "Issue processing bank account {$bankAccount[0]->getId()}",
                        );
                    }
                }
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }

    #[Route(path: '/mangopay/payins', name: 'webhooks_mangopay_payins')]
    public function payins(
        Request $request,
        MangopayCardService $mangopayCardService,
    ): Response {
        $eventType = $request->query->get('EventType');
        // Note that Mangopay themselves have typoed RessourceId!
        // https://docs.mangopay.com/webhooks
        $resourceId = $request->query->get('RessourceId');
        if (
            $eventType
            && $resourceId
            && $this->webhookService->isValidMangopayHook($eventType, $resourceId)
        ) {
            $this->logger->info('Hook event valid', [$eventType, $resourceId]);
            if (in_array($eventType, [
                EventType::PayinNormalSucceeded,
                EventType::PayinNormalFailed,
            ])) {
                // See if the payin is a card payin with a card that needs to be deactivated
                $cardId = $mangopayCardService->getPayInCardId($resourceId);
                if ($cardId !== null) {
                    // $this->logger->debug("Card {$cardId} to deactivate");
                    try {
                        // Deactivation can crash if Mangopay has issues
                        $mangopayCardService->deactivateCardById($cardId);
                    } catch (\Throwable $th) {
                        $this->logger->error("Issue deactivating card {$cardId}");
                    }
                }
            }
        }
        return new JsonResponse(null, Response::HTTP_OK);
    }
}
