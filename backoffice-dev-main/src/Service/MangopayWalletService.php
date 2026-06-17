<?php

namespace App\Service;

use MangoPay\FilterBankAccounts;
use MangoPay\FilterCards;
use MangoPay\FilterRecipients;
use MangoPay\FilterTransactions;
use MangoPay\FilterWallets;
use MangoPay\MangoPayApi;
use MangoPay\Pagination;
use MangoPay\Sorting;
use Psr\Log\LoggerInterface;

class MangopayWalletService
{
    /**
     * Prototype refactor of Mangopay service
     * - Offer a high level abstraction of wallet related activites
     *
     * Proposed style
     * - Methods for creating the relevant Mangopay objects, e.g. settlementTransfer
     *   - These return the relevant Mangopay object (type hint) with predefined and customised configurations
     *   - These are intended to make it easy to unit test
     * - Domain-specific methods (probably defined in an Interface) that will
     *   - Call the aforemetnioned build methods
     *   - Send the requests off to Mangopay
     *   - Return the response
     * - Can optionally wrap the sending methods for easy mocking, but we can just mock our domain-specific methods
     *   - This is what the existing Mangopay service does
     */

    public function __construct(
        private LoggerInterface $logger,
        public MangoPayApi $mangopayApi,
    ) {
        $this->logger = $logger;
        $this->mangopayApi = $mangopayApi;
    }

    /**
     * @return \MangoPay\RateLimit[]
     */
    public function getRateLimits(): array
    {
        return $this->mangopayApi->RateLimits ?? [];
    }

    public function retrieveClient(): \Mangopay\Client
    {
        return $this->mangopayApi->Clients->Get();
    }

    public function closeUser(\MangoPay\UserNaturalSca|\MangoPay\UserLegalSca $user): void
    {
        $this->mangopayApi->Users->Close($user);
    }

    public function getScaUser(string $userId): \MangoPay\UserNaturalSca|\MangoPay\UserLegalSca
    {
        return $this->mangopayApi->Users->GetSca($userId);
    }

    public function getUserRegulatory(string $userId): \MangoPay\UserBlockStatus
    {
        $this->logger->debug('Retrieve Mangopay user regulatory status', [
            'id' => $userId,
        ]);
        return $this->mangopayApi->Users->GetRegulatory($userId);
    }

    public function updateScaUser(\MangoPay\UserNaturalSca|\MangoPay\UserLegalSca $user): \MangoPay\UserNaturalSca|\MangoPay\UserLegalSca
    {
        return $this->mangopayApi->Users->UpdateSca($user);
    }

    public function enrollUserSca(string $userId): \MangoPay\UserEnrollmentResult
    {
        return $this->mangopayApi->Users->Enroll($userId);
    }

    public function retrieveScaStatus(string $userId): \MangoPay\ScaStatus
    {
        return $this->mangopayApi->Users->GetScaStatus($userId);
    }

    public function manageUserScaConsent(string $userId): \Mangopay\UserConsent
    {
        return $this->mangopayApi->Users->ManageConsent($userId);
    }

    public function getWallet(
        string $walletId,
        ?string $scaContext = null,
    ): \MangoPay\Wallet {
        return $this->mangopayApi->Wallets->Get($walletId, $scaContext);
    }

    public function createWallet(\MangoPay\Wallet $wallet): \MangoPay\Wallet
    {
        return $this->mangopayApi->Wallets->Create($wallet);
    }

    public function updateWallet(\MangoPay\Wallet $wallet): \MangoPay\Wallet
    {
        return $this->mangopayApi->Wallets->Update($wallet);
    }

    /**
     * @return \MangoPay\Wallet[]
     */
    public function listUserWallets(
        string $userId,
        ?Pagination $pagination = null,
        ?Sorting $sorting = null,
        ?FilterWallets $filter = null,
    ): array {
        return $this->mangopayApi->Users->GetWallets(
            $userId,
            $pagination,
            $sorting,
            $filter,
        );
    }

    /**
     * List transactions authored by the Mangopay user
     * @return \MangoPay\Transaction[]
     */
    public function listUserTransactions(
        string $userId,
        ?Pagination $pagination = null,
        ?Sorting $sorting = null,
        ?FilterTransactions $filter = null,
    ): array {
        return $this->mangopayApi->Users->GetTransactions(
            $userId,
            $pagination,
            $filter,
            $sorting,
        );
    }

    /**
     * List transactions involving a specific Mangopay wallet (debit or credit)
     * @return \MangoPay\Transaction[]
     */
    public function listWalletTransactions(
        string $walletId,
        ?Pagination $pagination = null,
        ?Sorting $sorting = null,
        ?FilterTransactions $filter = null,
    ): array {
        return $this->mangopayApi->Wallets->GetTransactions(
            $walletId,
            $pagination,
            $filter,
            $sorting,
        );
    }

    public function createTransfer(\MangoPay\Transfer $transfer): \MangoPay\Transfer
    {
        return $this->mangopayApi->Transfers->Create($transfer);
    }

    public function getTransfer(string $transferId): \MangoPay\Transfer
    {
        return $this->mangopayApi->Transfers->Get($transferId);
    }

    /** @return \MangoPay\Refund[] */
    public function listTransferRefunds(string $transferId): array
    {
        return $this->mangopayApi->Transfers->GetRefunds($transferId);
    }

    public function createTransferRefund(
        string $transferId,
        \MangoPay\Refund $refund,
    ): \MangoPay\Refund {
        return $this->mangopayApi->Transfers->createRefund($transferId, $refund);
    }

    public function getRefund(string $refundId): \MangoPay\Refund
    {
        return $this->mangopayApi->Refunds->Get($refundId);
    }

    public function getKycDocument(string $kycDocumentId): \MangoPay\KycDocument
    {
        return $this->mangopayApi->KycDocuments->Get($kycDocumentId);
    }

    public function createBankAccount(
        string $userId,
        \Mangopay\BankAccount $bankAccount,
    ): \Mangopay\BankAccount {
        return $this->mangopayApi->Users->createBankAccount($userId, $bankAccount);
    }

    public function retrieveBankAccount(
        string $userId,
        string $bankAccountId,
    ): \Mangopay\BankAccount {
        return $this->mangopayApi->Users->getBankAccount($userId, $bankAccountId);
    }

    public function updateBankAccount(
        string $userId,
        \Mangopay\BankAccount $bankAccount,
    ): \Mangopay\BankAccount {
        return $this->mangopayApi->Users->updateBankAccount($userId, $bankAccount);
    }

    /**
     * @return \MangoPay\BankAccount[]
     */
    public function listUserBankAccounts(
        string $userId,
        Pagination $pagination,
        Sorting $sorting,
        ?FilterBankAccounts $filter = null,
    ): array {
        return $this->mangopayApi->Users->GetBankAccounts(
            $userId,
            $pagination,
            $sorting,
            $filter,
        );
    }

    public function getReport(string $reportId): \MangoPay\ReportRequest
    {
        return $this->mangopayApi->Reports->Get($reportId);
    }

    public function retrieveRecipientSchema(
        string $payoutMethod,
        string $recipientType,
        string $currency,
        string $country,
    ): \Mangopay\RecipientSchema {
        return $this->mangopayApi->Recipients->GetSchema(
            $payoutMethod,
            $recipientType,
            $currency,
            $country,
        );
    }

    public function retrieveRecipient(string $recipientId): \Mangopay\Recipient
    {
        return $this->mangopayApi->Recipients->Get($recipientId);
    }

    /**
     * @return \MangoPay\Recipient[]
     */
    public function listUserRecipients(
        string $userId,
        Pagination $pagination,
        Sorting $sorting,
        ?FilterRecipients $filter = null,
    ): array {
        return $this->mangopayApi->Recipients->GetUserRecipients(
            $userId,
            $pagination,
            $sorting,
            $filter,
        );
    }

    /**
     * Doesn't actually return anything useful
     * Instead, will throw exception if not valid or some other error
     */
    public function validateRecipient(
        string $userId,
        \Mangopay\Recipient $recipient,
    ): ?object {
        return $this->mangopayApi->Recipients->Validate($recipient, $userId);
    }

    public function createRecipient(
        string $userId,
        \Mangopay\Recipient $recipient,
    ): \Mangopay\Recipient {
        return $this->mangopayApi->Recipients->Create($recipient, $userId);
    }

    public function deactivateRecipient(string $recipientId): \Mangopay\Recipient
    {
        return $this->mangopayApi->Recipients->Deactivate($recipientId);
    }

    public function retrievePayoutMethods(
        string $currency,
        string $country,
    ): \Mangopay\PayoutMethods {
        return $this->mangopayApi->Recipients->GetPayoutMethods($country, $currency);
    }

    public function retrievePayin(string $payinId): \Mangopay\PayIn
    {
        return $this->mangopayApi->PayIns->Get($payinId);
    }

    public function retrieveCard(string $cardId): \Mangopay\Card
    {
        return $this->mangopayApi->Cards->Get($cardId);
    }

    /** @return \MangoPay\Card[] */
    public function listUserCards(
        string $userId,
        bool $active = true,
        ?Pagination $pagination = null,
    ): array {
        if ($pagination === null) {
            $pagination = new Pagination();
        }
        $filter = new FilterCards();
        $filter->Active = $active ? 'true' : 'false';
        return $this->mangopayApi->Users->GetCards($userId, $pagination, $filter);
    }

    public function deactivateCard(\MangoPay\Card $card): \Mangopay\Card
    {
        $card->Active = false;
        return $this->mangopayApi->Cards->Update($card);
    }
}
