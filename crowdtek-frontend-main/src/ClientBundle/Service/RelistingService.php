<?php

namespace ClientBundle\Service;

;
use AppBundle\Entity\AssetProduct;
use AppBundle\Entity\Enum\ScaStatus;
use AppBundle\Entity\ScaAction;
use AppBundle\Entity\ScaOutcome;
use ClientBundle\Exception\RelistingNotAllowedException;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class RelistingService
{
    public const string BASE_MIN_COMMIT = "100";

    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private VerificationService $verificationService,
        private InvestmentServiceV2 $investmentService,
    ) {}

    /**
     * @throws RelistingNotAllowedException
     */
    public function createRelisting(
        AssetProduct $asset,
        int $numberOfShares,
        int $sharesAvailable,
        int|string|float $fee,
        bool $sca = true,
    ): ScaAction|ScaOutcome {
        $this->logger->info('IN prefundInvestAsset');

        // Pre-check for relisting
        $this->validateRelistingAmount($asset, $numberOfShares, $sharesAvailable);
        if ($fee > 0 && !$this->investmentService->checkBalanceAvailable($fee)) {
            $this->logger->debug("Wallet balance pre-check - low balance");
            throw new RelistingNotAllowedException('Insufficient wallet balance to make payment');
        }

        $sellOrder = $this->investmentService->createSellOrder(
            asset: $asset,
            numberOfShares: $numberOfShares,
            fees: $fee,
        );
        // $this->logger->debug("SellOrder", json_decode(json_encode($sellOrder), true));

        // Create SCA transfer and return the response if successful
        if ($fee > 0) {
            $scaAction = $this->investmentService->takeOrderPayment($sellOrder->id, $fee, $sca);
        }

        // $this->logger->debug("Sca payment", json_decode(json_encode($scaAction), true));

        if ($fee <= 0 || ($scaAction->providerStatus == 'SUCCEEDED' && empty($scaAction->pendingUserAction))) {
            // If the transfer for succeeded without SCA (e.g. below threshold) or fee is zero, mark sell order as payment completed
            // This should be the case most of the time
            $scaOutcome = $this->investmentService->processOrderPaymentOutcome($sellOrder->id, true);
            // $this->logger->debug("Sca outcome", json_decode(json_encode($scaOutcome), true));
            return $scaOutcome;
        }
        return $scaAction;
    }

    /**
     * @throws RelistingNotAllowedException
     */
    private function validateRelistingAmount(AssetProduct $asset, int $numberOfShares, int $sharesAvailable): bool
    {
        /**
         * Checks several things
         * - Must be at least 1 share
         * - Must be enough shares available (either across the entire asset or the specific trade order)
         * - Must be within min-max commit
         */
        if ($numberOfShares == 0) {
            $this->logger->warning("Must relist at least 1 share");
            throw new RelistingNotAllowedException('Relistings must be for at least 1 share');
        }
        if ($sharesAvailable < $numberOfShares) {
            $this->logger->warning(
                "Not enough shares available",
                ['wanted' => $numberOfShares, 'available' => $sharesAvailable],
            );
            throw new RelistingNotAllowedException('Not enough shares available to fulfil your request');
        }

        return true;
    }

    public function calculateMinShares(AssetProduct $asset, int|string $sharesAvailable): int
    {
        if ($asset->pricePerShare > 0) {
            $minCommitAsShares = round(self::BASE_MIN_COMMIT / $asset->pricePerShare) + 1;
            if (2 * $minCommitAsShares <= $sharesAvailable) {
                return (int)$minCommitAsShares;
            }
            return (int)$sharesAvailable;
        }
        return 0;
    }

    public function isFeeExempt(
        bool $isVip,
        int|string|float $lastFeeBand,
        int|string|float $valueAlreadyListed,
    ): bool {
        if ($isVip) {
            return true;
        }
        if ($valueAlreadyListed >= $lastFeeBand) {
            return true;
        }
        return false;
    }

    /**
     * @deprecated Use InvestmentServiceV2::checkUserCanInvest
     */
    public function isAllowedToRelist(array $userInfo): bool
    {
        if ($this->verificationService->needsIdentityVerification()) {
            return false;
        }
        if (($userInfo['sca_status'] ?? "inactive") == ScaStatus::Active->value) {
            return true;
        }
        return false;
    }

    /**
     * @deprecated Use createRelisting
     * @return array Mangopay transfer for the offering
     */
    public function takeRelistingFee(
        int $offeringId,
        float|string $fee,
        bool $sca = true,
    ): array {
        $this->logger->info('IN takeRelistingFee');
        if (!$this->checkBalanceAvailable($fee)) {
            $this->logger->debug("Wallet balance pre-check - low balance");
            throw new RelistingNotAllowedException('Insufficient wallet balance to pay relisting fee');
        }
        // Create SCA transfer and return the response if successful
        $transferResponse = $this->client->offeringClassic()->createPayment(
            $offeringId,
            [
                'json' => [
                    "amount" => (string)$fee,
                    "sca" => $sca,
                ],
            ],
        );
        $transferResponse = $this->client->getContent($transferResponse);
        if (!in_array($transferResponse['status'], [200, 201])) {
            $this->logger->error('Error creating transfer for relisting fee: ', $transferResponse);
            // Payment could not be taken, so mark investment as withdrawn (cancelled)
            $this->processScaTransferResult($offeringId, false, false);

            if (array_key_exists("user_message", $transferResponse['data'] ?? [])) {
                if (str_contains($transferResponse['data']['user_message'], "Insufficient funds")) {
                    throw new RelistingNotAllowedException('Insufficient wallet balance to pay relisting fee');
                }
            }
            throw new RelistingNotAllowedException(
                'Relisting could not be made. Please try again or contact support if issue persists.',
            );
        }
        $transferData = $transferResponse['data'];
        if ($transferData['transfer']['status'] == 'SUCCEEDED' && empty($transferData['transfer']['pending_user_action'])) {
            // If the transfer for some reason succeeded without SCA, mark investment as payment completed
            // Usually happens if SCA is not required for small investment amounts
            $this->processScaTransferResult($offeringId, true, false);
        }
        return $transferData;
    }

    /**
     * @deprecated Use InvestmentServiceV2:processOrderPaymentOutcome
     */
    public function processScaTransferResult(int $offering, bool $success, bool $verify = true): ?array
    {
        $this->logger->debug("Inform API of SCA result for offering", ['claimSuccess' => $success, 'verify' => $verify]);
        $response = $this->client->offeringClassic()->createPaymentOutcome($offering, [
            'json' => [
                'success' => $success,
                'verify' => $verify,
            ],
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->error(
                "Failed updating offering #{$offering} status. Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response),
            );
            throw new \RuntimeException("Unable to submit payment outcome for relisted offering #{$offering}");
        }
        // $this->logger->debug("Sca processing response", $this->client->getContent($response));
        return $this->client->getContent($response);
    }

    /**
     * @deprecated Use InvestmentServiceV2:checkBalanceAvailable
     */
    public function checkBalanceAvailable(float $amount): bool
    {
        $sessionBalance = $this->requestStack->getSession()->get('balance', null);
        $walletScaRequired = $this->requestStack->getSession()->get('walletScaRequired', true);
        if ($sessionBalance === null || $walletScaRequired) {
            // Delegate balance checking to backoffice/Mangopay
            $this->logger->debug("Wallet balance pre-check skipped - not in session");
            return true;
        }
        $walletBalance = (float)str_replace(' ', '', $sessionBalance);
        return $walletBalance >= $amount;
    }
}
