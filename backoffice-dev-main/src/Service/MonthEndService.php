<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TransferMode;
use App\Entity\Enum\TransferOrderPreset;
use App\Entity\Enum\TransferType;
use App\Entity\PaymentOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Service\Manager\AssetManagerV2;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * High level domain service that support month end activities
 * The API is domain orientated rather than concerning itself with underlying objects
 * Principally a coordinator services that calls other servics to do stuff
 */
class MonthEndService
{
    public const DESCRIPTION_PRESETS = [
        'accountancy' => 'Accountancy fees',
        'insurance' => 'Insurance',
        'corptax' => 'Corporation tax',
        'maintenance' => 'Maintenance accrual',
        'dividend' => 'Shareholder dividend',
        'management' => 'Yielders management fees',
        'settlement' => 'Settle investment',
        'stamp duty' => 'Stamp duty',
    ];

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function getAssetWalletChoices(Asset $asset): array
    {
        $walletChoices = [];
        // Use Symfony PropertyAccess component to access the wallet ids
        // Disable exception mode, returns null if property doesn't exist
        // This allows more consistent handling of cases where either the wallet
        // - Doesn't exist as a property
        // - Has not been set
        /**
         * Use Symfony PropertyAccess component to access the wallet ids
         * Disable exception mode, which returns null if property doesn't exist
         * This allows consistent handling of cases where either the wallet
         * - Property doesn't exist
         * - Property wallet exists but is not set
         */
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();
        foreach (AssetManagerV2::SUPPORTED_WALLETS as $walletType) {
            $walletId = $propertyAccessor->getValue($asset, $walletType . 'WalletId');
            if (!is_null($walletId)) {
                $walletChoices[$walletType] = $walletId;
            }
        }
        return $walletChoices;
    }

    public function createDefaultIncomeTransferPlan(array $walletChoices): array
    {
        $transferPlan = [];
        if (array_key_exists('expenses', $walletChoices)) {
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['expenses'],
                'description' => self::DESCRIPTION_PRESETS['accountancy'],
            ];
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['expenses'],
                'description' => self::DESCRIPTION_PRESETS['insurance'],
            ];
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['expenses'],
                'description' => self::DESCRIPTION_PRESETS['management'],
            ];
        }
        if (array_key_exists('tax', $walletChoices)) {
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['tax'],
                'description' => self::DESCRIPTION_PRESETS['corptax'],
            ];
        }
        if (array_key_exists('treasury', $walletChoices)) {
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['treasury'],
                'description' => self::DESCRIPTION_PRESETS['maintenance'],
            ];
        }
        if (array_key_exists('distribution', $walletChoices)) {
            $transferPlan[] = [
                'creditWalletId' => $walletChoices['distribution'],
                'description' => self::DESCRIPTION_PRESETS['dividend'],
            ];
        }
        return $transferPlan;
    }

    public function applyTemplateToTransferOrder(
        TransferOrder $transferOrder,
        array $template,
    ): TransferOrder {
        $transferOrder->getTransfers()->clear();
        foreach ($template as $transferPreset) {
            $transferRequest = new TransferRequest();
            $transferRequest->setDescription($transferPreset['description']);
            $transferRequest->setDebitWalletId(
                $transferPreset['debitWalletId'] ?? $transferOrder
                    ->getAsset()
                    ->getDepositWalletId(),
            );
            $transferRequest->setCreditWalletId($transferPreset['creditWalletId']);
            $transferRequest->setAmount($transferPreset['amount'] ?? 0);
            $transferOrder->addTransfer($transferRequest);
        }
        return $transferOrder;
    }

    public function groupIncomeTransfers(
        TransferOrder $transferOrder,
        array $walletIdMap,
        bool $aggregate = false,
    ): array {
        $walletNames = ['expenses', 'tax', 'treasury', 'distribution'];
        $groupedTransfers = array_fill_keys($walletNames, $aggregate ? 0 : []);
        foreach ($transferOrder->getTransfers() as $transfer) {
            if (array_key_exists($transfer->getCreditWalletId(), $walletIdMap)) {
                $walletType = $walletIdMap[$transfer->getCreditWalletId()];
                if (!in_array($walletType, $walletNames)) {
                    continue;
                }
                if ($aggregate) {
                    $groupedTransfers[$walletType] += $transfer->getAmount();
                    $groupedTransfers[$walletType] = (string) round(
                        $groupedTransfers[$walletType],
                        2,
                    );
                } else {
                    $groupedTransfers[$walletType][] = $transfer;
                }
            }
        }
        return $groupedTransfers;
    }

    public function transformPercentageToAbsolute(
        TransferRequest $transferRequest,
        array $walletIdMap,
    ): TransferRequest {
        $transferOrder = $transferRequest->getTransferOrder();
        $startingBalance = $transferOrder->getTargetTotal();
        // Can't do anything without a starting balance to calculate from
        if (is_null($startingBalance)) {
            return $transferRequest;
        }
        $groupedTransfers = $this->groupIncomeTransfers(
            $transferOrder,
            $walletIdMap,
            true,
        );
        // Decide on what balance to calculate the absolute amount
        $creditWallet = $walletIdMap[$transferRequest->getCreditWalletId()];
        // Distribution should ignore the current request's amount when calculating remaining balance
        // As that amount will be overwritten
        // No need to do this for others as they don't involve themselves in the base balance
        $baseBalance = match ($creditWallet) {
            'tax' => $startingBalance - $groupedTransfers['expenses'],
            'treasury' => $startingBalance - $groupedTransfers['expenses']
                - $groupedTransfers['tax'],
            'distribution' => $startingBalance + $transferRequest->getAmount()
                - array_sum($groupedTransfers),
            default => $startingBalance,
        };
        $absoluteAmount = (string) round(
            ($transferRequest->getAmount() * $baseBalance) / 100,
            2,
        );
        $transferRequest->setAmount($absoluteAmount);
        return $transferRequest;
    }

    public function isTransferOrderValid(
        TransferOrder $transferOrder,
        ?TransferType $transferType = null,
    ): bool {
        if (
            !is_null($transferType)
            && $transferType !== $transferOrder->getTransferType()
        ) {
            return false;
        }
        return empty($this->validateTransferOrder($transferOrder));
    }

    public function validateTransferOrder(TransferOrder $transferOrder): array
    {
        // Don't support settlement orders which are a special case since those involve investments being mutated
        return match ($transferOrder->getTransferType()) {
            TransferType::AssetIncomeProcessing => $this->validateAssetIncomeProcessing(
                $transferOrder,
            ),
            TransferType::FeeCollection => $this->validateFeeCollection($transferOrder),
            TransferType::PaymentAllocation => $this->validatePaymentAllocation(
                $transferOrder,
            ),
            TransferType::IncomeDisaggregation => $this->validateIncomeDisaggregation(
                $transferOrder,
            ),
            default => [],
        };
    }

    public function generateRelayTransfers(
        TransferOrder $transferOrder,
        iterable $transferRequests,
        string $creditWallet,
    ): TransferOrder {
        $transferOrder->getTransfers()->clear();

        /** @var TransferRequest $feeAllocation */
        foreach ($transferRequests as $feeAllocation) {
            // Skip if not a transfer request
            // Required as no PHP type for generics (i.e. data structure of some known type)
            if (!$feeAllocation instanceof TransferRequest) {
                continue;
            }
            // Create the new "relayed" transfer request and add metadata to description
            $transferRequest = $this->relayTransferRequest(
                $feeAllocation,
                $creditWallet,
            );
            $transferRequest->setDescription(
                $transferRequest->getDescription()
                . ';TR#'
                . $feeAllocation->getId()
                . ';',
            );
            // If asset is linked, add extra asset metadata to description
            if ($feeAllocation->getTransferOrder()->getAsset()) {
                $asset = $feeAllocation->getTransferOrder()->getAsset();
                $transferRequest->setAsset($asset);
                $transferRequest->setDescription(
                    $transferRequest->getDescription()
                        . "{$asset->getCompanyNumber()} {$asset->getName()};",
                );
            }

            $monthendPeriod = \DateTime::createFromInterface(
                $transferOrder->getScheduledFor(),
            )
                ->modify('-1 month')
                ->format('Y-m');
            $transferRequest->setDescription(
                $transferRequest->getDescription() . "For month {$monthendPeriod}",
            );

            $transferOrder->addTransfer($transferRequest);
        }
        return $transferOrder;
    }

    public function relayTransferRequest(
        TransferRequest $transferRequest,
        string $creditWallet,
    ): TransferRequest {
        $newTransferRequest = new TransferRequest();
        $newTransferRequest->setDebitWalletId($transferRequest->getCreditWalletId());
        $newTransferRequest->setCreditWalletId($creditWallet);
        $newTransferRequest->setDescription($transferRequest->getDescription());
        $newTransferRequest->setAmount($transferRequest->getAmount());
        if (!is_null($transferRequest->getAsset())) {
            $newTransferRequest->setAsset($transferRequest->getAsset());
        }
        return $newTransferRequest;
    }

    public function isSettlementOrder(
        TransferOrder $transferOrder,
        ?string $stampDutyWallet = null,
    ): bool {
        /**
         * Criteria for a settlement order
         * - Linked to an asset
         * - All transfer request descriptions match one of 2 strings
         * - All transfer requests have an investment on the linked asset attached
         * - Transfers are between the hold and settlement wallet for settlements
         * - Transfers are between the hold and the stamp duty wallet for stamp duty
         */
        if (is_null($transferOrder->getAsset())) {
            return false;
        }
        $assetWallets = $this->getAssetWalletChoices($transferOrder->getAsset());
        foreach ($transferOrder->getTransfers() as $transferRequest) {
            // ShareTrade must be attached to requests
            if (is_null($transferRequest->getShareTrade())) {
                return false;
            } elseif (
                // Share trade must be on the asset linked to the order
                $transferRequest
                    ->getShareTrade()
                    ->getBuyOrder()
                    ->getAsset()
                    ->getId() != $transferOrder->getAsset()->getId()
            ) {
                return false;
            }
            // Permit transfers without a standard description
            // But should raise a (soft) warning if descriptions are non-standard
            // To allow cases where off-season settlements are happening (e.g. prefunding)
            $isRelisting = in_array(
                $transferRequest->getShareTrade()->getSellOrder()->getType(),
                TradeOrderType::marketTradingTypes(),
            );
            $sellerWallet = $assetWallets['settlement'];
            if ($isRelisting) {
                $sellerWallet = $transferRequest
                    ->getShareTrade()
                    ->getSellOrder()
                    ?->getUser()
                    ?->getMangoPayWalletId();
            }
            if (
                TransferMode::Settlement == $transferRequest->getMode()
                || str_contains(
                    $transferRequest->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['settlement'],
                )
            ) {
                // settlements must be transfer between hold and settlement
                if (
                    $transferRequest->getDebitWalletId() != $assetWallets['hold']
                    || $transferRequest->getCreditWalletId() != $sellerWallet
                ) {
                    return false;
                }
            } elseif (
                TransferMode::StampDuty == $transferRequest->getMode()
                || str_contains(
                    $transferRequest->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                )
            ) {
                // stamp duty wallet must be passed to this method
                // stamp duty must transfer between hold and stam duty wallet
                if (
                    is_null($stampDutyWallet)
                    || $transferRequest->getDebitWalletId() != $assetWallets['hold']
                    || $transferRequest->getCreditWalletId() != $stampDutyWallet
                ) {
                    return false;
                }
            } else {
                return true;
            }
        }
        return true;
    }

    public function createTransferOrderByPreset(TransferOrderPreset $preset): TransferOrder
    {
        $type = match ($preset) {
            TransferOrderPreset::IncomeTransfer => TransferType::AssetIncomeProcessing,
            TransferOrderPreset::YieldersFees => TransferType::FeeCollection,
            TransferOrderPreset::InvestmentSettlement
                => TransferType::InvestmentSettlement,
            TransferOrderPreset::PrefunderRepaymentTransfer
                => TransferType::PaymentAllocation,
            TransferOrderPreset::IncomeDisaggregation
                => TransferType::IncomeDisaggregation,
            default => TransferType::Custom,
        };
        $transferOrder = new TransferOrder();
        $transferOrder->setScheduledFor(new \DateTime('first day of this month'));
        $transferOrder->setDescription($preset->value);
        $transferOrder->setTransferType($type);
        return $transferOrder;
    }

    public function createPaymentOrderByType(PaymentType $preset): PaymentOrder
    {
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setScheduledFor(new \DateTime('first day of this month'));
        $paymentOrder->setPaymentType($preset->value);
        return $paymentOrder;
    }

    public function determineDivestmentType(
        PaymentOrder $paymentOrder,
        ?int $sharesInCirculation = null,
    ): bool {
        // Only makes changes if either divestment or investment exit
        if (in_array($paymentOrder->getPaymentType(), [
            PaymentType::Divestment->value,
            PaymentType::InvestmentExit->value,
        ])) {
            if (is_null($sharesInCirculation)) {
                $sharesInCirculation = $paymentOrder->getAsset()->getAmountOfShares();
            }
            $sharesInOrder = 0;
            foreach ($paymentOrder->getPayments() as $payment) {
                $sharesInOrder += $payment->getShareholding();
            }
            /**
             * Only make changes if
             * - Currently investment exit, but not all shares are being liquidated
             * - Current divestment, but all shares are being liquidated
             */
            if (
                (int) $sharesInOrder < (int) $sharesInCirculation
                && $paymentOrder->getPaymentType() == PaymentType::InvestmentExit->value
            ) {
                $paymentOrder->setPaymentType(PaymentType::Divestment->value);
                return true;
            }
            if (
                (int) $sharesInOrder >= (int) $sharesInCirculation
                && $paymentOrder->getPaymentType() == PaymentType::Divestment->value
            ) {
                $paymentOrder->setPaymentType(PaymentType::InvestmentExit->value);
                return true;
            }
        }
        return false;
    }

    public function transferOrderAmountPerWallet(TransferOrder $transferOrder): array
    {
        $wallets = [
            'debit' => [],
            'credit' => [],
        ];
        $transfers = $transferOrder->getTransfers();
        foreach ($transfers as $transfer) {
            if (!array_key_exists($transfer->getDebitWalletId(), $wallets['debit'])) {
                $wallets['debit'][$transfer->getDebitWalletId()] = 0;
            }
            if (!array_key_exists($transfer->getCreditWalletId(), $wallets['credit'])) {
                $wallets['credit'][$transfer->getCreditWalletId()] = 0;
            }
            if (TransferRequest::STATE_PENDING === $transfer->getStatus()) {
                $wallets['credit'][$transfer->getCreditWalletId()] +=
                    $transfer->getAmount();
                $wallets['debit'][$transfer->getDebitWalletId()] +=
                    $transfer->getAmount();
                // round to 2 dp after floating point arithmetic
                $wallets['credit'][$transfer->getCreditWalletId()] = round(
                    $wallets['credit'][$transfer->getCreditWalletId()],
                    2,
                );
                $wallets['debit'][$transfer->getDebitWalletId()] = round(
                    $wallets['debit'][$transfer->getDebitWalletId()],
                    2,
                );
            }
        }
        return $wallets;
    }

    /**
     * @return \DateTimeImmutable[]
     */
    public function getMonthEndDateRangeFromDateTime(
        \DateTimeInterface $dateTime,
        int $monthOffset = 0,
    ): array {
        $originDate = new \DateTimeImmutable()
            ->setDate($dateTime->format('Y'), $dateTime->format('m'), 1)
            ->setTime(0, 0);
        $originDate = $originDate->modify("{$monthOffset} month");
        $rangeEndDate = $originDate->modify('+1 month');
        return ['start' => $originDate, 'end' => $rangeEndDate];
    }

    public function createStructuredDescription(
        string $description,
        Asset $asset,
        \DateTimeInterface $scheduledFor,
    ): string {
        $monthendPeriod = \DateTime::createFromInterface($scheduledFor)
            ->modify('-1 month')
            ->format('Y-m');
        $description .= ";{$asset->getCompanyNumber()} {$asset->getName()};For month {$monthendPeriod}";
        return $description;
    }

    private function validateAssetIncomeProcessing(TransferOrder $transferOrder): array
    {
        $issues = [];
        if (is_null($transferOrder->getAsset())) {
            $issues['missingAsset'] = 'No asset linked';
            return $issues;
        }
        if (is_null($transferOrder->getAsset()?->getDepositWalletId())) {
            $issues['missingDepositWallet'] = 'No asset deposit wallet configured';
        }
        $assetWallets = $this->getAssetWalletChoices($transferOrder->getAsset());
        foreach ($transferOrder->getTransfers() as $transferRequest) {
            if (
                $transferRequest->getDebitWalletId() != $assetWallets['deposit']
                || !in_array($transferRequest->getCreditWalletId(), $assetWallets)
            ) {
                $issues['invalidTransfer'] = 'Some transfers are not debiting from deposit or crediting an invalid wallet';
                break;
            }
        }
        return $issues;
    }

    private function validateFeeCollection(TransferOrder $transferOrder): array
    {
        $issues = [];
        if (!is_null($transferOrder->getAsset())) {
            $issues['unexpectedLinkedAsset'] = 'Unexpected asset linked to order';
        }
        return $issues;
    }

    private function validatePaymentAllocation(TransferOrder $transferOrder): array
    {
        $issues = [];
        if (is_null($transferOrder->getAsset())) {
            $issues['missingAsset'] = 'No asset linked';
        }
        return $issues;
    }

    private function validateIncomeDisaggregation(TransferOrder $transferOrder): array
    {
        $issues = [];
        if (!is_null($transferOrder->getAsset())) {
            $issues['unexpectedLinkedAsset'] = 'Unexpected asset linked to order';
        }
        return $issues;
    }
}
