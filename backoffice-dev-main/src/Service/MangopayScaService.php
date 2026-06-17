<?php

namespace App\Service;

use MangoPay\Recipient;
use MangoPay\TransactionStatus;
use MangoPay\UserConsent;
use MangoPay\UserEnrollmentResult;
use Psr\Log\LoggerInterface;

class MangopayScaService
{
    public const array MANGOPAY_SCA_URLS = [
        'sandbox' => 'https://sca.sandbox.mangopay.com',
        'prod' => 'https://sca.mangopay.com',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $mangopayService,
    ) {}

    /**
     * - In the context of SCA, has the Mangopay transfer succeeded
     * - Failure to get transfer means we are unable to confirm, thus we must treat this as a fail for downstream
     * - Should only call this after SCA session has ended (prematurely or not) from the frontend
     * - If transfer still in Created state after an SCA session
     * - We can only assume it ended with some issue, thus we must treat as a fail for downstream
     */
    public function isTransferSucceeded(string $transferId): bool
    {
        try {
            $transfer = $this->mangopayService->getTransfer($transferId);
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not fetch transfer {$transferId}",
                [$e->getMessage()],
            );
        }
        return match ($transfer->Status ?? null) {
            TransactionStatus::Succeeded => true,
            // TransactionStatus::Failed => false,
            default => false,
        };
    }

    public function isRecipientActivated(string $recipientId): ?bool
    {
        $this->logger->debug('Check recipient is activated', [$recipientId]);
        try {
            $recipient = $this->mangopayService->retrieveRecipient($recipientId);
            $this->logger->debug('Recipient status', [
                'recipientId' => $recipientId,
                'status' => $recipient->Status,
            ]);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error(
                "Could not fetch recipient {$recipientId}",
                [$e->GetCode(), $e->getMessage(), $e->GetErrorDetails()],
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not fetch recipient {$recipientId}",
                [$e->getMessage()],
            );
        }
        return match ($recipient->Status ?? null) {
            'ACTIVE' => true,
            'DEACTIVATED' => false,
            'PENDING', 'CANCELED' => null,
            // If Mangopay is unreachable, return false as we cannot confirm
            default => false,
        };
    }

    public function getScaSessionUrl(
        Recipient|UserConsent|UserEnrollmentResult $mangopayObject,
        string $returnUrl,
    ): ?string {
        $scaSessionUrl = $mangopayObject?->PendingUserAction?->RedirectUrl;
        $this->logger->debug("{$scaSessionUrl}");
        if ($scaSessionUrl === null) {
            throw new \RuntimeException('No Mangopay SCA session url found.');
        }
        $scaSessionUrlWithRedirect =
            "{$mangopayObject->PendingUserAction?->RedirectUrl}&"
            . http_build_query(['returnUrl' => $returnUrl]);
        if (
            str_contains($scaSessionUrl, self::MANGOPAY_SCA_URLS['sandbox'])
            || str_contains($scaSessionUrl, self::MANGOPAY_SCA_URLS['prod'])
        ) {
            return $scaSessionUrlWithRedirect;
        } else {
            throw new \RuntimeException(
                "Unknown Mangopay SCA session url: {$scaSessionUrlWithRedirect}",
            );
        }
    }
}
