<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\TransferRequest;
use App\Service\Manager\UserManagerV2;
use Psr\Log\LoggerInterface;

/**
 * High level abstraction for making transfers
 *
 * Transfers refers to the movement of money between the same entity/user
 * This includes cases where the entity is acting in proxy
 * - SPVA <-> SPVA
 * - SPVA <-> Yielders
 *
 * Can be argued that SPV <-> Yielders is a payment
 * Since the owner of both wallets is the same user, it's considered a transfer
 *
 * Fairly simple service that executes a transfer request
 *
 * - Create Mangopay transfer object
 * - Create Mangopay transfer (i.e. submit the object)
 * - Create Transaction object on success
 * - Throw exception on failure
 * - Return the Transaction object
 *
 * No persistence, state-management or updating of transfer orders/requests
 */
class TransferService
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $walletService,
        // Temporary way of getting superadmin as authorId
        // Do NOT use for anything else
        private UserManagerV2 $userManager,
    ) {}

    public function makeWalletTransfer(TransferRequest $transferRequest): Transaction
    {
        $transfer = $this->createWalletTransfer($transferRequest);
        // createTransfer will throw a \MangoPay\Libraries\ResponseException if there are issues
        // https://github.com/Mangopay/mangopay2-php-sdk#sample-usage
        $transfer = $this->walletService->createTransfer($transfer);
        if ('SUCCEEDED' == $transfer->Status) {
            $transaction = $this->createTransaction($transferRequest, $transfer);
            return $transaction;
        } else {
            throw new \Exception(
                'Transfer could not be made: ' . $transfer->ResultMessage,
                $transfer->ResultCode,
            );
        }
    }

    public function createWalletTransfer(TransferRequest $transferRequest): \MangoPay\Transfer
    {
        $debitedFunds = new \MangoPay\Money();
        $debitedFunds->Currency = 'GBP';
        // Convert amount to pence (lowest denomination)
        $debitedFunds->Amount = (int) round(
            100 * (float) $transferRequest->getAmount(),
        );

        $fees = new \MangoPay\Money();
        $fees->Amount = 0;
        $fees->Currency = 'GBP';

        $transfer = new \MangoPay\Transfer();
        $transfer->DebitedWalletId = $transferRequest->getDebitWalletId();
        $transfer->CreditedWalletId = $transferRequest->getCreditWalletId();
        $transfer->Tag = $transferRequest->getDescription();
        $transfer->DebitedFunds = $debitedFunds;
        $transfer->Fees = $fees;

        // Author of the transfer is currently always superadmin (i.e. system initiated)
        $transfer->AuthorId = $this->getDefaultTransferAuthorId();
        // Let Mangopay default creditUserId to owner of the creditWallet
        // $transfer->CreditedUserId = $this->getDefaultTransferAuthorId();

        // Explicitly state that this is an automated action and no SCA should be used
        $transfer->ScaContext = 'USER_NOT_PRESENT';

        if ($transferRequest->getInvestment()) {
            $transfer = $this->setInvestmentTags($transferRequest, $transfer);
        }
        return $transfer;
    }

    public function createTransaction(
        TransferRequest $transferRequest,
        \MangoPay\Transfer $walletTransfer,
    ): Transaction {
        $transaction = new Transaction();
        $transaction->setDebitResourceId($transferRequest->getDebitWalletId());
        $transaction->setCreditResourceId($transferRequest->getCreditWalletId());
        $transaction->setComments($transferRequest->getDescription());

        $transaction->setAmount((string) $walletTransfer->DebitedFunds->Amount);
        $transaction->setPaymentStatus((string) $walletTransfer->Status);
        $transaction->setReferenceId((string) $walletTransfer->Id);
        $transaction->setCurrency((string) $walletTransfer->DebitedFunds->Currency);
        $transaction->setType((string) $walletTransfer->Type);

        return $transaction;
    }

    private function setInvestmentTags(
        TransferRequest $transferRequest,
        \MangoPay\Transfer $walletTransfer,
    ): \MangoPay\Transfer {
        $asset = $transferRequest->getTransferOrder()?->getAsset();
        $investment = $transferRequest->getInvestment();
        // if no asset or investment set, then we can't do anything here, so just return the walletTransfer as is
        if (is_null($asset) || is_null($investment)) {
            return $walletTransfer;
        }
        // Create the base tag which will include the description at the start
        $tag = "Desc:{$transferRequest->getDescription()};AstName:{$asset->getName()};AstCode:{$asset->getCompanyNumber()}";
        // Add "type" tag at the end if prefunding or a relisting
        if ('prefunding' == $investment->getOffering()?->getOfferingType()) {
            $tag .= ';Type:Prefunding';
        } elseif (!is_null($investment->getOffering()?->getSellInvestment())) {
            $tag .= ';Type:Relisting';
        }
        $walletTransfer->Tag = $tag;
        return $walletTransfer;
    }

    private function getDefaultTransferAuthorId(): string
    {
        $user = $this->userManager->getSuperAdmin();
        return $user->getMangoPayUserId();
    }
}
