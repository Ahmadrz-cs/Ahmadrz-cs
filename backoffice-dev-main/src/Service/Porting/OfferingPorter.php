<?php

namespace App\Service\Porting;

use App\Entity\AssetDocuments;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\OfferingDocuments;
use App\Entity\TradeOrder;
use App\Entity\TradeOrderStatusLog;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Porting\Enum\OfferingPortingMode;
use BcMath\Number;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;

class OfferingPorter
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
    ) {}

    public function portOffering(Offering $offering): TradeOrder
    {
        $this->logger->debug('Porting offering', [
            'offering' => $offering->getId(),
            'lifecycleStatus' => $offering->getLifecycleStatus(),
            'raisedpercent' => $offering->getRaisedPercent(),
        ]);
        $mode = $this->identifyPortingMode($offering);
        $asset = $offering->getAsset();
        $seller = $this->findSeller($offering);
        $sharePrice = $this->findSharePrice($offering);
        $shareQuantity = $this->findShareQuantity($offering, $sharePrice);
        $type = match ($mode) {
            OfferingPortingMode::FirstParty => TradeOrderType::Initial,
            default => TradeOrderType::Market,
        };
        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Sell,
            asset: $asset,
            user: $seller,
            numberOfShares: $shareQuantity,
            pricePerShare: $sharePrice,
            type: $type,
        );
        $tradeOrder->setMinimumShares($this->findShareQuantityForAmount(
            $offering->getMinCommitUser(),
            $sharePrice,
        ));
        $tradeOrder->setMaximumShares($this->findShareQuantityForAmount(
            $offering->getMaxCommitUser(),
            $sharePrice,
        ));
        $tradeOrder->setNotes("port:o{$offering->getId()}");
        $tradeOrder->setCreatedAt($offering->getCreatedAt());
        $tradeOrder->setCreatedBy($seller);
        $this->portOfferingStatuses($offering, $tradeOrder);

        $offering->setTradeOrder($tradeOrder);
        return $tradeOrder;
    }

    public function portOfferingDocument(OfferingDocuments $offeringDocument): AssetDocuments
    {
        $asset = $offeringDocument->getOffering()->getAsset();
        $assetDocument = new AssetDocuments();
        $assetDocument->setAsset($asset);
        $assetDocument->setDocument($offeringDocument->getDocument());
        $assetDocument->setCreatedAt($offeringDocument->getCreatedAt());
        $assetDocument->setCreatedBy($offeringDocument->getCreatedBy());
        $offeringDocument->setCreatedById($asset->getId());
        return $assetDocument;
    }

    private function portOfferingStatuses(
        Offering $offering,
        TradeOrder $tradeOrder,
    ): TradeOrder {
        $statuses = $offering->getStatus();
        if ($statuses->getPublishedOn()) {
            $firstInvestmentTime = $this->findFirstOrLastInvestment(
                $offering,
                false,
            ) ?? $statuses->getPublishedOn();
            $activeLog = new TradeOrderStatusLog(
                $tradeOrder,
                TradeOrderStatus::Active,
                min($statuses->getPublishedOn(), $firstInvestmentTime),
            );
            $tradeOrder->addStatusLog($activeLog);
        }
        if ($statuses->getCancelledOn()) {
            $cancelLog = new TradeOrderStatusLog(
                $tradeOrder,
                TradeOrderStatus::Cancelled,
                $statuses->getCancelledOn(),
            );
            $tradeOrder->addStatusLog($cancelLog);
        }
        if (in_array($statuses->getLifecycleStatus(), [
            OfferingLifecycle::STATE_DRAFT,
            OfferingLifecycle::STATE_SUBMITTED,
            OfferingLifecycle::STATE_PUBLISHED,
        ])) {
            // If the offering is fully funded, it should be marked as completed
            // $this->logger->debug('Determining end state for active offerings');
            if ($offering->getRaisedPercent() >= 100) {
                // $this->logger->debug('Completed');
                $lastInvestmentTime = $this->findFirstOrLastInvestment($offering);
                $completeLog = new TradeOrderStatusLog(
                    $tradeOrder,
                    TradeOrderStatus::Completed,
                    $lastInvestmentTime ?? $statuses->getUpdatedAt(),
                );
                $tradeOrder->addStatusLog($completeLog);
            } else {
                // All remaining open/draft secondary market offerings will be cancelled
                // legacy assets are not being re-opened to relisting as they are all being exited
                // $this->logger->debug('Cancelled');
                // Set cancellation time to start of current day
                // Make it look more machine automated and easier to test
                if ($tradeOrder->getType() != TradeOrderType::Initial) {
                    $cancelNowLog = new TradeOrderStatusLog(
                        $tradeOrder,
                        TradeOrderStatus::Cancelled,
                        new \DateTime()->setTime(0, 0, 0),
                    );
                    $tradeOrder->addStatusLog($cancelNowLog);
                }
            }
        }
        return $tradeOrder;
    }

    private function identifyPortingMode(Offering $offering): OfferingPortingMode
    {
        if (
            $offering->isIsSecondaryMrkt() == true
            && $offering->getSellInvestment() === null
        ) {
            return OfferingPortingMode::FirstParty;
        }
        return OfferingPortingMode::Relisting;
    }

    private function findSeller(Offering $offering): User
    {
        $seller = null;
        $mode = $this->identifyPortingMode($offering);
        if ($offering->getCreatedById() && $mode == OfferingPortingMode::Relisting) {
            $seller = $this->userRepository->find($offering->getCreatedById());
        }
        if ($seller == null) {
            $superadmins = $this->userRepository->findByRole('ROLE_SUPER_ADMIN');
            if (!empty($superadmins)) {
                $seller = $superadmins[0];
            } else {
                $seller = $this->userRepository->findOneBy(['username' =>
                    $offering->getCreatedBy()]);
            }
        }
        return $seller;
    }

    private function findSharePrice(Offering $offering): Number
    {
        /**
         * Try in order
         * - Offering share price
         * - Asset share price
         * - Asset funding goal / asset amount of shares
         */
        $price = $offering->getPricePerShare();
        $asset = $offering->getAsset();
        if ($price <= 0) {
            $price = $asset->getPricePerShare();
        }
        if ($price <= 0) {
            $price = number_format(
                $asset->getFundingGoal() / $asset->getAmountOfShares(),
                2,
            );
        }
        return new Number($price);
    }

    private function findShareQuantity(Offering $offering, Number $sharePrice): int
    {
        $shares = $this->findShareQuantityForAmount(
            $offering->getFundingGoal(),
            $sharePrice,
        );
        return $shares;
    }

    private function findShareQuantityForAmount(
        Number|string|float|int|null $amount,
        Number $sharePrice,
    ): ?int {
        if ($amount === null) {
            return null;
        }
        $amount = new Number((string) $amount);
        return (int) (string) $amount->div($sharePrice)->ceil();
    }

    /**
     * The boolean $end refers to whether to return the first or the last investment
     * 0 (false) == first
     * 1 (true) == last
     */
    private function findFirstOrLastInvestment(
        Offering $offering,
        bool $end = true,
    ): ?\DateTime {
        $investments = $offering->getInvestments()->getValues();

        if (!empty($investments)) {
            usort(
                $investments,
                fn(
                    Investment $a,
                    Investment $b,
                ) => $a->getCreatedAt() <=> $b->getCreatedAt(),
            );
            if ($end) {
                $investment = array_last($investments);
                $this->logger->debug("Last investment ID {$investment->getId()}");
            } else {
                $investment = array_first($investments);
                $this->logger->debug("First investment ID {$investment->getId()}");
            }
            return $investment->getCreatedAt();
        }
        return null;
    }
}
