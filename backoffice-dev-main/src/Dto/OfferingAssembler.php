<?php

namespace App\Dto;

use App\Dto\OfferingPostDTO;
use App\Entity\Offering;
use App\Repository\AssetRepository;

/**
 * TODO re-add term field when requirements are clear (year or months)
 */

final class OfferingAssembler
{
    public function __construct(
        private AssetRepository $assetRepository,
    ) {
        $this->assetRepository = $assetRepository;
    }

    public function readDTO(
        OfferingDTO $offeringDTO,
        ?Offering $offering = null,
    ): Offering {
        if (!$offering) {
            $offering = new Offering();
        }

        $offering->setName($offeringDTO->getName() ?? $offering->getName());
        $offering->setFundingGoal(
            $offeringDTO->getFundingGoal() ?? $offering->getFundingGoal(),
        );
        $offering->setExternalCommitments(
            $offeringDTO->getExternalCommitments() ?? $offering->getExternalCommitments(),
        );
        $offering->setIsFeatured(
            $offeringDTO->getIsFeatured() ?? $offering->getIsFeatured(),
        );
        $offering->setPricePerShare(
            $offeringDTO->getPricePerShare() ?? $offering->getPricePerShare(),
        );
        $offering->setNetRentProjected(
            $offeringDTO->getNetAnnualYield() ?? $offering->getNetRentProjected(),
        );
        $offering->setGrossProjectReturn(
            $offeringDTO->getNetTotalReturn() ?? $offering->getGrossProjectReturn(),
        );
        //$offering->setTerm($offeringDTO)
        $offering->setNoOfShares(
            $offeringDTO->getNumberOfShares() ?? $offering->getNoOfShares(),
        );
        $offering->setMinCommitUser(
            $offeringDTO->getMinCommit() ?? $offering->getMinCommitUser(),
        );
        $offering->setMaxCommitUser(
            $offeringDTO->getMaxCommit() ?? $offering->getMaxCommitUser(),
        );
        $offering->setLifecycleStatus(
            $offeringDTO->getStatus() ?? $offering->getLifecycleStatus(),
        );

        if ($offeringDTO instanceof OfferingPostDTO) {
            $asset = $this->assetRepository->find($offeringDTO->getAssetId());
            if ($asset) {
                $offering->setAsset($asset);
            }
            // name is set to asset name by default
            if (!$offeringDTO->getName() and $offering->getAsset()) {
                $offering->setName($offering->getAsset()->getName());
            }
        }

        return $offering;
    }

    /**
     * @param Offering $offering
     * @param OfferingDTO $offeringDTO
     * @return Offering
     */
    public function updateOffering(
        Offering $offering,
        OfferingDTO $offeringDTO,
    ): Offering {
        return $this->readDTO($offeringDTO, $offering);
    }

    /**
     * @param OfferingDTO $offeringDTO
     * @return Offering
     */
    public function createOffering(OfferingDTO $offeringDTO): Offering
    {
        return $this->readDTO($offeringDTO);
    }
}
