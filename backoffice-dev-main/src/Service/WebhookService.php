<?php

namespace App\Service;

use App\Entity\WebhookEvent;
use App\Repository\WebhookEventRepository;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\EventType;
use MangoPay\KycDocumentStatus;
use MangoPay\KycLevel;
use MangoPay\PayInStatus;
use MangoPay\ReportStatus;
use MangoPay\TransactionStatus;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class WebhookService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private TagAwareCacheInterface $defaultAppCache,
        private WebhookEventRepository $webhookEventRepository,
        private MangopayWalletService $mangopayService,
    ) {}

    public const COOLDOWN = 60; // 1 minutes in seconds
    public const SUPPORTED_MANGOPAY_EVENTS = [
        EventType::UserKycLight,
        EventType::UserKycRegular,
        EventType::KycSucceeded,
        EventType::KycFailed,
        EventType::KycOutdated,
        EventType::KycValidationAsked,
        EventType::TransferNormalSucceeded,
        EventType::TransferNormalFailed,
        EventType::UserAccountActivated,
        EventType::ScaEnrollmentSucceeded,
        EventType::RecipientActive,
        EventType::RecipientCanceled,
        EventType::RecipientDeactivated,
        EventType::PayinNormalSucceeded,
        EventType::PayinNormalFailed,
        EventType::UserInflowsBlocked,
        EventType::UserInflowsUnblocked,
        EventType::UserOutflowsBlocked,
        EventType::UserOutflowsUnblocked,
        // EventType::ReportGenerated,
        'REPORT_READY_FOR_DOWNLOAD',
    ];

    public function isValidMangopayHook(string $eventType, ?string $resourceId): bool
    {
        if (!in_array($eventType, self::SUPPORTED_MANGOPAY_EVENTS)) {
            $this->logger->debug('Unsupported eventtype', [$eventType, $resourceId]);
            return false;
        }
        if (is_null($resourceId)) {
            $this->logger->debug('No resource id', [$eventType, $resourceId]);
            return false;
        }
        // Do garbage collection if at least the two parameters are valid
        // So we're not doing unnecessary db writes
        // But do before the newness check
        $this->cleanOldWebhookEvents();
        if (!$this->isNew($eventType, $resourceId)) {
            $this->logger->debug('Duplicate event', [$eventType, $resourceId]);
            return false;
        }
        $verificationResult = $this->verifyMangopayWebhookEvent(
            $eventType,
            $resourceId,
        );
        if (!$verificationResult) {
            $this->logger->debug('Verification with Mangopay failed', [
                $eventType,
                $resourceId,
            ]);
        }
        return $verificationResult;
    }

    public function isNew(string $eventType, string $resourceId): bool
    {
        $fingerprint = hash('xxh3', $eventType . $resourceId);
        $existingEvent = $this->webhookEventRepository->findOneBy([
            'fingerprint' => $fingerprint,
        ]);
        if (is_null($existingEvent)) {
            $this->recordWebhookEvent($eventType, $resourceId, $fingerprint);
            return true;
        }
        $this->refreshWebhookEvent($existingEvent);
        return false;
    }

    public function cleanOldWebhookEvents(int $timeDelta = self::COOLDOWN): int
    {
        $cutoffTime = time() - $timeDelta;
        $qb = $this->entityManager->createQueryBuilder();
        $qb
            ->delete(WebhookEvent::class, 'e')
            ->andWhere($qb->expr()->lt('e.lastReceived', ':cutoffTime'))
            ->setParameter('cutoffTime', $cutoffTime);
        $this->logger->debug("Clearing webhook events older than {$cutoffTime}");
        return $qb->getQuery()->execute();
    }

    private function verifyMangopayWebhookEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        return match ($eventType) {
            EventType::UserKycLight,
            EventType::UserKycRegular,
                => $this->verifyMangopayUserEvent($eventType, $resourceId),
            EventType::KycSucceeded,
            EventType::KycFailed,
            EventType::KycOutdated,
            EventType::KycValidationAsked,
                => $this->verifyMangopayKycEvent($eventType, $resourceId),
            EventType::UserInflowsBlocked,
            EventType::UserInflowsUnblocked,
            EventType::UserOutflowsBlocked,
            EventType::UserOutflowsUnblocked,
                => $this->verifyMangopayInOutFlowEvent($eventType, $resourceId),
            EventType::TransferNormalSucceeded,
            EventType::TransferNormalFailed,
                => $this->verifyMangopayTransferEvent($eventType, $resourceId),
            EventType::RecipientActive,
            EventType::RecipientCanceled,
            EventType::RecipientDeactivated,
                => $this->verifyMangopayRecipientEvent($eventType, $resourceId),
            EventType::PayinNormalSucceeded,
            EventType::PayinNormalFailed,
                => $this->verifyMangopayPayinEvent($eventType, $resourceId),
            EventType::UserAccountActivated,
            EventType::ScaEnrollmentSucceeded,
                => $this->verifyMangopayScaUserEvent($eventType, $resourceId),
            'REPORT_READY_FOR_DOWNLOAD' => $this->verifyMangopayReportEvent(
                $eventType,
                $resourceId,
            ),
            default => false,
        };
    }

    private function verifyMangopayUserEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $kycUser = $this->mangopayService->getScaUser($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::UserKycLight => KycLevel::Light,
            EventType::UserKycRegular => KycLevel::Regular,
            default => '',
        };
        // $this->logger->debug('Expected kyc user level', [$expected, $kycUser->KYCLevel]);
        // return true; // for debug testing
        return $kycUser->KYCLevel === $expected;
    }

    private function verifyMangopayScaUserEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $scaUser = $this->mangopayService->getScaUser($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::UserAccountActivated,
            EventType::ScaEnrollmentSucceeded,
                => 'ACTIVE',
            default => '',
        };
        // $this->logger->debug('Expected SCA user status', [$expected, $scaUser->UserStatus]);
        return $scaUser->UserStatus === $expected;
    }

    private function verifyMangopayKycEvent(string $eventType, string $resourceId): bool
    {
        try {
            $kycDocument = $this->mangopayService->getKycDocument($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::KycValidationAsked => KycDocumentStatus::ValidationAsked,
            EventType::KycSucceeded => KycDocumentStatus::Validated,
            EventType::KycFailed => KycDocumentStatus::Refused,
            EventType::KycOutdated => KycDocumentStatus::OutOfDate,
            default => '',
        };
        // $this->logger->debug('Expected kyc doc status', [$expected, $kycDocument->Status]);
        return $kycDocument->Status === $expected;
    }

    private function verifyMangopayReportEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $report = $this->mangopayService->getReport($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = ReportStatus::ReadyForDownload;
        // $this->logger->debug('Expected report status', [$expected, $report->Status]);
        return $report->Status === $expected;
    }

    private function verifyMangopayTransferEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $transfer = $this->mangopayService->getTransfer($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::TransferNormalSucceeded => TransactionStatus::Succeeded,
            EventType::TransferNormalFailed => TransactionStatus::Failed,
            default => '',
        };
        // $this->logger->debug('Expected transfer status', [$expected, $transfer->Status]);
        return $transfer->Status === $expected;
    }

    private function verifyMangopayRecipientEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $recipient = $this->mangopayService->retrieveRecipient($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::RecipientActive => 'ACTIVE',
            EventType::RecipientCanceled => 'CANCELED',
            EventType::RecipientDeactivated => 'DEACTIVATED',
            default => '',
        };
        // $this->logger->debug('Expected recipient status', [$expected, $recipient->Status]);
        return $recipient->Status === $expected;
    }

    private function verifyMangopayPayinEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $payin = $this->mangopayService->retrievePayin($resourceId);
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        $expected = match ($eventType) {
            EventType::PayinNormalSucceeded => PayInStatus::Succeeded,
            EventType::PayinNormalFailed => PayInStatus::Failed,
            default => '',
        };
        // $this->logger->debug('Expected payin status', [$expected, $recipient->Status]);
        return $payin->Status === $expected;
    }

    private function verifyMangopayInOutFlowEvent(
        string $eventType,
        string $resourceId,
    ): bool {
        try {
            $blocks = $this->defaultAppCache->get(
                "mangopayUserRegulatory_{$resourceId}",
                function (ItemInterface $item) use (
                    $resourceId,
                ): ?\Mangopay\UserBlockStatus {
                    $item->expiresAfter(self::COOLDOWN);
                    $item->tag(['mangopay', 'webhook']);
                    return $this->mangopayService->getUserRegulatory($resourceId);
                },
            );
        } catch (\Exception $e) {
            $this->logger->info(
                "Webhook event {$eventType} could not fetch resource {$resourceId}",
                [$e->getMessage()],
            );
            return false;
        }
        // $this->logger->debug("ScopesBlocked", [
        //     'in' => $blocks->ScopeBlocked->Inflows,
        //     'out' => $blocks->ScopeBlocked->Outflows,
        // ]);
        return match ($eventType) {
            EventType::UserInflowsBlocked => $blocks->ScopeBlocked->Inflows,
            EventType::UserInflowsUnblocked => !$blocks->ScopeBlocked->Inflows,
            EventType::UserOutflowsBlocked => $blocks->ScopeBlocked->Outflows,
            EventType::UserOutflowsUnblocked => !$blocks->ScopeBlocked->Outflows,
            default => false,
        };
    }

    private function recordWebhookEvent(
        string $eventType,
        string $resourceId,
        string $fingerprint,
    ): WebhookEvent {
        $webhookEvent = new WebhookEvent($eventType, $resourceId, $fingerprint, time());
        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();
        return $webhookEvent;
    }

    private function refreshWebhookEvent(WebhookEvent $webhookEvent): WebhookEvent
    {
        $webhookEvent->setLastReceived(time());
        $this->entityManager->flush();
        return $webhookEvent;
    }
}
