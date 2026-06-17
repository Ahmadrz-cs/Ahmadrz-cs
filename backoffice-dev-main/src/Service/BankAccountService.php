<?php

namespace App\Service;

use App\Dto\Sca\ScaActionResponseDto;
use App\Entity\BankAccount;
use App\Entity\Enum\ActionRequest;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountTransition;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use App\Service\MangopayWalletService;
use MangoPay\Address;
use MangoPay\BusinessRecipient;
use MangoPay\IndividualRecipient;
use MangoPay\Recipient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class BankAccountService
{
    public function __construct(
        private LoggerInterface $logger,
        private WorkflowInterface $bankAccountStateMachine,
        private BankAccountRepository $bankAccountRepository,
        private MangopayWalletService $walletService,
        private NotificationService $notificationService,
        private NormalizerInterface $snakeToCamelNormalizer,
        private string $teamAddress,
    ) {}

    public function getSchemaForRecipient(
        string $country,
        string $currency = 'GBP',
        string $payoutMethod = 'auto',
        string $recipientType = 'Individual',
    ): \Mangopay\RecipientSchema {
        if (strtolower($payoutMethod) == 'auto') {
            $payoutMethod = match ($country) {
                'GB' => 'LocalBankTransfer',
                default => 'InternationalBankTransfer',
            };
        } else {
            // Way of making safe the payout method as we're not using enums or doing validation
            $payoutMethod = match ($payoutMethod) {
                'LocalBankTransfer' => 'LocalBankTransfer',
                default => 'InternationalBankTransfer',
            };
        }
        // $this->logger->debug("schema for: ", [
        //     $payoutMethod,
        //     $recipientType,
        //     $currency,
        //     $country
        // ]);
        return $this->walletService->retrieveRecipientSchema(
            $payoutMethod,
            $recipientType,
            $currency,
            $country,
        );
    }

    public function getPayoutMethods(
        string $country,
        string $currency = 'GBP',
    ): \Mangopay\PayoutMethods {
        return $this->walletService->retrievePayoutMethods($currency, $country);
    }

    public function normalizeSchema(
        \Mangopay\RecipientSchema $schema,
        string $countryAlpha2,
    ): array {
        if ($countryAlpha2 == 'GB') {
            // For some reason local bank transfers support multiple currencies in Mangopay
            // So the schema is split by currency - GBP in our case
            // Although this could change
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->disableExceptionOnInvalidPropertyPath()
                ->getPropertyAccessor();
            // Not sure if GBP is an object or an array, so try both
            if ($propertyAccessor->isReadable($schema->LocalBankTransfer, 'GBP')) {
                $bankAccountSchema = $propertyAccessor->getValue(
                    $schema->LocalBankTransfer,
                    'GBP',
                );
            } elseif ($propertyAccessor->isReadable(
                $schema->LocalBankTransfer,
                '[GBP]',
            )) {
                $bankAccountSchema = $propertyAccessor->getValue(
                    $schema->LocalBankTransfer,
                    '[GBP]',
                );
            }
            if (is_null($bankAccountSchema)) {
                $bankAccountSchema = $schema->LocalBankTransfer;
            }
        } else {
            $bankAccountSchema = $schema->InternationalBankTransfer;
        }
        // $this->logger->debug("schema as array", [$bankAccountSchema, gettype($bankAccountSchema)]);
        $schemaArrayPartNormalized = $this->snakeToCamelNormalizer->normalize(
            $bankAccountSchema,
            'json',
        );
        $schemaArray = [];
        // convert keys to camelCase (from pascalCase)
        foreach ($schemaArrayPartNormalized as $key => $value) {
            $schemaArray[lcfirst($key)] = $value;
        }
        if (array_key_exists('bIC', $schemaArray)) {
            // Fix the incorrect lowercase conversion of BIC
            $schemaArray['bic'] = $schemaArray['bIC'];
            unset($schemaArray['bIC']);
        }
        if (array_key_exists('sortCode', $schemaArray)) {
            // Normalize the GB style accounts, so the keys are always the same
            $schemaArray['bic'] = $schemaArray['sortCode'];
            unset($schemaArray['sortCode']);
        }
        return $schemaArray;
    }

    public function getFingerprint(BankAccount $bankAccount): ?string
    {
        if (
            empty($bankAccount->getAccountNumber())
            || empty($bankAccount->getCountry())
            || empty($bankAccount->getCurrency())
        ) {
            return null;
        }
        return hash(
            algo: 'xxh128',
            data: (string) $bankAccount->getCurrency()
            . (string) $bankAccount->getCountry()
            . (string) $bankAccount->getBankIdentifierCode()
            . (string) $bankAccount->getAccountNumber(),
        );
    }

    public function getUserBankAccountRegistrations(User $user): iterable
    {
        return $this->bankAccountRepository
            ->buildQueryWithAssociations(
                [
                    'userId' => $user->getId(),
                    // 'status' => [BankAccountStatus::Pending, BankAccountStatus::Approved, BankAccountStatus::Active]
                ],
                ['id' => 'DESC'],
            )
            ->setMaxResults(5)
            ->getResult();
    }

    public function isNotDuplicated(BankAccount $bankAccount): bool
    {
        $existingMatches = $this->bankAccountRepository->buildQueryWithAssociations([
            'userId' => $bankAccount->getUser()->getId(),
            'fingerprint' => $this->getFingerprint($bankAccount),
            'status' => [
                BankAccountStatus::Pending,
                BankAccountStatus::Validated,
                BankAccountStatus::Approved,
                BankAccountStatus::Active,
            ],
        ])->getResult();
        // $this->logger->debug(count($existingMatches));
        return empty($existingMatches);
    }

    public function validateWithMangopay(BankAccount $bankAccount): array
    {
        $recipient = $this->createMangopayRecipientObject($bankAccount);
        $errors = [];
        try {
            $this->walletService->validateRecipient(
                $bankAccount->getUser()->getMangoPayUserId(),
                $recipient,
            );
            if ($bankAccount->getStatus() == BankAccountStatus::Pending) {
                $bankAccount->setStatus(BankAccountStatus::Validated);
            }

            // $this->logger->debug(
            //     "Validation succeeded for bankAccount",
            //     ['recipient' => $recipient]
            // );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->warning('Validation failed for bankAccount', [
                'errors' => $th->GetErrorDetails()->Errors,
            ]);
            $errors = json_decode(json_encode($th->GetErrorDetails()->Errors), true);
            if ($bankAccount->getStatus() == BankAccountStatus::Validated) {
                $bankAccount->setStatus(BankAccountStatus::Pending);
            }
        } catch (\Throwable $th) {
            $this->logger->error('Error encountered when validating bankAccount', ['errors' =>
                $th->getMessage()]);
            $errors = ['Unable to validate with Mangopay'];
        }
        return $errors ?? [];
    }

    public function syncStatusWithMangopay(BankAccount $bankAccount): BankAccount
    {
        $recipient = $this->walletService->retrieveRecipient(
            $bankAccount->getProviderId(),
        );
        $expectedStatus = match ($recipient->Status) {
            'CANCELED', 'DEACTIVATED' => BankAccountStatus::Closed,
            'ACTIVE' => BankAccountStatus::Active,
            default => $bankAccount->getStatus(), // do nothing by default
        };
        $bankAccount->setStatus($expectedStatus);
        return $bankAccount;
    }

    public function transitionBankAccount(
        BankAccount $bankAccount,
        string $transition,
    ): void {
        if ($this->bankAccountStateMachine->can($bankAccount, $transition)) {
            $this->bankAccountStateMachine->apply($bankAccount, $transition);
        }
    }

    public function createMangopayRecipientObject(BankAccount $bankAccount): Recipient
    {
        $recipient = new Recipient();
        $recipient->PayoutMethodType = match ($bankAccount->getAccountType()) {
            BankAccountType::GB => 'LocalBankTransfer',
            default => 'InternationalBankTransfer',
        };
        $recipient->RecipientType = match ($bankAccount->getAccountHolderType()) {
            BankAccountHolderType::Business => 'Business',
            default => 'Individual',
        };
        $recipient->Currency = 'GBP';
        $recipient->Country = $bankAccount->getCountry();
        $recipient->Tag = $bankAccount->getDescription();
        $recipient->DisplayName =
            $bankAccount->getDisplayName() ?? $this->createDisplayName($bankAccount);
        if ($recipient->PayoutMethodType == 'LocalBankTransfer') {
            $recipient->LocalBankTransfer = [
                'GBP' => [
                    'AccountNumber' => $bankAccount->getAccountNumber(),
                    'SortCode' => $bankAccount->getBankIdentifierCode(),
                ],
            ];
        } else {
            $details = ['AccountNumber' => $bankAccount->getAccountNumber()];
            if ($bankAccount->getBankIdentifierCode()) {
                $details['BIC'] = $bankAccount->getBankIdentifierCode();
            }
            $recipient->InternationalBankTransfer = $details;
        }
        if ($recipient->RecipientType == 'Business') {
            $recipient->BusinessRecipient =
                $this->createBusinessRecipient($bankAccount);
        } else {
            $recipient->IndividualRecipient =
                $this->createIndividualRecipient($bankAccount);
        }
        return $recipient;
    }

    /**
     * @throws \Symfony\Component\Workflow\Exception\LogicException
     * @throws \MangoPay\Libraries\ResponseException
     */
    public function createMangopayRecipient(BankAccount $bankAccount): Recipient
    {
        /**
         * Manage state transitions for BankAccount object
         * If can enable, attempt to create the bank account on Mangopay
         *
         * Does NOT handle errors, will rethrow the Mangopay ResponseException
         * after managing state transition
         */
        try {
            $this->logger->debug(
                "Trying to create Mangopay recipient for bank account {$bankAccount->getId()}",
            );
            $recipient = $this->walletService->createRecipient(
                $bankAccount->getUser()->getMangopayUserId(),
                $this->createMangopayRecipientObject($bankAccount),
            );
            $bankAccount->setProviderId($recipient->Id);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error('Error creating Mangopay recipient', [
                'errors' => $e->GetErrorDetails()->Errors,
            ]);
            throw $e;
        }
        return $recipient;
    }

    /**
     * @throws \Symfony\Component\Workflow\Exception\LogicException
     * @throws \MangoPay\Libraries\ResponseException
     */
    public function disableBankAccount(BankAccount $bankAccount): BankAccount
    {
        $this->logger->debug('Trying to disable bank account', [$bankAccount->getId()]);
        if ($this->bankAccountStateMachine->can(
            $bankAccount,
            BankAccountTransition::Disable->value,
        )) {
            if (!empty($bankAccount->getProviderId())) {
                $this->logger->debug('Checking Mangopay recipient status', [$bankAccount->getProviderId()]);
                $recipient = $this->walletService->retrieveRecipient(
                    $bankAccount->getProviderId(),
                );
                if ($recipient->Status == 'ACTIVE') {
                    $this->logger->info('Deactivating Mangopay recipient', [$bankAccount->getProviderId()]);
                    $recipient = $this->walletService->deactivateRecipient(
                        $bankAccount->getProviderId(),
                    );
                } else {
                    $this->logger->debug(
                        "Mangopay recipient already $recipient->Status",
                        [$bankAccount->getProviderId()],
                    );
                }
            }
            // $bankAccount->setProviderId(null);
            $bankAccount->setAccountNumber(null);
            $bankAccount->setBankIdentifierCode(null);
            $this->bankAccountStateMachine->apply(
                $bankAccount,
                BankAccountTransition::Disable->value,
            );
        }
        return $bankAccount;
    }

    public function activateBankAccount(BankAccount $bankAccount): ScaActionResponseDto
    {
        if ($bankAccount->getStatus() != BankAccountStatus::Approved) {
            throw new UnprocessableEntityHttpException(
                'Can only activate approved bank account registrations',
            );
        }
        $recipient = $this->createMangopayRecipient($bankAccount);
        if ($recipient->PendingUserAction?->RedirectUrl) {
            $pendingUserAction = [
                'redirectUrl' => $recipient->PendingUserAction->RedirectUrl,
            ];
        }
        $dto = new ScaActionResponseDto(
            id: $bankAccount->getId(),
            object: 'bankAccount',
            status: $bankAccount->getStatus()->value,
            providerId: $recipient->Id,
            providerStatus: $recipient->Status,
            pendingUserAction: $pendingUserAction ?? [],
        );
        return $dto;
    }

    public function processActivationOutcome(
        BankAccount $bankAccount,
        ?bool $success,
    ): BankAccount {
        if ($success === null) {
            // No definitive success outcome, so do nothing
            $this->logger->debug('Recipient activation outcome null, no action taken');
            return $bankAccount;
        }
        $transition = $success
            ? BankAccountTransition::Enable
            : BankAccountTransition::Disable;
        // Only update if necessary - i.e. the registration was approved
        if ($bankAccount->getStatus() == BankAccountStatus::Approved) {
            // $this->logger->debug("Apply transition {$transition->value} to bankAccount#{$bankAccount->getId()}");
            $this->bankAccountStateMachine->apply($bankAccount, $transition->value);
            // Clear out bank details
            $bankAccount->setAccountNumber(null);
            $bankAccount->setBankIdentifierCode(null);
        }
        return $bankAccount;
    }

    public function createDisplayName(BankAccount $bankAccount): string
    {
        // Currency CountryAlpha2 - Last 4 digits of account number
        return (
            "{$bankAccount->getCurrency()} {$bankAccount->getCountry()} _ "
            . (string) substr($bankAccount->getAccountNumber(), -4)
        );
    }

    public function sendCreationNotification(BankAccount $bankAccount): void
    {
        $user = $bankAccount->getUser();
        // Notify user
        $this->notificationService->notifyUserByEmail(
            recipient: $user,
            subject: 'Your bank account registration has been received',
            content: 'We have received your bank account registration request. You will be notified once the request has been approved or if amendments are required.',
            context: [
                'title' => 'Bank Account Registration Received',
            ],
        );
    }

    public function sendReviewNotification(
        BankAccount $bankAccount,
        bool $isUpdate = false,
    ): void {
        $user = $bankAccount->getUser();
        $action = $isUpdate ? 'updated an existing' : 'submitted a new';
        // Notify bizops
        $this->notificationService->notifyUserByEmail(
            recipient: $this->teamAddress,
            subject: 'Bank account registration ready for review',
            content: "User ID#{$user->getId()} with username {$user->getUserIdentifier()} has {$action} bank account registration ID#{$bankAccount->getId()}.
                \nA review by BizOps staff is required for approval or rejection.",
            context: [
                'title' => 'Bank Account Registration Pending Review',
            ],
            isUserStaff: true,
        );
    }

    public function sendApprovalNotification(BankAccount $bankAccount): void
    {
        // Notify user
        $this->notificationService->notifyUserByEmail(
            recipient: $bankAccount->getUser(),
            subject: 'Your bank account registration has been approved',
            content: 'Your bank account registration has been approved. You will need to activate your linked bank account in your profile before it can be used for withdrawals.',
            context: [
                'title' => 'Bank Account Registration Approved',
            ],
        );
    }

    public function sendActionRequestNotification(
        BankAccount $bankAccount,
        bool $isUpdate = false,
    ): void {
        $actionRequests = $bankAccount->getMetadata()['actionRequests'] ?? [];
        if (!empty($actionRequests)) {
            // Notify user
            $actions = [];
            foreach ($actionRequests as $a) {
                $actions[] = str_replace('_', ' ', $a->value);
            }
            $actions = join(', ', $actions);
            $this->notificationService->notifyUserByEmail(
                recipient: $bankAccount->getUser(),
                subject: 'Your bank account registration has been updated',
                content: "Your bank account registration has been reviewed and additional information was requested: {$actions}.
                    \nYou can respond to this request from the 'Linked Bank Accounts' section of your profile.",
                context: [
                    'title' => 'Bank Account Registration More Info Requested',
                ],
                isUserStaff: true,
            );
        }
    }

    public function sendClosureNotification(
        BankAccount $bankAccount,
        ?BankAccountTransition $transition = null,
        string $reason = '',
    ): void {
        // Notify user
        if (!empty($reason)) {
            $reason = " for the following reason(s): {$reason}";
        }
        $transition = match ($transition) {
            BankAccountTransition::Reject => 'rejected',
            default => 'closed',
        };
        $this->notificationService->notifyUserByEmail(
            recipient: $bankAccount->getUser(),
            subject: "Your bank account registration has been {$transition}",
            content: "Your bank account registration has been {$transition}{$reason}.
                \nA new bank account registration can be submitted from your profile.",
            context: [
                'title' => 'Bank Account Registration ' . ucfirst($transition),
            ],
        );
    }

    /**
     * Convert list of stringy action requests into the enum equivalent
     * Invalid values are removed
     * @param string[]
     * @return ActionRequest[]
     */
    public function actionRequestsAsEnum(array $actionRequests): array
    {
        return array_filter(array_map(
            fn(string $action): ?ActionRequest => ActionRequest::tryFrom($action),
            $actionRequests,
        ));
    }

    private function createIndividualRecipient(BankAccount $bankAccount): IndividualRecipient
    {
        $details = new IndividualRecipient();
        $details->FirstName = $bankAccount->getAccountHolderName() ?? $bankAccount
            ->getUser()
            ->getFirstName();
        $details->LastName = $bankAccount->getAccountHolderLastName() ?? $bankAccount
            ->getUser()
            ->getLastName();
        $details->Address = $this->createOwnerAddress($bankAccount);
        return $details;
    }

    private function createBusinessRecipient(BankAccount $bankAccount): BusinessRecipient
    {
        $details = new BusinessRecipient();
        $details->BusinessName = $bankAccount->getAccountHolderName();
        $details->Address = $this->createOwnerAddress($bankAccount);
        return $details;
    }

    private function createOwnerAddress(BankAccount $bankAccount): Address
    {
        $ownerAddress = new Address();
        $address = $bankAccount->getAccountHolderAddress();
        if (null === $address) {
            $address = $bankAccount->getUser()->getMainAddress();
        }
        $ownerAddress->AddressLine1 = $address->getAddress1();
        $ownerAddress->AddressLine2 = $address->getAddress2();
        $ownerAddress->City = $address->getCity();
        $ownerAddress->Region = $address->getRegion(); // Only for US, Canada, Mexico
        $ownerAddress->PostalCode = $address->getPostCode();
        $ownerAddress->Country = $address->getCountry();
        return $ownerAddress;
    }
}
