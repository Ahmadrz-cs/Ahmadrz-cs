<?php

namespace App\Dto;

use App\Entity\Asset;
use App\Entity\AssetMember;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class AssetAssembler
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param AssetDTO $assetDTO
     * @param Asset|null $asset
     * @return Asset
     */
    public function readDTO(AssetDTO $assetDTO, ?Asset $asset = null): Asset
    {
        if (!$asset) {
            $asset = new Asset();
        }

        $asset->setAdditionalType(
            $assetDTO->getAdditionalType() ?? $asset->getAdditionalType(),
        );
        $asset->setAlternateName(
            $assetDTO->getAlternateName() ?? $asset->getAlternateName(),
        );
        $asset->setBriefDescription(
            $assetDTO->getBriefDescription() ?? $asset->getBriefDescription(),
        );
        $asset->setDetailedDesc(
            $assetDTO->getDetailedDesc() ?? $asset->getDetailedDesc(),
        );
        $asset->setDisplayName($assetDTO->getDisplayName() ?? $asset->getDisplayName());
        $asset->setLegalName($assetDTO->getLegalName() ?? $asset->getLegalName());
        $asset->setOrgEmail($assetDTO->getOrgEmail() ?? $asset->getOrgEmail());
        $asset->setSector($assetDTO->getSector() ?? $asset->getSector());
        $asset->setTaxId($assetDTO->getTaxId() ?? $asset->getTaxId());
        $asset->setTelephone($assetDTO->getTelephone() ?? $asset->getTelephone());
        $asset->setSetupFee($assetDTO->getSetupFee() ?? $asset->getSetupFee());
        $asset->setAdminFee($assetDTO->getAdminFee() ?? $asset->getAdminFee());
        $asset->setName($assetDTO->getName() ?? $asset->getName());
        $asset->setCompanyNumber(
            $assetDTO->getCompanyNumber() ?? $asset->getCompanyNumber(),
        );
        $asset->setFundingGoal($assetDTO->getFundingGoal() ?? $asset->getFundingGoal());
        $asset->setAmountOfShares(
            $assetDTO->getAmountOfShares() ?? $asset->getAmountOfShares(),
        );
        $asset->setAssetType($assetDTO->getAssetType() ?? $asset->getAssetType());
        $asset->setPricePerShare(
            $assetDTO->getPricePerShare() ?? $asset->getPricePerShare(),
        );
        $asset->setNetRentalReturnPA(
            $assetDTO->getNetRentalReturnPa() ?? $asset->getNetRentalReturnPA(),
        );
        $asset->setManagementFee(
            $assetDTO->getManagementFee() ?? $asset->getManagementFee(),
        );
        $asset->setProfitShare($assetDTO->getProfitShare() ?? $asset->getProfitShare());
        $asset->setStampDutyUser(
            $assetDTO->getStampDutyUser() ?? $asset->getStampDutyUser(),
        );
        $asset->setInvestmentTerm(
            $assetDTO->getInvestmentTerm() ?? $asset->getInvestmentTerm(),
        );
        $asset->setGrossYield($assetDTO->getGrossYield() ?? $asset->getGrossYield());
        $asset->setGrossRentalReturnPA(
            $assetDTO->getGrossRentalReturnPA() ?? $asset->getGrossRentalReturnPA(),
        );
        $asset->setGrossCapitalAppreciation(
            $assetDTO->getGrossCapitalAppreciation() ?? $asset->getGrossCapitalAppreciation(),
        );
        $asset->setNetCapitalAppreciation(
            $assetDTO->getNetCapitalAppreciation() ?? $asset->getNetRentalReturnPA(),
        );
        $asset->setPointsOfInterest(
            $assetDTO->getPointsOfInterest() ?? $asset->getPointsOfInterest(),
        );
        $asset->setBlockedForSale(
            $assetDTO->getPointsOfInterest() ?? $asset->getPointsOfInterest(),
        );
        $asset->setMangoPayUserId(
            $assetDTO->getMangoPayUserId() ?? $asset->getMangoPayUserId(),
        );
        $asset->setMangoPayWalletId(
            $assetDTO->getMangoPayWalletId() ?? $asset->getMangoPayWalletId(),
        );
        $asset->setAdditionalWallet(
            $assetDTO->getAdditionalWallet() ?? $asset->getAdditionalWallet(),
        );
        $asset->setVisibility($assetDTO->getVisibility() ?? $asset->getVisibility());

        if (!empty($assetDTO->getContactPoint())) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['username' => $assetDTO->getContactPoint()]);

            if ($user) {
                $asset->setContactPoint($user);
            }
        }

        return $asset;
    }

    /**
     * @param Asset $asset
     * @param AssetDTO $assetDTO
     * @return Asset
     */
    public function updateAsset(Asset $asset, AssetDTO $assetDTO): Asset
    {
        return $this->readDTO($assetDTO, $asset);
    }

    /**
     * @param AssetDTO $assetDTO
     * @return Asset
     */
    public function createAsset(AssetDTO $assetDTO): Asset
    {
        return $this->readDTO($assetDTO);
    }
}
