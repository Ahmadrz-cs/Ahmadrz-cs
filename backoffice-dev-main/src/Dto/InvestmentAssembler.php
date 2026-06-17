<?php

namespace App\Dto;

use App\Dto\InvestmentDTO;
use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Repository\OfferingRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;

class InvestmentAssembler
{
    public function __construct(
        private UserRepository $userRepository,
        private OfferingRepository $offeringRepository,
        private Security $security,
    ) {
        $this->userRepository = $userRepository;
        $this->offeringRepository = $offeringRepository;
        $this->security = $security;
    }

    /**
     * Read InvestmentDTO object and build a new Investment object
     */
    public function readDTO(
        InvestmentDTO $investmentDTO,
        ?Investment $investment = null,
    ): Investment {
        if ($investmentDTO instanceof InvestmentPostDTO) {
            $investment = new Investment();
            //name is not used but has a not null constraint
            $investment->setName('');
            $investment->setCurrency('GBP');
            // With SCA, create investment in OPEN state until payment has been successfully taken
            // Then update (patch) the status based on SCA outcome
            // Either via an API PATCH or via Mangopay Webhook
            // src/Controller/Webhooks/MangopayController.php:transfers()
            $investment->setLifecycleStatus(InvestmentLifecycle::STATE_OPEN);
            // $investment->setLifecycleStatus(InvestmentLifecycle::STATE_APPROVED);

            if ($investmentDTO->getUserId()) {
                $user = $this->userRepository->find($investmentDTO->getUserId());
                if ($user) {
                    $investment->setUser($user);
                }
            } else {
                $investment->setUser($this->security->getUser());
            }

            if ($investmentDTO->getOfferingId()) {
                $offering = $this->offeringRepository->find($investmentDTO->getOfferingId());
                if ($offering) {
                    $investment->setOffering($offering);
                }
            }

            /**
             * additional fields to validating prefunding investments
             * - sharesToKeep is a "claim" for how many shares a follow-up retention investment has (if any)
             * - prefundingId is for the follow-up retention investment to soft-link the investment
             */
            if ($investmentDTO->getSharesToKeep()) {
                $investmentAddField = new InvestmentAddFields();
                $investmentAddField->setInvestment($investment);
                $investmentAddField->setFieldKey('sharesToKeep');
                $investmentAddField->setFieldValue($investmentDTO->getSharesToKeep());
                $investment->addAddField($investmentAddField);
            }

            if ($investmentDTO->getPrefundingId()) {
                $investmentAddField = new InvestmentAddFields();
                $investmentAddField->setInvestment($investment);
                $investmentAddField->setFieldKey('prefundingId');
                $investmentAddField->setFieldValue($investmentDTO->getPrefundingId());
                $investment->addAddField($investmentAddField);
            }
        }

        $investment->setType($investmentDTO->getType() ?? $investment->getType());
        $investment->setLifecycleStatus(
            $investmentDTO->getStatus() ?? $investment->getLifecycleStatus(),
        );
        $investment->setCurrency(
            $investmentDTO->getCurrency() ?? $investment->getCurrency(),
        );
        $investment->setTransactionId(
            $investmentDTO->getTransactionId() ?? $investment->getTransactionId(),
        );

        //for legacy purposes duplicate number of shares to share_amount and numberOfShares
        $investment->setShareAmount(
            $investmentDTO->getNumberOfShares() ?? $investment->getShareAmount(),
        );
        $investment->setNumberOfShares(
            $investmentDTO->getNumberOfShares() ?? $investment->getNumberOfShares(),
        );

        if ($investment->getOffering()) {
            $offeringSharePrice = $investment->getOffering()->getPricePerShare();
            $assetSharePrice = $investment
                ->getOffering()
                ->getAsset()
                ->getPricePerShare();
            //defualt use offering share price. Use asset share price if offering share price is null.
            if ((float) $investmentDTO->getPricePerShare()) {
                $investment->setPricePerShare($investmentDTO->getPricePerShare());
            } elseif ((float) $offeringSharePrice) {
                $investment->setPricePerShare($offeringSharePrice);
            } elseif ($assetSharePrice) {
                $investment->setPricePerShare($assetSharePrice);
            }

            //temporary if statement until edge case of offering and asset share price both set to null.
            if ($investment->getPricePerShare()) {
                $investment->setInvestmentValue(
                    $investment->getPricePerShare() * $investment->getNumberOfShares(),
                );
            }
        }

        //duplicate pricePerShare to orgPricePerShare
        if ($investment->getPricePerShare()) {
            $investment->setOrgPricePerShare($investment->getPricePerShare());
        }

        return $investment;
    }

    /**
     * Udpdate an existing investment object
     */
    public function updateInvestment(
        InvestmentDTO $investmentDTO,
        Investment $investment,
    ): Investment {
        return $this->readDTO($investmentDTO, $investment);
    }

    /**
     * Create a new investment object
     */
    public function createInvestment(InvestmentDTO $investmentDTO): Investment
    {
        return $this->readDTO($investmentDTO);
    }
}
