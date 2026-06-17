<?php

namespace App\Service;

use MangoPay\PayInPaymentType;
use Psr\Log\LoggerInterface;

class MangopayCardService
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $mangopayService,
    ) {}

    /**
     * Returns the cardId of a Card PayIn.
     * Returns null for any other type of PayIn or if there was an error.
     */
    public function getPayInCardId(string $payinId): ?string
    {
        try {
            $payin = $this->mangopayService->retrievePayin($payinId);
            // $this->logger->debug("Checking payin", ["payin" => $payin]);
            if ($payin->PaymentType == PayInPaymentType::Card) {
                return $payin->PaymentDetails?->CardId;
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                "Could not fetch payin {$payinId}",
                [$e->getMessage()],
            );
        }
        return null;
    }

    public function deactivateCardById(string $cardId): ?\Mangopay\Card
    {
        try {
            $card = $this->mangopayService->retrieveCard($cardId);
            // $this->logger->debug("Card to deactivate", ["card" => $card]);
            if ($card->Active) {
                $card = $this->mangopayService->deactivateCard($card);
                $this->logger->info("Card {$cardId} successfully deactivated");
            } else {
                $this->logger->info("Card {$cardId} already deactivated");
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                "Could not deactive card {$cardId}",
                [$e->getMessage()],
            );
        }
        return $card ?? null;
    }
}
