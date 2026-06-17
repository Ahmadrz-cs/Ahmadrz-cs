<?php

namespace App\Service;

use App\Dto\Struct\UserShares;
use App\Entity\Enum\ShareTradeType;
use App\Entity\Enum\ShareTransferMode;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Enum\TradeStatus;
use App\Entity\ShareTrade;
use App\Entity\ShareTransferOrder;
use App\Entity\ShareTransferRequest;
use App\Entity\User;
use Psr\Log\LoggerInterface;

class ShareTransferService
{
    public const SHARE_TRANSFER_TEMPLATE_NAME = 'Share transfer allocation';

    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function getShareTradeQueryFilter(
        ShareTransferOrder $shareTransferOrder,
        ShareTradeType $tradeType = ShareTradeType::FirstParty,
    ): array {
        if (is_null($shareTransferOrder->getAsset())) {
            return [];
        }
        $dateStart = match ($tradeType) {
            ShareTradeType::Repayment => $shareTransferOrder->getRepaymentStart(),
            default => $shareTransferOrder->getPeriodStart(),
        };
        $dateEnd = match ($tradeType) {
            ShareTradeType::Repayment => $shareTransferOrder->getRepaymentEnd(),
            default => $shareTransferOrder->getPeriodEnd(),
        };
        $filters = [
            'assetId' => $shareTransferOrder->getAsset()->getId(),
            'status' => [TradeStatus::Settled],
            'createdAt_gte' => $dateStart,
            'createdAt_lt' => $dateEnd,
            'sellOrderType' => $tradeType->validSellTypes(),
            'buyOrderType' => $tradeType->validBuyTypes(),
        ];
        return $filters;
    }

    /**
     * Only need to pool if there are any proxy buy back ShareTrades
     * Pooling is done on both the first party investment and buyback ShareTrades
     *
     * @param ShareTrade[] $shareTrades
     * @return UserShares[]
     */
    public function poolShareTrades(
        array $shareTrades,
        ShareTradeType $tradeType = ShareTradeType::FirstParty,
    ): array {
        $userShareMap = [];
        foreach ($shareTrades as $shareTrade) {
            $user = match ($tradeType) {
                ShareTradeType::Repayment => $shareTrade->getSellOrder()->getUser(),
                default => $shareTrade->getBuyOrder()->getUser(),
            };
            if (!array_key_exists($user->getId(), $userShareMap)) {
                $userShareMap[$user->getId()] = new UserShares($user, 0);
            }
            $userShareMap[$user->getId()]->shares += $shareTrade->getNumberOfShares();
        }
        // Sort by largest to smallest aggregate share amount before returning
        \uasort(
            $userShareMap,
            fn(UserShares $a, UserShares $b) => $b->shares <=> $a->shares,
        );
        return $userShareMap;
    }

    /**
     * Summary of generateDirectShareTransfers
     * @param ShareTransferOrder $shareTransferOrder
     * @param ShareTrade[] $shareTrades
     * @return ShareTransferOrder
     */
    public function generateDirectShareTransfers(
        ShareTransferOrder $shareTransferOrder,
        array $shareTrades = [],
    ): ShareTransferOrder {
        foreach ($shareTrades as $shareTrade) {
            $shareTransferOrder->addShareTransfer($this->createDirectShareTransfer(
                $shareTrade,
            ));
        }
        return $shareTransferOrder;
    }

    /**
     * Summary of generateDirectShareTransfers
     * @param ShareTransferOrder $shareTransferOrder
     * @param ShareTrade[] $firstParty
     * @param ShareTrade[] $proxyBuyBack
     * @return ShareTransferOrder
     */
    public function generatePooledShareTransfers(
        ShareTransferOrder $shareTransferOrder,
        array $firstParty = [],
        array $proxyBuyBack = [],
    ): ShareTransferOrder {
        $pooledBuyBacks = $this->poolShareTrades(
            $proxyBuyBack,
            ShareTradeType::Repayment,
        );
        $pooledInvestments = $this->poolShareTrades($firstParty);

        $totalBuyBack = array_reduce(
            $pooledBuyBacks,
            fn(int $carry, UserShares $item) => $carry += $item->shares,
            0,
        );
        $totalInvestments = array_reduce(
            $pooledInvestments,
            fn(int $carry, UserShares $item) => $carry += $item->shares,
            0,
        );

        if ($totalBuyBack != $totalInvestments) {
            throw new \Exception(
                'Imbalance of shares to allocate. From: '
                . $totalBuyBack
                . ' To: '
                . $totalInvestments,
            );
        }

        $currentBuyerShares = array_shift($pooledInvestments);
        foreach ($pooledBuyBacks as $sellerShares) {
            while ($sellerShares->shares > 0) {
                $sharesToTransfer = min(
                    $sellerShares->shares,
                    $currentBuyerShares->shares,
                );
                $shareTransferOrder->addShareTransfer($this->createPooledShareTransfer(
                    $sellerShares->user,
                    $currentBuyerShares->user,
                    $sharesToTransfer,
                ));
                $sellerShares->shares -= $sharesToTransfer;
                $currentBuyerShares->shares -= $sharesToTransfer;
                if ($currentBuyerShares->shares <= 0) {
                    $currentBuyerShares = array_shift($pooledInvestments);
                }
            }
        }
        return $shareTransferOrder;
    }

    public function formatShareTransferCallable(): \Closure
    {
        return function (ShareTransferRequest $item): array {
            $output = [];
            $output['id'] = $item->getId();
            $output['orderId'] = $item->getShareTransferOrder()?->getId();
            $output['assetId'] = $item->getShareTransferOrder()?->getAsset()?->getId();
            $output['assetName'] = $item
                ->getShareTransferOrder()
                ?->getAsset()
                ?->getName();
            $output['assetSpv'] = $item
                ->getShareTransferOrder()
                ?->getAsset()
                ?->getCompanyNumber();
            $output['assetSharePrice'] = $item
                ->getShareTransferOrder()
                ?->getAsset()
                ?->getPricePerShare();
            $output['numberOfShares'] = $item->getShares();
            $output['tradeValue'] = (string) $item->getShareTrade()?->getTradeValue();
            $output['calculatedInvestmentValue'] = (string) round(
                $output['numberOfShares'] * $output['assetSharePrice'],
                2,
            );

            if (
                $output['calculatedInvestmentValue'] >= 1000
                && $item->getInvestment()?->getOffering()?->getOfferingType()
                    != 'prefunding'
            ) {
                $output['estimatedStampDuty'] = (string) (
                    ceil($output['calculatedInvestmentValue'] / 1000) * 5
                );
            } elseif ($item->getShareTrade()?->getBuyOrder() !== null) {
                $output['estimatedStampDuty'] = (string) $item
                    ->getShareTrade()
                    ->getBuyOrder()
                    ->getExpectedStampDuty();
            } else {
                $output['estimatedStampDuty'] = (string) 0;
            }

            $output['investmentId'] = $item->getInvestment()?->getId();
            $output['shareTradeId'] = $item->getShareTrade()?->getId();

            $output['buyerId'] = $item->getBuyer()?->getId();
            $output['buyerUsername'] = $item->getBuyer()?->getUserIdentifier();
            $output['buyerContactEmail'] = $item->getBuyer()?->getEmail();
            $output['buyerTitle'] = $item->getBuyer()?->getHonoricPrefix();
            $output['buyerFirstName'] = $item->getBuyer()?->getFirstname();
            $output['buyerLastName'] = $item->getBuyer()?->getLastname();
            $output['buyerAdressLine1'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getAddress1();
            $output['buyerAdressLine2'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getAddress2();
            $output['buyerAddressCity'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getCity();
            $output['buyerAddressRegion'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getRegion();
            $output['buyerAddressPostCode'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getPostCode();
            $output['buyerAddressCountry'] = $item
                ->getBuyer()
                ?->getMainAddress()
                ?->getCountry();
            $output['buyerCompanyName'] = $item->getBuyer()?->getCompany()?->getName();
            $output['buyerCompanyRegNumber'] = $item
                ->getBuyer()
                ?->getCompany()
                ?->getRegistrationNumber();
            $output['buyerCompanyAddress1'] = $item
                ->getBuyer()
                ?->getCompany()
                ?->getRegAddress1();
            $output['buyerCompanyPostCode'] = $item
                ->getBuyer()
                ?->getCompany()
                ?->getPostCode();
            $output['buyerCompanyCountry'] = $item
                ->getBuyer()
                ?->getCompany()
                ?->getRegCountry();
            $output['buyerCompanyApprovedOn'] = $item->getBuyer()?->findCustomFieldValue(
                'companyApprovedOn',
            );

            $output['sellerId'] = $item->getSeller()?->getId();
            $output['sellerUsername'] = $item->getSeller()?->getUserIdentifier();
            $output['sellerContactEmail'] = $item->getSeller()?->getEmail();
            $output['sellerTitle'] = $item->getSeller()?->getHonoricPrefix();
            $output['sellerFirstName'] = $item->getSeller()?->getFirstname();
            $output['sellerLastName'] = $item->getSeller()?->getLastname();

            return $output;
        };
    }

    private function createDirectShareTransfer(ShareTrade $shareTrade): ShareTransferRequest
    {
        $shareTransfer = new ShareTransferRequest();
        $shareTransfer->setSeller($shareTrade->getSellOrder()->getUser());
        $shareTransfer->setBuyer($shareTrade->getBuyOrder()->getUser());
        $shareTransfer->setShares($shareTrade->getNumberOfShares());
        $shareTransfer->setShareTrade($shareTrade);
        return $shareTransfer;
    }

    private function createPooledShareTransfer(
        User $seller,
        User $buyer,
        int $shares,
    ): ShareTransferRequest {
        $shareTransfer = new ShareTransferRequest();
        $shareTransfer->setSeller($seller);
        $shareTransfer->setBuyer($buyer);
        $shareTransfer->setShares($shares);
        return $shareTransfer;
    }
}
