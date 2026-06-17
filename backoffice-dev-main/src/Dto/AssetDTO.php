<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class AssetDTO
{
    /**
     * TODO add custom validation to fields with projections e.g grossRentalReturnPA
     * TODO custom validator to check contact point is a valid email
     * TODO add nested DTO for Asset Members and Asset Status
     * TODO re-order fields, construct and gettters into alphabetical order
     */

    #[JMS\Type('string')]
    private $alternateName;

    #[JMS\Type('string')]
    private $additionalType;

    #[JMS\Type('string')]
    private $briefDescription;

    #[JMS\Type('string')]
    private $creditScore;

    #[JMS\Type('string')]
    private $detailedDesc;

    #[JMS\Type('string')]
    private $displayName;

    #[JMS\Type('string')]
    private $foundingLocation;

    #[JMS\Type('string')]
    private $legalName;

    #[JMS\Type('string')]
    #[Assert\Email]
    private $orgEmail;

    #[JMS\Type('string')]
    #[Assert\Url]
    private $orgWebsite;

    #[JMS\Type('string')]
    private $sector;

    #[JMS\Type('string')]
    private $taxId;

    #[JMS\Type('string')]
    private $telephone;

    #[JMS\Type('string')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'This value must be a number and can only contain two decimal places',
    )]
    private $setupFee;

    #[JMS\Type('string')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'This value must be a number and can only contain two decimal places',
    )]
    private $adminFee;

    #[JMS\Type('string')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'This value must be a number and can only contain two decimal places',
    )]
    private $managementFee;

    #[JMS\Type('string')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'This value must be a number and can only contain two decimal places',
    )]
    private $profitShare;

    #[JMS\Type('string')]
    #[Assert\Email]
    private $stampDutyUser;

    #[JMS\Type('int')]
    private $investmentTerm;

    #[JMS\Type('string')]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'This value must be a number and can only contain two decimal places',
    )]
    private $grossYield;

    #[JMS\Type('string')]
    private $grossRentalReturnPA;

    #[JMS\Type('string')]
    private $pointsOfInterest;

    #[JMS\Type('bool')]
    private $blockedForSale;

    #[JMS\Type('int')]
    private $visibility;

    #[JMS\Type('int')]
    private $mangoPayUserId;

    #[JMS\Type('int')]
    private $mangoPayWalletId;

    #[JMS\Type('int')]
    private $additionalWallet;

    #[JMS\Type('string')]
    #[Assert\Email]
    private $contactPoint;

    #[JMS\Type('string')]
    #[Assert\DateTime(
        format: 'd-m-Y',
        message: 'This value must be a valid date in the format d-m-Y',
    )]
    private $foundingDate;

    #[JMS\Type('string')]
    private $name;

    #[JMS\Type('string')]
    private $companyNumber;

    #[JMS\Type('double')]
    private $fundingGoal;

    #[JMS\Type('int')]
    private $numberOfShares;

    #[JMS\Type('string')]
    private $type;

    #[JMS\Type('double')]
    private $pricePerShare;

    #[JMS\Type('string')]
    private $netRentalReturnPa;

    #[JMS\Type('string')]
    private $grossCapitalAppreciation;

    #[JMS\Type('string')]
    private $netCapitalAppreciation;

    #[JMS\Type('string')]
    private $netCapitalAppreciationYield;

    public function __construct(
        ?string $name,
        ?string $foundingDate,
        ?string $additionalType,
        ?string $alternateName,
        ?string $briefDescription,
        ?string $creditScore,
        ?string $detailedDesc,
        ?string $displayName,
        ?string $foundingLocation,
        ?string $legalName,
        ?string $orgEmail,
        ?string $orgWebsite,
        ?string $sector,
        ?string $taxId,
        ?string $telephone,
        ?string $setupFee,
        ?string $adminFee,
        ?string $managementFee,
        ?string $profitShare,
        ?string $stampDutyUser,
        ?int $investmentTerm,
        ?string $grossYield,
        ?string $grossRentalReturnPA,
        ?string $grossCapitalAppreciation,
        ?string $netCapitalAppreciation,
        ?string $netCapitalAppreciationYield,
        ?string $pointsOfInterest,
        ?bool $blockedForSale,
        ?int $visibility,
        ?int $mangoPayUserId,
        ?int $mangoPayWalletId,
        ?int $additionalWallet,
        ?string $contactPoint,
        ?string $netRentalReturnPa,
        ?string $companyNumber,
        ?float $fundingGoal,
        ?int $numberOfShares,
        ?string $type,
        ?float $pricePerShare,
    ) {
        $this->additionalType = $additionalType;
        $this->alternateName = $alternateName;
        $this->briefDescription = $briefDescription;
        $this->creditScore = $creditScore;
        $this->detailedDesc = $detailedDesc;
        $this->displayName = $displayName;
        $this->foundingLocation = $foundingLocation;
        $this->legalName = $legalName;
        $this->orgEmail = $orgEmail;
        $this->orgWebsite = $orgWebsite;
        $this->sector = $sector;
        $this->taxId = $taxId;
        $this->telephone = $telephone;
        $this->setupFee = $setupFee;
        $this->adminFee = $adminFee;
        $this->managementFee = $managementFee;
        $this->profitShare = $profitShare;
        $this->stampDutyUser = $stampDutyUser;
        $this->investmentTerm = $investmentTerm;
        $this->grossYield = $grossYield;
        $this->grossRentalReturnPA = $grossRentalReturnPA;
        $this->grossCapitalAppreciation = $grossCapitalAppreciation;
        $this->netCapitalAppreciation = $netCapitalAppreciation;
        $this->netCapitalAppreciationYield = $netCapitalAppreciationYield;
        $this->pointsOfInterest = $pointsOfInterest;
        $this->blockedForSale = $blockedForSale;
        $this->visibility = $visibility;
        $this->mangoPayUserId = $mangoPayUserId;
        $this->mangoPayWalletId = $mangoPayWalletId;
        $this->additionalWallet = $additionalWallet;
        $this->contactPoint = $contactPoint;
        $this->name = $name;
        $this->foundingDate = $foundingDate;
        $this->companyNumber = $companyNumber;
        $this->fundingGoal = $fundingGoal;
        $this->numberOfShares = $numberOfShares;
        $this->type = $type;
        $this->pricePerShare = $pricePerShare;
        $this->netRentalReturnPa = $netRentalReturnPa;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getCompanyNumber()
    {
        return $this->companyNumber;
    }

    public function getFundingGoal()
    {
        return $this->fundingGoal;
    }

    public function getAmountOfShares()
    {
        return $this->numberOfShares;
    }

    public function getAssetType()
    {
        return $this->type;
    }

    public function getPricePerShare()
    {
        return $this->pricePerShare;
    }

    public function getNetRentalReturnPa()
    {
        return $this->netRentalReturnPa;
    }

    public function getFoundingDate()
    {
        return $this->foundingDate;
    }

    public function getAdditionalType()
    {
        return $this->additionalType;
    }

    public function getAlternateName()
    {
        return $this->alternateName;
    }

    public function getBriefDescription()
    {
        return $this->briefDescription;
    }

    public function getCreditScore()
    {
        return $this->creditScore;
    }

    public function getDetailedDesc()
    {
        return $this->detailedDesc;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function getFoundingLocation()
    {
        return $this->foundingLocation;
    }

    public function getLegalName()
    {
        return $this->legalName;
    }

    public function getOrgEmail()
    {
        return $this->orgEmail;
    }

    public function getOrgWebsite()
    {
        return $this->orgWebsite;
    }

    public function getSector()
    {
        return $this->sector;
    }

    public function getTaxId()
    {
        return $this->taxId;
    }

    public function getTelephone()
    {
        return $this->telephone;
    }

    public function getSetupFee()
    {
        return $this->setupFee;
    }

    public function getAdminFee()
    {
        return $this->adminFee;
    }

    public function getManagementFee()
    {
        return $this->managementFee;
    }

    public function getProfitShare()
    {
        return $this->profitShare;
    }

    public function getStampDutyUser()
    {
        return $this->stampDutyUser;
    }

    public function getInvestmentTerm()
    {
        return $this->investmentTerm;
    }

    public function getGrossYield()
    {
        return $this->grossYield;
    }

    public function getGrossRentalReturnPA()
    {
        return $this->grossRentalReturnPA;
    }

    public function getGrossCapitalAppreciation()
    {
        return $this->grossCapitalAppreciation;
    }

    public function getNetCapitalAppreciation()
    {
        return $this->netCapitalAppreciation;
    }

    public function getNetCapitalAppreciationYield()
    {
        return $this->netCapitalAppreciationYield;
    }

    public function getPointsOfInterest()
    {
        return $this->pointsOfInterest;
    }

    public function getBlockedForSale()
    {
        return $this->blockedForSale;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function getMangoPayUserId()
    {
        return $this->mangoPayUserId;
    }

    public function getMangoPayWalletId()
    {
        return $this->mangoPayWalletId;
    }

    public function getAdditionalWallet()
    {
        return $this->additionalWallet;
    }

    public function getContactPoint()
    {
        return $this->contactPoint;
    }
}
