<?php

namespace App\Service;

use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\BankAccount as MangopayBankAcount;
use MangoPay\Recipient;
use Psr\Log\LoggerInterface;

class BankAccountSyncService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private BankAccountRepository $bankAccountRepository,
        private BankAccountService $bankAccountService,
        private MangopayWalletService $walletService,
    ) {}

    /**
     * @return BankAccount[]
     */
    public function syncBankAccounts(
        User $user,
        int $limit,
        bool $useRecipients = true,
    ): array {
        $registrations = [];
        $source = match ($useRecipients) {
            true => $this->listRecentActiveRecipients($user),
            default => $this->listRecentActiveBankAccounts($user),
        };
        $toSync = $this->filterUnsyncedRecipients(
            $this->getUserSyncedRecipientIds($user),
            $source,
            $limit,
        );
        $syncedIds = [];
        foreach ($toSync as $providerId) {
            $mangopayRecord = $this->walletService->retrieveRecipient($providerId);
            $bankAccount = $this->mapActiveMangopayRecipient($user, $mangopayRecord);
            $this->em->persist($bankAccount);
            $registrations[] = $bankAccount;
            $syncedIds[] = $providerId;
        }
        $user->setBankAccountsSyncedAt(new \DateTime());
        $this->logger->info('Successfully synced bank accounts', [
            'user' => $user->getId(),
            'lastSynced' => $user->getBankAccountsSyncedAt()->format(\DateTime::ATOM),
            'count' => count($syncedIds),
            'syncedIds' => $syncedIds,
        ]);
        return $registrations;
    }

    /**
     * @return string[]
     */
    public function getUserSyncedRecipientIds(User $user): array
    {
        $syncedIds = [];
        foreach ($user->getBankAccounts() as $account) {
            if (
                $account->getProviderId()
                && !in_array($account->getStatus(), [
                    BankAccountStatus::Closed,
                    BankAccountStatus::Rejected,
                ])
            ) {
                $syncedIds[$account->getId()] = $account->getProviderId();
            }
        }
        // $this->logger->debug("Aldready synced for user #{$user->getId()}:", $syncedIds);
        return $syncedIds;
    }

    /**
     * @param string[] $alreadySynced
     * @param iterable<Recipient|MangopayBankAcount> $recipients
     * @return string[]
     */
    public function filterUnsyncedRecipients(
        array $alreadySynced,
        iterable $recipients,
        int $batchSize = 5,
    ): array {
        $toSyncIds = [];
        foreach ($recipients as $rec) {
            if (!in_array($rec->Id, $alreadySynced)) {
                $toSyncIds[] = $rec->Id;
            }
        }
        // $this->logger->debug("Ids to sync", $toSyncIds);
        return array_slice($toSyncIds, 0, $batchSize);
    }

    public function mapActiveMangopayRecipient(
        User $user,
        Recipient $recipient,
    ): BankAccount {
        if ($recipient->Status != 'ACTIVE') {
            throw new \InvalidArgumentException(
                'Recipient to map to bank account must be active',
            );
        }
        $bankAccount = new BankAccount();
        ['bic' => $bic, 'accountNumber' => $accountNumber] =
            $this->extractAccountDetailsForFingerprint($recipient);
        $bankAccount->setBankIdentifierCode($bic);
        $bankAccount->setAccountNumber($accountNumber);

        $bankAccount->setUser($user);
        $bankAccount->setProviderId($recipient->Id);
        $bankAccount->setStatus(BankAccountStatus::Active);
        $bankAccount->setDescription($recipient->Tag);

        $bankAccount->setCountry($recipient->Country);
        $bankAccount->setCurrency($recipient->Currency);
        $bankAccount->setAccountType(match ($recipient->Country) {
            'GB' => BankAccountType::GB,
            default => BankAccountType::International,
        });

        // Generate the displayname and fingerprint
        $bankAccount->setDisplayName($this->bankAccountService->createDisplayName(
            $bankAccount,
        ));
        $bankAccount->setFingerprint($this->bankAccountService->getFingerprint(
            $bankAccount,
        ));

        // Clear out the account details
        $bankAccount->setAccountNumber(null);
        $bankAccount->setBankIdentifierCode(null);

        return $bankAccount;
    }

    public function loadAccountDetails(BankAccount $bankAccount): BankAccount
    {
        // Only load for active accounts
        if (
            $bankAccount->getStatus() === BankAccountStatus::Active
            && $bankAccount->getProviderId()
        ) {
            try {
                $recipient = $this->walletService->retrieveRecipient($bankAccount->getProviderId());
                ['bic' => $bic, 'accountNumber' => $accountNumber] =
                    $this->extractAccountDetailsForFingerprint($recipient);
                $bankAccount->setAccountNumber($accountNumber);
                $bankAccount->setBankIdentifierCode($bic);
            } catch (\Throwable $th) {
                $this->logger->error('Unable to retrieve Mangopay recipient for active bank account registration', [
                    'registrationId' => $bankAccount->getId(),
                    'mangopayId' => $bankAccount->getProviderId(),
                ]);
            }
        }
        return $bankAccount;
    }

    /**
     * @return array{accountNumber: string|null, bic: string|null}
     */
    private function extractAccountDetailsForFingerprint(Recipient $recipient): array
    {
        $details = match ($recipient->PayoutMethodType) {
            // Local bank transfer is nested structure, so use json encode decode to convert to associative array
            'LocalBankTransfer' => json_decode(
                json_encode($recipient->LocalBankTransfer),
                true,
            )[$recipient->Currency],
            // International bank transfer is single level, so can just type cast
            'InternationalBankTransfer'
                => (array) $recipient->InternationalBankTransfer,
            default => [],
        };
        // $this->logger->debug("Recipient detail fields", array_keys($details) ?? []);

        // Special case BIC extraction for older Mangopay bank account types
        if ($recipient->Country == 'GB' && array_key_exists('SortCode', $details)) {
            $bic = (string) $details['SortCode'];
        }
        if ($recipient->Country == 'US' && array_key_exists('ABA', $details)) {
            $bic = (string) $details['ABA'];
        }
        if (
            $recipient->Country == 'CA'
            && array_key_exists('InstitutionNumber', $details)
            && array_key_exists('BranchCode', $details)
        ) {
            $bic = (string) $details['InstitutionNumber'] . $details['BranchCode'];
        }

        // Mainly support AccountNumber, IBAN, BIC fields. These will take precedence over the legacy special cases
        if (array_key_exists('AccountNumber', $details)) {
            $accountNumber = (string) $details['AccountNumber'];
        }
        if (array_key_exists('IBAN', $details)) {
            $accountNumber = (string) $details['IBAN'];
        }
        if (array_key_exists('BIC', $details)) {
            $bic = (string) $details['BIC'];
        }
        return ['bic' => $bic ?? null, 'accountNumber' => $accountNumber ?? null];
    }

    private function listRecentActiveBankAccounts(User $user, int $limit = 10): array
    {
        $pagination = new \MangoPay\Pagination();
        $pagination->Page = 1;
        $pagination->ItemsPerPage = $limit;
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('CreationDate', 'DESC');
        $filterBankAccounts = new \MangoPay\FilterBankAccounts();
        $filterBankAccounts->Active = 'true';
        $mangopayBankAccounts = $this->walletService->listUserBankAccounts(
            $user->getMangoPayUserId(),
            $pagination,
            $sorting,
            $filterBankAccounts,
        );
        return $mangopayBankAccounts;
    }

    private function listRecentActiveRecipients(User $user, int $limit = 10): array
    {
        $pagination = new \MangoPay\Pagination();
        $pagination->Page = 1;
        $pagination->ItemsPerPage = $limit;
        $sorting = new \MangoPay\Sorting();
        $sorting->AddField('CreationDate', 'DESC');
        $mangopayRecipients = $this->walletService->listUserRecipients(
            $user->getMangoPayUserId(),
            $pagination,
            $sorting,
        );
        $mangopayRecipients = array_filter(
            $mangopayRecipients,
            fn(Recipient $r): bool => $r->Status == 'ACTIVE',
        );
        return $mangopayRecipients;
    }
}
