<?php

namespace ClientBundle\Service;

use AppBundle\Entity\BankAccount;
use AppBundle\Entity\Enum\BankAccountStatus;
use AppBundle\Entity\Enum\RestrictionReason;
use AppBundle\Entity\Enum\ScaStatus;
use AppBundle\Entity\ScaAction;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BankAccountService
{
    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private VerificationService $verificationService,
    ) {
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return BankAccount[]
     */
    public function listBankAccounts(bool $activeOnly = false): array
    {
        $statuses = [BankAccountStatus::Active];
        if (!$activeOnly) {
            $statuses = array_merge($statuses, [
                BankAccountStatus::Pending,
                BankAccountStatus::Validated,
                BankAccountStatus::Approved,
            ]);
        }
        $response = $this->client->bankAccount()->all([
            'query' => [
                'status' => $statuses
            ]
        ]);
        if (200 !== $response->getStatusCode()) {
            $this->logger->debug("Could not list bank accounts" . $response->getBody());
            throw new NotFoundHttpException('Unable to load linked bank accounts');
        }
        $responseBody = $this->client->getContent($response);
        // https://symfony.com/doc/current/serializer.html#handling-arrays
        return $this->denormalizer->denormalize(
            $responseBody,
            BankAccount::class . '[]',
        );
    }

    /**
     * @param BankAccount[]
     * @return array
     */
    public function convertLinkAccountsToChoices(array $linkedAccounts): array
    {
        $choices = [];
        foreach ($linkedAccounts as $ba) {
            $labelToRender = str_replace('_', '••••', $ba->displayName);
            $choices[$labelToRender] = $ba->providerId;
        }
        return $choices;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function retrieveLinkedAccount(string $id): BankAccount
    {
        $response = $this->client->bankAccount()->retrieve($id);
        if (200 !== $response->getStatusCode()) {
            throw new NotFoundHttpException('Unable to load single linked bank account');
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            BankAccount::class,
        );
    }

    public function getLastSync(): \DateTime|null
    {
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        if (array_key_exists("bank_accounts_synced_at", $userInfo)) {
            $datetimeString = $userInfo["bank_accounts_synced_at"];
            if ($datetimeString === null) {
                return null;
            }
            return new \DateTime($datetimeString);
        }
        return null;
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @return BankAccount[]
     */
    public function syncMangopayLegacyBankAccounts(int $limit = 5): array
    {
        $response = $this->client->bankAccount()->mangopaySync([
            'json' => ['limit' => $limit]
        ]);
        if (200 !== $response->getStatusCode()) {
            $this->logger->debug("Could not sync bank accounts" . $response->getBody());
            throw new NotFoundHttpException('Unable to sync bank accounts');
        }
        // We should get back a list of bank accounts that were synced
        $responseBody = $this->client->getContent($response);
        // https://symfony.com/doc/current/serializer.html#handling-arrays
        return $this->denormalizer->denormalize(
            $responseBody,
            BankAccount::class . '[]',
        );
    }

    public function addNewBankAccount(BankAccount $bankAccount): BankAccount
    {
        $requestBody = $this->normalizer->normalize(
            $bankAccount,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );
        // $this->logger->debug("Create new bank account registration ", $requestBody);
        $response = $this->client->bankAccount()->create(['json' => $requestBody]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to create new bank account registration');
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            BankAccount::class,
        );
    }

    public function activateBankAccount(BankAccount $bankAccount): ScaAction
    {
        $this->logger->debug("Activating bank account - SCA to be triggered");
        $response = $this->client->bankAccount()->activate($bankAccount->id);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to activate bank account registration');
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            ScaAction::class,
        );
    }

    public function processScaResult(BankAccount $bankAccount, bool $success, bool $verify = true): BankAccount
    {
        $this->logger->debug("Inform API of SCA result for bank account activation", ['claimSuccess' => $success, 'verify' => $verify]);
        $response = $this->client->bankAccount()->activationOutcome($bankAccount->id, [
            'json' => [
                'success' => $success,
                'verify' => $verify,
            ]
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Failed updating bank account #{$bankAccount->id} status. Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new \RuntimeException("Unable to submit sca outcome for bank account #{$bankAccount->id}");
        }
        // $this->logger->debug("Sca processing response", $this->client->getContent($response));
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            BankAccount::class,
        );
    }

    public function unlinkBankAccount(BankAccount $bankAccount): BankAccount
    {
        $response = $this->client->bankAccount()->deactivate($bankAccount->id);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to deactivate bank account registration');
        }
        return $this->denormalizer->denormalize(
            $this->client->getContent($response),
            BankAccount::class,
        );
    }

    public function checkLinkingRestrictions(): array
    {
        $restrictions = [];
        if (!$this->requestStack->getSession()->get('authenticated')) {
            $restrictions[] = RestrictionReason::NotAuthenticated;
            return $restrictions;
        }

        $userInfo = $this->requestStack->getSession()->get('userInfo');

        // PS22/10 is not required to link a bank account for withdrawing from the wallet
        // But they must otherwise be SCA enrolled and KYC approved
        if (empty($userInfo)) {
            $restrictions[] = RestrictionReason::NotAuthenticated;
        }
        if (!$userInfo['registration_complete'] && $userInfo['ob_step'] < 5) {
            $restrictions[] = RestrictionReason::RegistrationIncomplete;
        }
        if (!$userInfo['has_been_approved']) {
            $restrictions[] = RestrictionReason::NotApproved;
        }
        if (!($userInfo['sca_status'] == ScaStatus::Active->value)) {
            $restrictions[] = RestrictionReason::ScaEnrollment;
        }
        if ($this->verificationService->needsIdentityVerification()) {
            $restrictions[] = RestrictionReason::IdentityVerification;
        }
        return $restrictions;
    }

    /**
     * Summary of clearProofOfAddressActionRequests
     * @return void
     */
    public function clearProofOfAddressActionRequests(): array
    {
        try {
            $baIdsCleared = [];
            foreach ($this->listBankAccounts() as $bankAccount) {
                $this->logger->debug("Checking bank account registration {$bankAccount->uuid}");
                if (in_array("proof_of_address", $bankAccount->metadata['actionRequests'] ?? [])) {
                    $response = $this->client->bankAccount()->actionCompletion($bankAccount->id, [
                        'json' => [
                            "actionRequests" => ["proof_of_address"]
                        ]
                    ]);
                    if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
                        $this->logger->error(
                            "Unable to clear proof of address action for bank account #{$bankAccount->id}. Status code: {$response->getStatusCode()}. Response: ",
                            $this->client->getContent($response)
                        );
                        continue;
                    }
                    $baIdsCleared[] = $bankAccount->uuid;
                }
            }
            return $baIdsCleared;
        } catch (\Throwable $th) {
            $this->logger->error("Unable to list bank accounts to check for actionRequests");
        }
        return [];
    }
}
