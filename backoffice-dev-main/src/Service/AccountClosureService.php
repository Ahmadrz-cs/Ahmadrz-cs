<?php

namespace App\Service;

use App\Entity\Enum\AccountCleanupAction;
use App\Entity\Enum\AccountClosureRestriction;
use App\Entity\Enum\AccountRetentionLevel;
use App\Entity\Enum\UserStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Entity\UserStatusLog;
use App\Repository\ContegoLogRepository;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Service\Manager\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class AccountClosureService
{
    public const DEFAULT_CACHE_TAG = 'account_closure';
    public const DEFAULT_CACHE_TTL = 300;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private Security $security,
        private TagAwareCacheInterface $defaultAppCache,
        private ContegoLogRepository $contegoLogRepository,
        private HoldingRepository $holdingRepository,
        private InvestmentRepository $investmentRepository,
        private MangopayWalletService $mangopayWalletService,
        private SalesforceService $salesforceService,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
    ) {}

    /**
     * @return AccountClosureRestriction[]
     */
    public function getAccountClosureRestrictions(User $user): array
    {
        $restrictions = [];

        $userInvestments = $this->investmentRepository->findByWithAssociations([
            'userId' => $user->getId(),
            'lifecycleStatus' => [
                InvestmentLifecycle::STATE_APPROVED,
                InvestmentLifecycle::STATE_SETTLED,
            ],
        ]);
        // If Mangopay is closeable - do additional checks
        // Otherwise, additional Mangopay checks not possible due to blocked account
        $mangopayClosable = $this->isMangopayUserStatusCloseable($user);
        if ($userInvestments->count() > 0) {
            $restrictions[] = AccountClosureRestriction::MangopayUser;
            $restrictions[] = AccountClosureRestriction::Investments;
            $restrictions[] = AccountClosureRestriction::Transactions;
        } else {
            if ($user->getMangoPayUserId()) {
                // Doesn't actually matter if the Mangopay user is already closed or not
                // This check is simply for whether they have or had a Mangopay user
                $restrictions[] = AccountClosureRestriction::MangopayUser;
            }

            // Can only check for transactions if Mangopay account not closed
            if ($mangopayClosable && !$this->hasNoMangopayTransactions($user)) {
                $restrictions[] = AccountClosureRestriction::Transactions;
            }
        }

        // Staff accounts cannot be closed - demote them first
        // https://symfony.com/blog/new-in-symfony-7-3-arbitrary-user-permission-checks
        if ($this->security->isGrantedForUser($user, 'ROLE_ANALYST')) {
            $restrictions[] = AccountClosureRestriction::Staff;
        }

        // Active shareholders cannot close their account
        $currentHoldings = $this->holdingRepository->getShareHoldings([
            'currentHolding' => 1,
            'capitalRepayments' => false,
            'userId' => $user->getId(),
        ]);
        if (count($currentHoldings) > 0) {
            $restrictions[] = AccountClosureRestriction::Shareholder;
        }

        // Wallet balance prevents us from closing a Mangopay user account, so is a separate check
        // Can only check for wallet balance if Mangopay account not closed
        if ($mangopayClosable && !$this->isMangopayWalletEmpty($user)) {
            $restrictions[] = AccountClosureRestriction::WalletBalance;
        }
        return $restrictions;
    }

    /**
     * @param AccountClosureRestriction[] $restrictions
     */
    public function canCloseAccount(array $restrictions): bool
    {
        if (
            !empty(array_uintersect(
                AccountClosureRestriction::hardRestrictions(),
                $restrictions,
                fn($r1, $r2) => $r1->value <=> $r2->value,
            ))
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param AccountClosureRestriction[] $restrictions
     */
    public function getRetentionLevel(array $restrictions): AccountRetentionLevel
    {
        if (!$this->canCloseAccount($restrictions)) {
            // Active shareholder or staff, can't really delete anything
            return AccountRetentionLevel::Full;
        }
        if (
            in_array(AccountClosureRestriction::Transactions, $restrictions)
            || in_array(AccountClosureRestriction::Investments, $restrictions)
        ) {
            // Some financial activity, so need to retain PII for AML purposes
            return AccountRetentionLevel::AML;
        }
        if (in_array(AccountClosureRestriction::MangopayUser, $restrictions)) {
            // Never engaged financially, so just need to keep the Mangopay ID
            return AccountRetentionLevel::Wallet;
        }
        // No Mangopay account, can do a full wipe
        return AccountRetentionLevel::None;
    }

    public function generateAnonymisedUsername(User $user): string
    {
        $timestamp = new \DateTime()->format('Ymd_His');
        return "{$user->getId()}_$timestamp@closed.example.com";
    }

    public function getMangopayUserStatus(User $user): ?string
    {
        if (!empty($user->getMangoPayUserId())) {
            $cachedStatus = $this->defaultAppCache->get(
                "mangopayUserStatus_{$user->getId()}_{$user->getMangoPayUserId()}",
                function (ItemInterface $item) use ($user): array {
                    $item->expiresAfter(self::DEFAULT_CACHE_TTL);
                    $item->tag([self::DEFAULT_CACHE_TAG]);

                    $status = null;
                    try {
                        $mangopayUser = $this->mangopayWalletService->getScaUser($user->getMangopayUserId());
                        $status = $mangopayUser->UserStatus;
                    } catch (\Throwable $th) {
                        $this->logger->error(
                            "Error retrieving Mangopay user #{$user->getId()}",
                            [$th->getMessage()],
                        );
                    }
                    return [
                        'status' => $status,
                        'lastChecked' => new \Datetime()->format(\DateTimeInterface::RFC2822),
                    ];
                },
            );
            $this->logger->debug('Cached Mangopay user status', $cachedStatus);
            return $cachedStatus['status'];
        }
        return null;
    }

    public function hasSalesforceContact(User $user): bool
    {
        $salesforceId = $user->findCustomFieldValue('salesforce_id');
        if (empty($salesforceId)) {
            return false;
        }
        try {
            $response = $this->salesforceService->retrieve('Contact', $salesforceId);
            $this->logger->debug('Salesforce contact retrieve status: '
                . $response->getStatusCode(), [json_decode(
                $response->getBody(),
                true,
            )]);
        } catch (\GuzzleHttp\Exception\RequestException $th) {
            $this->logger->debug('Salesforce contact retrieve status: '
                . $th->getResponse()?->getStatusCode(), [json_decode(
                $th->getResponse()?->getBody(),
                true,
            )]);
            if ($th->getResponse()?->getStatusCode() === Response::HTTP_NOT_FOUND) {
                return false;
            }
        } catch (\Throwable $th) {
            $this->logger->debug(
                'Salesforce contact retrieve error: ' . $th->getMessage(),
            );
        }
        return true;
    }

    /**
     * Process cleanup actions
     * @param AccountCleanupAction[] $actions
     * @return AccountCleanupAction[]
     */
    public function cleanupData(User $user, array $actions): array
    {
        // $this->logger->debug("Actions selected: " . json_encode($actions));
        if (!$this->isUserBlocked($user)) {
            return [];
        }
        $processedActions = [];
        foreach ($actions as $action) {
            $outcome = match ($action) {
                AccountCleanupAction::Identity => $this->cleanupIdentity($user),
                AccountCleanupAction::Contact => $this->cleanupContact($user),
                AccountCleanupAction::Address => $this->cleanupAddress($user),
                AccountCleanupAction::Documents => $this->cleanupDocuments($user),
                AccountCleanupAction::Logs => $this->cleanupLogs($user),
                AccountCleanupAction::Onboarding => $this->cleanupOnboarding($user),
                AccountCleanupAction::AdditionalFields
                    => $this->cleanupAdditionalFields($user),
                AccountCleanupAction::Company => $this->cleanupCompany($user),
                AccountCleanupAction::Kyc => $this->cleanupKyc($user),
                AccountCleanupAction::Salesforce => $this->closeSalesforce($user),
                default => false,
            };
            if ($outcome) {
                $processedActions[] = $action;
            }
        }
        // Due to importance of the username, this will be processed towards the end
        if (in_array(AccountCleanupAction::Username, $actions)) {
            $outcome = $this->cleanupUsername($user);
            if ($outcome) {
                $processedActions[] = AccountCleanupAction::Username;
            }
        }
        // Due to importance of Mangopay account, this should also be processed towards the end
        if (in_array(AccountCleanupAction::Mangopay, $actions)) {
            $outcome = $this->closeMangopay($user);
            if ($outcome) {
                $processedActions[] = AccountCleanupAction::Mangopay;
            }
        }
        // $this->logger->debug("Actions processed: " . json_encode($actions));
        return $processedActions;
    }

    public function toggleAccountBlock(User $user): User
    {
        if ($user->isEnabled() && !$user->isSuperAdmin()) {
            $user->setEnabled(false);
            $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
            if (!in_array($user->getCurrentStatus(), UserStatus::inactive())) {
                $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));
            }
        } else {
            $user->setEnabled(true);

            // Reverting the status toggle is a bit difficult as we don't have a full record of lifecycleStatus history
            // Best effort reversion, but BizOps will need to fix if necessary
            $latestState = UserLifecycle::STATE_EMAIL_NOT_VERIFIED;
            if ($user->getStatus()->getIsEmailVerified()) {
                $latestState = UserLifecycle::STATE_EMAIL_VERIFIED;
            }
            if ($user->getStatus()->getIsRegCompleted()) {
                $latestState = UserLifecycle::STATE_REGISTRATION_COMPLETE;
            }
            if ($user->getStatus()->getApprovedOn()) {
                $latestState = UserLifecycle::STATE_APPROVED;
            }
            $user->getStatus()->setLifecycleStatus($latestState);

            if (in_array($user->getCurrentStatus(), UserStatus::inactive())) {
                // If user in inactive state, reactivate it
                // Get most recent active states
                $activeStates = $user
                    ->getStatusLogs()
                    ->map(
                        static fn(UserStatusLog $log): UserStatus => $log->getStatus(),
                    )
                    ->filter(static fn(UserStatus $s): bool => !in_array(
                        $s,
                        UserStatus::inactive(),
                    ));

                $user->addStatusLog(
                    new UserStatusLog(
                        status: $activeStates->isEmpty()
                            ? UserStatus::Pending
                            : $activeStates->last(),
                    ),
                );
            }
        }
        return $user;
    }

    private function isUserBlocked(User $user): bool
    {
        if ($user->isEnabled()) {
            return false;
        }
        if ($user->getLifecycleStatus() !== UserLifecycle::STATE_BLOCKED) {
            return false;
        }
        if (!in_array($user->getCurrentStatus(), UserStatus::inactive())) {
            return false;
        }
        return true;
    }

    private function closeMangopay(User $user): bool
    {
        if (!$this->canCloseMangopay($user)) {
            return false;
        }
        /**
         * Call Mangopay to close
         * Due to the finality, best to do this as one of the last items
         * You can technically reopen by creating a new user
         * But you'll need to go through KYC and SCA again
         */
        try {
            $mangopayUser = $this->mangopayWalletService->getScaUser($user->getMangopayUserId());
            $this->mangopayWalletService->closeUser($mangopayUser);
            $this->defaultAppCache->delete(
                "mangopayUserStatus_{$user->getId()}_{$user->getMangoPayUserId()}",
            );
        } catch (\Mangopay\Libraries\ResponseException $e) {
            $this->logger->error(
                "Error retrieving and closing Mangopay user #{$user->getId()}",
                [
                    'errors' => $e->GetErrorDetails()->Errors,
                ],
            );
        } catch (\Throwable $th) {
            $this->logger->error(
                "Error retrieving and closing Mangopay user #{$user->getId()}",
                [$th->getMessage()],
            );
            return false;
        }
        return true;
    }

    private function closeSalesforce(User $user): bool
    {
        if (!$this->canCloseSalesforce($user)) {
            $this->logger->debug("Can't close Salesforce");
            return false;
        }
        // Call Salesforce to delete the contact
        $salesforceIdField = $user->findCustomField('salesforce_id');
        $salesforceId = $salesforceIdField?->getFieldValue();
        if (is_null($salesforceId)) {
            $this->logger->debug('No Salesforces id');
            return false;
        }
        try {
            // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/dome_delete_record.htm
            // https://developer.salesforce.com/docs/atlas.en-us.api_rest.meta/api_rest/errorcodes.htm
            // No response body, only status code 204
            $response = $this->salesforceService->delete('Contact', $salesforceId);
            $this->logger->debug(
                "Salesforce delete response for user #{$user->getId()}",
                [
                    'sfId' => $salesforceId,
                    'statusCode' => $response->getStatusCode(),
                ],
            );
            if ($response->getStatusCode() >= 200 || $response->getStatusCode() < 300) {
                // $user->removeCustomField($salesforceIdField);
                $this->logger->info(
                    "Successfully deleted Salesforce contact for user #{$user->getId()}",
                );
                return true;
            } else {
                $this->logger->error(
                    "Unable to delete Salesforce contact for user #{$user->getId()}",
                );
                return false;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "Unable to delete Salesforce contactfor user #{$user->getId()}",
            );
            return false;
        }
    }

    /**
     * Checks whether the status of the user's Mangopay account is not CLOSED (either PENDING_USER_ACTION or ACTIVE)
     */
    private function isMangopayUserStatusCloseable(User $user): bool
    {
        if (is_null($user->getMangoPayUserId())) {
            // Nothing to close
            return false;
        }
        try {
            $status = $this->getMangopayUserStatus($user);
            $this->logger->debug(
                "Checking if Mangopay user #{$user->getId()} is closeable",
                ['status' => $status],
            );
            if ($status === null || $status == 'CLOSED') {
                return false;
            }
        } catch (\Throwable $th) {
            $this->logger->error(
                "Error retrieving Mangopay user #{$user->getId()}",
                [$th->getMessage()],
            );
            return false;
        }
        return true;
    }

    /**
     * Doesn't distinguish between whether there is a Mangopay user at all or non-empty wallet
     * In either case, you cannot call close on the Mangopay user
     */
    private function canCloseMangopay(User $user): bool
    {
        /**
         * Requirements
         * - User must be blocked (from logging in)
         * - User must have a Mangopay user id
         * - User must have a Mangopay wallet id
         * - Mangopay user must not already be closed
         * - Mangopay wallet must be empty
         */
        if (!$this->isUserBlocked($user)) {
            return false;
        }
        if (is_null($user->getMangoPayUserId())) {
            // Nothing to close
            return false;
        }
        if (is_null($user->getMangoPayWalletId())) {
            // Can't check whether wallet is empty
            return false;
        }
        if (!$this->isMangopayUserStatusCloseable($user)) {
            return false;
        }
        if (!$this->isMangopayWalletEmpty($user)) {
            return false;
        }
        return true;
    }

    private function canCloseSalesforce(User $user): bool
    {
        if (!$this->isUserBlocked($user)) {
            return false;
        }
        $salesforceId = $user->findCustomFieldValue('salesforce_id');
        if (empty($salesforceId)) {
            return false;
        }
        return true;
    }

    private function isMangopayWalletEmpty(User $user): bool
    {
        if (is_null($user->getMangoPayWalletId())) {
            /**
             * No wallet to check
             * It is possible for a user to have multiple wallets and not be linked in CMS
             * So this check isn't guaranteed
             */
            return true;
        }
        try {
            $wallet = $this->mangopayWalletService->getWallet(
                $user->getMangoPayWalletId(),
                'USER_NOT_PRESENT',
            );
            $this->logger->debug(
                "Checking if Mangopay user #{$user->getId()} main wallet is empty",
                [
                    'balance' => $wallet->Balance->Amount,
                ],
            );
            if ($wallet->Balance->Amount > 0) {
                return false;
            }
        } catch (\Throwable $th) {
            $this->logger->error(
                "Error retrieving Mangopay wallet(s) for user #{$user->getId()}",
                [$th->getMessage()],
            );
        }
        return true;
    }

    private function hasNoMangopayTransactions(User $user): bool
    {
        if (is_null($user->getMangoPayUserId())) {
            return true;
        }
        try {
            // Only need to check if non-zero - limit page to reduce unnecessary traffic
            $pagination = new \MangoPay\Pagination(1, 1);
            $filter = new \MangoPay\FilterTransactions();
            $filter->ScaContext = 'USER_NOT_PRESENT';
            $filter->Status = \MangoPay\TransactionStatus::Succeeded;
            $transactions = $this->mangopayWalletService->listWalletTransactions(
                $user->getMangoPayWalletId(),
                $pagination,
                null,
                $filter,
            );
            $this->logger->debug(
                "Checking if Mangopay user #{$user->getId()} has any transactions",
                ['minCount' => count($transactions)],
            );
            if (count($transactions) > 0) {
                return false;
            }
        } catch (\Throwable $th) {
            $this->logger->error(
                "Error retrieving Mangopay transactions for user #{$user->getId()}",
                [$th->getMessage()],
            );
        }
        return true;
    }

    private function cleanupUsername(User $user): bool
    {
        $user->setUsername($this->generateAnonymisedUsername($user));
        return true;
    }

    private function cleanupIdentity(User $user): bool
    {
        $user->setFirstname(null);
        $user->setLastname(null);
        $user->setMiddlename(null);
        $user->setAdditionalName(null);
        $user->setGender(null);
        $user->setType(null);
        $user->setHonoricPrefix(null);
        $user->setHonoricSuffix(null);
        $user->setJobTitle(null);
        $user->setLocation(null);
        $user->setNationality(null);
        $user->setBirthDate(null);
        $user->setBirthCountry(null);
        $user->setBirthPlace(null);
        $user->setDrivingLicenseNo(null);
        $user->setPassportCountry(null);
        $user->setPassportExpiry(null);
        $user->setPassportNumber(null);
        $user->setIncomeRange(null);
        $user->setOccupation(null);
        $user->setAffiliateCode(null);
        $user->setBiography(null);
        $user->setReferralCode(null);
        $user->setSector(null);
        $user->setTagline(null);
        $user->setTaxId(null);
        $user->setTimezone(null);
        $user->setWebsite(null);

        $investor = $user->getInvestor();
        $investor->setWordsOfOwn(null);
        return true;
    }

    private function cleanupContact(User $user): bool
    {
        $user->setEmail($this->generateAnonymisedUsername($user));
        $user->setMobile(null);
        $user->setPhone1(null);
        $user->setPhone2(null);
        return true;
    }

    private function cleanupAddress(User $user): bool
    {
        $user->getAddresses()->clear();
        return true;
    }

    private function cleanupDocuments(User $user): bool
    {
        // Delete documents one by one rather than using clear()
        // Note that clear() required orphanRemoval=true to work
        foreach ($user->getDocuments() as $document) {
            try {
                if ($document->getDocument()->getDocumentUrl()) {
                    $this->documentService->delete(
                        $document->getDocument()->getDocumentUrl(),
                        $this->documentManager->getVisibilityForDocType('user'),
                    );
                }
                $this->em->remove($document);
            } catch (\Throwable $e) {
                $this->logger->error(
                    "Could not delete user document #{$document->getId()}"
                        . $e->getMessage(),
                );
            }
        }
        // Clear out from user
        $user->getDocuments()->clear();
        return true;
    }

    private function cleanupLogs(User $user): bool
    {
        $user->getCommunication()->clear();
        $user->getLogs()->clear();
        return true;
    }

    private function cleanupOnboarding(User $user): bool
    {
        $user->getOnboardingProfile()->getCategorisations()->clear();
        // Could alternatively just delete/nullify all the details
        return true;
    }

    private function cleanupCompany(User $user): bool
    {
        $company = $user->getCompany();
        if ($company) {
            $company->setName(null);
            $company->setPosition(null);
            $company->setRegAddress1(null);
            $company->setRegAddress2(null);
            $company->setRegAddress3(null);
            $company->setBeneficialOwners(null);
            $company->setDirectors(null);
            $company->setRegCountry(null);
            $company->setBusinessNature(null);
            $company->setTelephone(null);
            $company->setPostCode(null);
            $company->setBuildingName(null);
            $company->setRegistrationNumber(null);
            $company->setOtherName(null);
            $company->setCompanyWebsite(null);
            $company->setOperatingAddress(null);
            $company->setOperatingPostCode(null);
        }
        return true;
    }

    private function cleanupAdditionalFields(User $user): bool
    {
        $salesforceIdField = $user->findCustomField('salesforce_id');
        $user->getCustomFields()->clear();
        // Keep salesforce id if found
        if ($salesforceIdField) {
            $user->addCustomField($salesforceIdField);
        }
        return true;
    }

    private function cleanupKyc(User $user): bool
    {
        $contegoLogs = $this->contegoLogRepository->findBy(['user' => $user->getUserIdentifier()]);
        foreach ($contegoLogs as $log) {
            $this->em->remove($log);
        }
        return true;
    }
}
