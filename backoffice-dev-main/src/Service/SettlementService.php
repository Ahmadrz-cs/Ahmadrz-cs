<?php

namespace App\Service;

use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferMode;
use App\Entity\Investment;
use App\Entity\ShareTrade;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Repository\CommunicationRepository;
use App\Repository\InvestmentRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\Manager\InvestmentManagerV2;
use BcMath\Number;
use Psr\Log\LoggerInterface;

class SettlementService
{
    public function __construct(
        private LoggerInterface $logger,
        private CommunicationRepository $communicationRepository,
        private InvestmentRepository $investmentRepository,
        private UserRepository $userRepository,
        private MailerService $mailerService,
        private InvestmentManagerV2 $investmentManager,
    ) {}

    /** @param ShareTrade[] $shareTrades */
    public function getSettlementOverview(array $shareTrades): array
    {
        $this->logger->info('Building overview from list of share trades');

        $overview = [
            'prefunding' => [
                'count' => 0,
            ],
            'retailFirstParty' => [
                'count' => 0,
            ],
            'retailRelisted' => [
                'count' => 0,
            ],
            'assetSummary' => [],
        ];
        foreach ($shareTrades as $shareTrade) {
            if ($shareTrade->getStatus() == TradeStatus::Unsettled) {
                $assetId = $shareTrade->getBuyOrder()->getAsset()->getId();
                $overview['assetSummary'][$assetId][] = $shareTrade;
                $overview[$this->getTradeClassification($shareTrade)]['count'] += 1;
            } else {
                // $this->logger->debug('skipping', [
                //     'status' => $shareTrade->getStatus(),
                //     'id' => $shareTrade->getId(),
                // ]);
            }
        }
        return $overview;
    }

    public function getTradeClassification(ShareTrade $shareTrade): string
    {
        if (in_array(
            $shareTrade->getSellOrder()->getType(),
            TradeOrderType::marketTradingTypes(),
        )) {
            return 'retailRelisted';
        }
        if ($shareTrade->getBuyOrder()->getType() == TradeOrderType::Prefunding) {
            return 'prefunding';
        }
        return 'retailFirstParty';
    }

    /**
     * @param ShareTrade[] $shareTrades
     * @return array<array|string>|array{tradeCount: int, tradeSharesTotal: int, tradeValueTotal: int}
     */
    public function getTradeSettlementSummary(array $shareTrades): array
    {
        $summary = [
            'tradeCount' => 0,
            'tradeValueTotal' => 0,
            'tradeSharesTotal' => 0,
        ];
        foreach ($shareTrades as $shareTrade) {
            $summary['tradeCount'] += 1;
            $summary['tradeValueTotal'] += $shareTrade->getTradeValue();
            $summary['tradeSharesTotal'] += $shareTrade->getNumberOfShares();
        }
        $summary['tradeValueTotal'] = (string) $summary['tradeValueTotal'];
        return $summary;
    }

    public function getTradeStampDutyOverview(array $groupedTradessByUser): array
    {
        $summary = [
            'stampDutyCount' => 0,
            'stampDutyValue' => 0,
            'userSummary' => [],
        ];
        /** @var ShareTrade[] $shareTrades */
        foreach ($groupedTradessByUser as $shareTrades) {
            if (empty($shareTrades)) {
                continue;
            }
            $tradeTotal = new Number(0);
            foreach ($shareTrades as $shareTrade) {
                if (!$shareTrade instanceof ShareTrade) {
                    continue;
                }
                $tradeTotal = $tradeTotal->add($shareTrade->getTradeValue());
            }
            if (!$this->investmentManager->isTradeStampDutyExempt($shareTrade)) {
                $stampDuty =
                    $this->investmentManager->calculateTradeValueStampDuty($tradeTotal);
                $this->logger->debug('No exemption duty', [$stampDuty]);
            } else {
                $stampDuty = 0;
                $this->logger->debug('Duty exemption');
            }
            if ($stampDuty > 0) {
                $summary['stampDutyCount'] += 1;
            }
            $user = $shareTrades[0]->getBuyOrder()->getUser();
            $summary['userSummary'][$user->getId()]['settlementValueTotal'] =
                (string) $tradeTotal;
            $summary['userSummary'][$user->getId()]['stampDutyDue'] =
                (string) $stampDuty;
            $summary['stampDutyValue'] += $stampDuty;
        }
        $summary['stampDutyValue'] = (string) $summary['stampDutyValue'];
        return $summary;
    }

    public function getTradeSettlementOrderSummary(TransferOrder $transferOrder): array
    {
        $summary = [
            'tradeCount' => 0,
            'tradeValueTotal' => 0,
            'tradeSharesTotal' => 0,
            'stampDutyCount' => 0,
            'stampDutyValue' => 0,
            'tradesToSettle' => [],
        ];
        foreach ($transferOrder->getTransfers() as $transferRequest) {
            if ($transferRequest->getShareTrade()?->getId()) {
                $summary['tradesToSettle'][] = $transferRequest
                    ->getShareTrade()
                    ->getId();
            }
            if (
                TransferMode::Settlement == $transferRequest->getMode()
                || str_contains(
                    $transferRequest->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['settlement'],
                )
            ) {
                $summary['tradeCount'] += 1;
                $summary['tradeValueTotal'] += $transferRequest->getAmount();
                $summary['tradeSharesTotal'] += $transferRequest
                    ->getShareTrade()
                    ?->getNumberOfShares();
            }
            if (
                TransferMode::StampDuty == $transferRequest->getMode()
                || str_contains(
                    $transferRequest->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['stamp duty'],
                )
            ) {
                $summary['stampDutyCount'] += 1;
                $summary['stampDutyValue'] += $transferRequest->getAmount();
            }
        }
        $summary['tradeValueTotal'] = round($summary['tradeValueTotal'], 2);
        $summary['stampDutyValue'] = round($summary['stampDutyValue'], 2);
        $summary['tradesToSettle'] = array_values(array_unique(
            $summary['tradesToSettle'],
        ));
        return $summary;
    }

    /**
     * @param ShareTrade[] $shareTrades
     * @throws \Exception
     */
    public function generateSettlementTransfers(
        TransferOrder $transferOrder,
        array $shareTrades,
    ): TransferOrder {
        $asset = $transferOrder->getAsset();
        if (is_null($asset)) {
            throw new \Exception(
                'Cannot generate settlements if no asset linked to the order',
            );
        }
        foreach ($shareTrades as $shareTrade) {
            if (
                $shareTrade->getStatus() != TradeStatus::Unsettled
                || $asset->getId() != $shareTrade->getBuyOrder()->getAsset()->getId()
            ) {
                continue;
            }
            $settlementRequest = $this->generateSettlement($shareTrade);
            $transferOrder->addTransfer($settlementRequest);
        }
        return $transferOrder;
    }

    public function generateSettlement(ShareTrade $shareTrade): TransferRequest
    {
        $asset = $shareTrade->getBuyOrder()->getAsset();

        $transferRequest = new TransferRequest();
        // The mode tells the transfer order runner how to execute this transfer request
        $transferRequest->setMode(TransferMode::Settlement);
        $transferRequest->setDebitWalletId($asset->getHoldWalletId());
        if (in_array(
            $shareTrade->getSellOrder()->getType(),
            TradeOrderType::marketTradingTypes(),
        )) {
            // secondary market == set the settlement wallet to the seller
            $transferRequest->setCreditWalletId(
                $shareTrade->getSellOrder()->getUser()->getMangoPayWalletId(),
            );
        } else {
            // first party == settle into the relevant asset wallet
            $transferRequest->setCreditWalletId($asset->getSettlementWalletId());
        }
        $transferRequest->setDescription(
            MonthEndService::DESCRIPTION_PRESETS['settlement']
            . " #{$shareTrade->getId()}",
        );
        $transferRequest->setAmount((string) $shareTrade->getTradeValue());
        $transferRequest->setShareTrade($shareTrade);
        return $transferRequest;
    }

    public function generateStampDutyTransfers(
        TransferOrder $transferOrder,
        array $groupedTradesByUser,
    ): TransferOrder {
        $asset = $transferOrder->getAsset();
        if (is_null($asset)) {
            throw new \Exception(
                'Cannot generate settlements if no asset linked to the order',
            );
        }
        $stampDutyUser = $this->userRepository->findByEmail($asset->getStampDutyUser());
        if (is_null($stampDutyUser)) {
            throw new \Exception(
                "No stamp duty user found for asset #{$asset->getId()}",
            );
        }
        if (empty($stampDutyUser->getMangoPayWalletId())) {
            throw new \Exception(
                "No wallet configured for stamp duty user {$stampDutyUser->getUserIdentifier()}",
            );
        }
        $stampDutySummary = $this->getTradeStampDutyOverview($groupedTradesByUser);

        foreach ($stampDutySummary['userSummary'] as $userId => $summary) {
            if (
                !array_key_exists($userId, $groupedTradesByUser)
                || empty($groupedTradesByUser[$userId])
            ) {
                continue;
            }
            $stampDutyRequest = $this->createAggregatedStampDuty(
                $summary['stampDutyDue'],
                $summary['settlementValueTotal'],
                $groupedTradesByUser[$userId][0],
                $stampDutyUser->getMangoPayWalletId(),
            );
            if (!is_null($stampDutyRequest)) {
                $transferOrder->addTransfer($stampDutyRequest);
            }
        }
        return $transferOrder;
    }

    public function groupTradeSettlementsByUser(TransferOrder $transferOrder): array
    {
        $tradesWithTransfers = [];
        $processedTradeIds = [];
        foreach ($transferOrder->getTransfers() as $existingTransfer) {
            /**
             * Possible to have duplicate trades since the
             * stamp duty transfer will also have an investment relation
             *
             * Check that both
             * - The transfer request has an investment relation
             * - The investment has not already been processed before (duplicate)
             */
            if (
                !is_null($existingTransfer->getShareTrade()?->getId())
                && !in_array(
                    $existingTransfer->getShareTrade()?->getId(),
                    $processedTradeIds,
                )
            ) {
                $processedTradeIds[] = $existingTransfer->getShareTrade()->getId();

                $userId = $existingTransfer
                    ->getShareTrade()
                    ->getBuyOrder()
                    ->getUser()
                    ->getId();
                $tradesWithTransfers[$userId][] = $existingTransfer->getShareTrade();
            }
        }
        return $tradesWithTransfers;
    }

    private function createAggregatedStampDuty(
        string $stampDutyAmount,
        Number|string $tradeTotal,
        ShareTrade $shareTrade,
        string $stampDutyWallet,
    ): ?TransferRequest {
        if (!$tradeTotal instanceof Number) {
            $tradeTotal = new Number($tradeTotal);
        }
        if (empty($stampDutyAmount) || empty($tradeTotal)) {
            return null;
        }
        $user = $shareTrade->getBuyOrder()->getUser();
        $asset = $shareTrade->getBuyOrder()->getAsset();
        $transferRequest = new TransferRequest();
        $transferRequest->setMode(TransferMode::StampDuty);
        $transferRequest->setAmount($stampDutyAmount);
        $transferRequest->setDebitWalletId($asset->getHoldWalletId());
        $transferRequest->setCreditWalletId($stampDutyWallet);
        $transferRequest->setDescription(
            MonthEndService::DESCRIPTION_PRESETS['stamp duty']
            . " User#{$user->getId()} on amount {$tradeTotal}",
        );

        // The share trade relation is only used to identify it as a settlement transfer for stamp duty
        // Which then receives additional Mangopay tags on the created transfer
        $transferRequest->setShareTrade($shareTrade);
        return $transferRequest;
    }
}
