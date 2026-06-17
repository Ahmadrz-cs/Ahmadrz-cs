<?php

namespace App\Service;

use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetDocuments;
use App\Entity\AssetMember;
use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Enum\AssetStatus;
use App\Entity\Enum\ProductDocumentType;
use App\Entity\Enum\ProductMode;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Entity\TradeOrder;
use App\Service\Manager\UserManagerV2;
use BcMath\Number;
use Psr\Log\LoggerInterface;

/**
 * Service to support the "Product" interface that wraps assets and offerings
 * This does NOT do any persistence
 */
class ProductService
{
    public function __construct(
        private LoggerInterface $logger,
        private UserManagerV2 $userManager,
        private AssetService $assetService,
    ) {}

    public function setCommonFields(Asset $asset): Asset
    {
        // Ensure all names are set to the same thing
        $asset->setDisplayName($asset->getName());

        // Ensure asset descriptions are the same
        $asset->setDetailedDesc($asset->getBriefDescription());

        // Calculate the funding goal (valuation) and set on both asset and offering
        $valuation = round($asset->getPricePerShare() * $asset->getAmountOfShares(), 2);
        $asset->setFundingGoal($valuation);

        // Calculated the yield from the income and asset funding goal
        // Note that offering net rent projected uses whole percentages rather than fractional
        if ($asset->getFundingGoal() > 0 && $asset->getNetProjectedIncome() > 0) {
            $yield = $asset->getNetProjectedIncome() / $asset->getFundingGoal();
            $asset->setNetProjectedYield($yield);
        }

        return $asset;
    }

    public function fillDefaults(Asset $asset): Asset
    {
        /**
         * Fills in the defaults that may not otherwise be set
         * Operates on a safe basis, i.e. it will only set a default if the field is empty
         *
         * - Asset CreatedById (superadmin)
         * - Asset Org email (team@yielders.co.uk)
         * - Asset Member-author (superadmin)
         * - Asset contact point (superadmin)
         * - Asset address (empty one)
         * - Asset should be published if it is currently in draft, submitted or approved
         *
         * - Offering createdById (superadmin)
         * - Offering toggled-on secondary market listing (this is a bool so will always toggle on)
         * - Offering should be approved if it is currently in draft or submitted
         */

        // getSuperAdmin will throw an exception if none found - caller of fillDefaults should handle exceptions
        $superadmin = $this->userManager->getSuperAdmin();

        if (in_array($asset->getLifecycleStatus(), [
            AssetLifecycle::STATE_DRAFT,
            AssetLifecycle::STATE_SUBMITTED,
            AssetLifecycle::STATE_APPROVED,
        ])) {
            $asset->setLifecycleStatus(AssetLifecycle::STATE_PUBLISHED);
        }
        if ($asset->getAddresses()->count() < 1) {
            $assetAddress = new AssetAddress();
            $asset->addAddress($assetAddress);
        }
        if (empty($asset->getOrgEmail())) {
            $asset->setOrgEmail('team@yielders.co.uk');
        }
        if (empty($asset->getStampDutyUser())) {
            $asset->setStampDutyUser('stampduty@yielders.co.uk');
        }
        if (empty($asset->getMembers()->count())) {
            $assetmember = new AssetMember();
            $assetmember->setMembertype(AssetMember::MEMBER_TYPE_AUTHOR);
            $assetmember->setUser($superadmin);
            $asset->addMember($assetmember);
        }
        if (empty($asset->getContactPoint())) {
            $asset->setContactPoint($superadmin);
        }
        if (empty($asset->getCreatedById())) {
            $asset->setCreatedById($superadmin->getId());
        }
        return $asset;
    }

    public function identifyDataMissingForLaunch(Asset $asset): array
    {
        $todoList = [];

        if (!$this->isNameSet($asset)) {
            $todoList['nameSet'] = 'Asset name and/or display name are missing';
        }
        if (!$this->isNameSynced($asset)) {
            $todoList['nameSynced'] = 'Asset and offering names do not match';
        }
        if (empty($asset->getCompanyNumber())) {
            $todoList['spvSet'] = 'SPV company number is missing';
        }
        if (!$this->isDescriptionSet($asset)) {
            $todoList['descriptionSet'] = 'Asset brief and detailed descriptions are missing or do not match';
        }
        if (!$this->isAddressSet($asset)) {
            $todoList['addressSet'] = 'Asset address is missing or incomplete';
        }
        if (!$this->isCoordinatesSet($asset)) {
            $todoList['coordinatesSet'] = 'Asset address coordinates are missing or incomplete';
        }
        if (!$this->isAuthorSet($asset)) {
            $todoList['authorSet'] = 'Asset member author is missing';
        }
        if (empty($asset->getContactPoint())) {
            $todoList['contactPointSet'] = 'Asset contact point (share issuer) is missing';
        }
        if (empty($asset->getOrgEmail())) {
            $todoList['orgEmailSet'] = 'Asset org email is missing';
        }
        if (empty($asset->getStampDutyUser())) {
            $todoList['stampDutyUserSet'] = 'Asset stamp duty user is missing';
        }
        if (AssetLifecycle::STATE_PUBLISHED != $asset->getLifecycleStatus()) {
            $todoList['assetStatus'] = 'Asset is not in the published state';
        }
        if (empty($asset->getInvestmentTerm())) {
            $todoList['termSet'] = 'Asset investment term is missing';
        }
        if (empty($asset->getPricePerShare())) {
            $todoList['sharePriceSet'] = 'Asset share price is missing';
        }
        if (empty($asset->getAmountOfShares())) {
            $todoList['shareAmountSet'] = 'Asset number of shares to issue is missing';
        }
        if (empty($asset->getFundingGoal())) {
            $todoList['fundingGoalSet'] = 'Asset funding goal is missing';
        }
        if (empty($asset->getFundingGoal())) {
            $todoList['investmentTermStartSet'] = 'Asset invstment term start is missing';
        }
        if (
            empty($asset->getNetProjectedYield())
            || empty($asset->getNetProjectedIncome())
        ) {
            $todoList['yieldSet'] = 'Rental income or yield is missing';
        }
        // if (!$this->isCommitmentRulesSet($offering)) {
        //     $todoList['commitmentRulesSet'] = 'Offering min and max commits are missing. These cannot be zero';
        // }
        if (!$this->isWalletsSet($asset)) {
            $todoList['walletsSet'] = 'Some wallets are missing';
        }
        if (!$this->isDocumentsSet($asset)) {
            $todoList['documentsSet'] = 'Some required documents are missing';
        }
        return $todoList;
    }

    public function isLaunchReady(Asset $asset): bool
    {
        // Human readable alias method for checking if there is no data missing for launch
        $missingData = $this->identifyDataMissingForLaunch($asset);
        if (!empty($missingData)) {
            $this->logger->info('Launch issues found: ', array_keys($missingData));
        }
        return empty($missingData);
    }

    public function isAlreadyLaunched(Asset $asset, ?ProductMode $mode = null): bool
    {
        /**
         * Note that this will perform a simple check with no intention to backport procedures
         * - Asset AND offering is published
         * - Asset status is acquiring, active, or closing
         * - OR at least 1 share has already been sold
         */
        $checkedStates = [
            AssetStatus::Active,
            AssetStatus::Closing,
        ];
        if ($mode !== ProductMode::Retail) {
            // Acquiring is not considered "launched" if launching to retail
            // But is considered launch for prefunding
            $checkedStates[] = AssetStatus::Acquiring;
        }
        return AssetLifecycle::STATE_PUBLISHED == $asset->getLifecycleStatus()
        && in_array(
            $asset->getCurrentStatus(),
            $checkedStates,
        )// || $offering->getSharesSold() >= 1
        ;
    }

    // public function publishOffering(Offering $offering): Offering
    // {
    //     /**
    //      * Increment the status until it is published
    //      * Nothing will happen if the offering is in any other status
    //      */
    //     if (OfferingLifecycle::STATE_DRAFT == $offering->getLifecycleStatus()) {
    //         $offering->setLifecycleStatus(OfferingLifecycle::STATE_SUBMITTED);
    //     }
    //     if (OfferingLifecycle::STATE_SUBMITTED == $offering->getLifecycleStatus()) {
    //         $offering->setLifecycleStatus(OfferingLifecycle::STATE_APPROVED);
    //     }
    //     if (OfferingLifecycle::STATE_APPROVED == $offering->getLifecycleStatus()) {
    //         $offering->setLifecycleStatus(OfferingLifecycle::STATE_PUBLISHED);
    //     }
    //     return $offering;
    // }

    public function launchProduct(Asset $asset, ProductMode $launchMode): Asset
    {
        // Guard clause to prevent launching before the product is ready
        if (!$this->isLaunchReady($asset)) {
            throw new \Exception('Product not ready for launch');
        }
        /**
         * - [Asset] Ensure visibility is set to auto (to avoid unexpected changes)
         * - [Asset] Disable secondary market (applies to both launch types)
         * - [Asset] Transition to relevant status
         */
        $asset->setVisibility(BaseEntity::VISIBILITY_AUTO);
        // Explicitly set the buy and sell restrictions
        // Launch usually only opens buying
        $asset->setSellRestricted(true);
        $asset->setBuyRestricted(false);

        if (ProductMode::Retail === $launchMode) {
            $asset->setVisibility(BaseEntity::VISIBILITY_AUTO);
            // $offering->setOfferingType($launchMode->value);
            $this->assetService->applyStatusChange(
                $asset,
                AssetStatus::Active,
                'Launch to retail',
            );
        }
        if (ProductMode::Prefunding === $launchMode) {
            $asset->setVisibility(BaseEntity::VISIBILITY_VIP);
            // $offering->setOfferingType($launchMode->value);
            $this->assetService->applyStatusChange(
                $asset,
                AssetStatus::Acquiring,
                'Launch to prefunding',
            );
        }
        return $asset;
    }

    public function prepareLaunchTradeOrder(
        Asset $asset,
        Number $pricePerShare,
        int $numberOfShares,
        Number $minCommit,
        Number $maxCommit,
        ?TradeOrder $tradeOrder = null,
        ProductMode $mode = ProductMode::Retail,
    ): TradeOrder {
        if ($tradeOrder === null) {
            $tradeOrder = new TradeOrder();
        }
        $tradeOrder->setDirection(TradeDirection::Sell);
        $tradeOrder->setType(TradeOrderType::Initial);
        $tradeOrder->setAsset($asset);
        $tradeOrder->setUser($asset->getContactPoint());
        $tradeOrder->setPricePerShare($pricePerShare);
        $tradeOrder->setNumberOfShares($numberOfShares);
        $tradeOrder->setMinimumShares(
            (int) (string) $minCommit->div($pricePerShare)->ceil(),
        );
        $tradeOrder->setMaximumShares(min(
            (int) (string) $maxCommit->div($pricePerShare)->floor(),
            $numberOfShares,
        ));
        $tradeOrder->setNotes($mode->value);
        $tradeOrder->setStatus(TradeOrderStatus::Active);
        return $tradeOrder;
    }

    public function toggleVisibility(Asset $asset): Asset
    {
        // Check what what direction the toggle needs to be
        if (BaseEntity::VISIBILITY_ADMIN == $asset->getVisibility()) {
            // Open it back up to the relevant audience
            // if (ProductMode::Prefunding->value == $offering->getOfferingType()) {
            //     $offering->setVisibility(BaseEntity::VISIBILITY_VIP);
            // } else {
            //     $offering->setVisibility(BaseEntity::VISIBILITY_AUTO);
            // }
            $asset->setVisibility(BaseEntity::VISIBILITY_AUTO);
        } else {
            // make it admin only
            $asset->setVisibility(BaseEntity::VISIBILITY_ADMIN);
        }
        // // Force asset to be auto for consistency
        // $asset->setVisibility(BaseEntity::VISIBILITY_AUTO);
        return $asset;
    }

    public function sortDocuments(Asset $asset): array
    {
        /**
         * @var AssetDocuments[] $assetDocs
         */
        $assetDocs = $asset->getDocuments();
        $sortedDocs = [
            'logo' => [],
            'articlesOfAssociation' => [],
            'informationMemorandum' => [],
            'financialSummary' => [],
            'propertyPhotos' => [],
            'others' => [],
        ];
        foreach ($assetDocs ?? [] as $aDoc) {
            if ('logo' === $aDoc->getDocument()->getTag()) {
                $sortedDocs['logo'][] = $aDoc;
            } elseif ('property_photos' === $aDoc->getDocument()->getTag()) {
                $sortedDocs['propertyPhotos'][] = $aDoc;
            } elseif ('read_to_activate' == $aDoc->getDocument()->getTag()) {
                if (
                    'Articles of Association' == $aDoc->getDocument()->getDescription()
                ) {
                    $sortedDocs['articlesOfAssociation'][] = $aDoc;
                }
                if (
                    'Information Memorandum' == $aDoc->getDocument()->getDescription()
                ) {
                    $sortedDocs['informationMemorandum'][] = $aDoc;
                }
            } elseif (
                'calculations' === $aDoc->getDocument()->getTag()
                && 'Financial Summary' === $aDoc->getDocument()->getDescription()
            ) {
                $sortedDocs['financialSummary'][] = $aDoc;
            } else {
                $sortedDocs['others'][] = $aDoc;
            }
        }
        return $sortedDocs;
    }

    public function createRelationalDocument(
        ProductDocumentType $type,
        Asset $asset,
        Document $document,
    ): AssetDocuments {
        $relationalDoc = new AssetDocuments();
        $relationalDoc->setDocument($document);
        $asset->addDocument($relationalDoc);
        if (ProductDocumentType::Logo === $type) {
            $document->setTag('logo');
        }
        if (ProductDocumentType::PropertyPhotos === $type) {
            $document->setTag('property_photos');
        }
        if (ProductDocumentType::ArticlesOfAssociation === $type) {
            $document->setTag('read_to_activate');
            $document->setDescription('Articles of Association');
        }
        if (ProductDocumentType::InformationMemorandum === $type) {
            $document->setTag('read_to_activate');
            $document->setDescription('Information Memorandum');
        }
        if (ProductDocumentType::FinancialSummary === $type) {
            $document->setTag('calculations');
            $document->setDescription('Financial Summary');
        }

        return $relationalDoc;
    }

    private function isNameSet(Asset $asset): bool
    {
        return $asset->getName() && $asset->getDisplayName();
    }

    private function isNameSynced(Asset $asset): bool
    {
        return 1 == count(array_unique([
            $asset->getName(),
            $asset->getDisplayName(),
        ]));
    }

    private function isDescriptionSet(Asset $asset): bool
    {
        return (
            $asset->getBriefDescription()
            && $asset->getDetailedDesc()
            && $asset->getBriefDescription() == $asset->getDetailedDesc()
        );
    }

    private function isAddressSet(Asset $asset): bool
    {
        $address = $asset->getMainAddress();
        return $address->getAddress1()
        // && $address->getAddress2()
        && $address->getCity()
        && $address->getPostCode()
        && $address->getCountry();
    }

    private function isCoordinatesSet(Asset $asset): bool
    {
        $address = $asset->getMainAddress();
        return !is_null($address->getLatitude()) && !is_null($address->getLongitude());
    }

    private function isAuthorSet(Asset $asset): bool
    {
        /** @var \App\Entity\AssetMember[] $assetMembers */
        $assetMembers = $asset->getMembers();
        foreach ($assetMembers as $member) {
            if (
                \App\Entity\AssetMember::MEMBER_TYPE_AUTHOR == $member->getMembertype()
            ) {
                return true;
            }
        }
        return false;
    }

    private function isTermSet(Asset $asset): bool
    {
        return (int) $asset->getInvestmentTerm();
    }

    private function isSharePriceSet(Asset $asset): bool
    {
        return (bool) $asset->getPricePerShare();
    }

    private function isShareAmountSet(Asset $asset): bool
    {
        return (bool) $asset->getAmountOfShares();
    }

    private function isFundingGoalSet(Asset $asset): bool
    {
        return (bool) $asset->getFundingGoal();
    }

    // private function isCommitmentRulesSet(Offering $offering): bool
    // {
    //     return (float) $offering->getMinCommitUser()
    //     && (float) $offering->getMaxCommitUser();
    // }

    private function isWalletsSet(Asset $asset): bool
    {
        return $asset->getHoldWalletId()
        && $asset->getMainWalletId()// && $asset->getExpensesWalletId()
        // && $asset->getTaxWalletId()
        // && $asset->getTreasuryWalletId()
        ;
    }

    private function isDocumentsSet(Asset $asset): bool
    {
        $docTypesWanted = [
            'logo',
            'articlesOfAssociation',
            'informationMemorandum',
            'financialSummary',
            'propertyPhotos',
        ];
        $sortedDocs = $this->sortDocuments($asset);
        $checklist = [];
        // Check whether each of the wanted doc type lists are empty
        foreach ($sortedDocs as $docType => $docList) {
            if (in_array($docType, $docTypesWanted)) {
                $checklist[$docType] = !empty($docList);
            }
        }
        // Check if all elements are true (i.e. none of the doc type lists are empty)
        // Remove any duplicates (with array_unique) and reset array keys (with array_values)
        $checklist = array_values(array_unique($checklist));
        // Check that there's only 1 value remaining (all others were duplicates and removed)
        // And that the only value remaining is also true
        return 1 === count($checklist) && true === $checklist[0];
    }
}
