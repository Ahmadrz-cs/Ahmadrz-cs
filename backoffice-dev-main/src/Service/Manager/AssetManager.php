<?php

namespace App\Service\Manager;

use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetMember;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;
use App\Service\Util\Helper;

class AssetManager extends BaseManager
{
    protected $entityClass = Asset::class;

    public function findAllSelfAsset($filterParam)
    {
        //dump($this->getUser()->getId());
        $resultValues = $this->findAllValue(
            $filterParam['offset'],
            $filterParam['limit'],
            ['contactPoint' => $this->getUser()->getId()],
        );
        return $resultValues;
    }

    public function findAllSelfAssetCount()
    {
        //dump($this->getUser()->getId());
        $resultValues = $this->findAllCount(['contactPoint' =>
            $this->getUser()->getId()]);
        return $resultValues;
    }

    public function findAllAsset()
    {
        $resultValues = $this->findAllValue();
        return $resultValues;
    }

    public function findByQuery(array $queryParams, bool $admin = false): array
    {
        $criteria = $this->getCriteria($queryParams);
        $sort = $this->getSortPreferences($queryParams['sort']);
        $auxiliaryFilters = $this->getAuxiliaryFilters($queryParams, $admin);

        $results = $this->findBy(
            $criteria,
            $sort,
            $queryParams['limit'],
            $queryParams['offset'],
        );
        $results = $this->applyAuxiliaryFilters($results, $auxiliaryFilters);

        return $results;
    }

    /**
     * Criteria supported
     * - id
     */
    public function getCriteria(array $queryParams): array
    {
        $criteria = [];

        // criteria with matching property names
        $criteriaAllowed = ['id'];
        foreach ($queryParams as $key => $query) {
            if (in_array($key, $criteriaAllowed)) {
                if (!empty($query)) {
                    $criteria[$key] = explode(',', $query);
                }
            }
        }

        // criteria with mapped property names
        if (!empty($queryParams['type'])) {
            $typesAsString = explode(',', $queryParams['type']);
            foreach ($typesAsString as $type) {
                $criteria['assetType'] = ucfirst($type);
            }
        }

        // $this->getLogger()->info('Criteria: ' . json_encode($criteria));

        return $criteria;
    }

    /**
     * Filters supported
     * - status
     */
    public function getAuxiliaryFilters(array $queryParams, bool $admin = false): array
    {
        $auxiliaryFilters = [];

        /**
         * Always use status from query param if provided
         * Otherwise, admin should not set a status filter
         * Regular users will default and be limited to certain states
         */
        $defaultStates = [AssetLifecycle::STATE_PUBLISHED_INT];
        if (
            $admin
            && (!empty($queryParams['status']) || $queryParams['status'] === '0')
        ) {
            $auxiliaryFilters['status'] = explode(',', $queryParams['status']);
        }
        if (!$admin) {
            if (!empty($queryParams['status']) || $queryParams['status'] === '0') {
                $auxiliaryFilters['status'] = explode(',', $queryParams['status']);
                $auxiliaryFilters['status'] = array_intersect(
                    $defaultStates,
                    $auxiliaryFilters['status'],
                );
            } else {
                $auxiliaryFilters['status'] = $defaultStates;
            }
        }
        return $auxiliaryFilters;
    }

    /**
     * Filters supported
     * - status
     */
    public function applyAuxiliaryFilters($collection, $filters)
    {
        $filteredCollection = [];
        // $this->getLogger()->info('Auxiliary filters: ' . json_encode($filters));

        foreach ($collection as $item) {
            if (
                isset($filters['status'])
                && !in_array(
                    AssetLifecycle::StateAsInt($item->getLifecycleStatus()),
                    $filters['status'],
                )
            ) {
                continue;
            }
            // example of adding additional permitted filters
            // if (!in_array($asset->getAssetType(), $filters['type'])) {
            //     continue;
            // }
            $filteredCollection[] = $item;
        }
        return $filteredCollection;
    }

    public function findAssetById($id)
    {
        //Getting single Asset
        /** @var Asset $singleAsset */
        $singleAsset = $this->findOneById($id);

        //special case if the user is the author of the Asset then return
        //regardless of the lifecycle state or VIP state or admin state
        $assetmember = $singleAsset->getAuthor();

        if ($assetmember === null) {
            //every asset should have an authoer if this doesn't then there is an issue.
            $this->getLogger()->critical('Asset'
            . $id
            . ' Does not have an author... please investigate');
            return false;
        }

        if ($assetmember->getUser()->getUsername() == $this->getUser()->getUsername()) {
            return $singleAsset;
        }

        if (in_array($singleAsset->getLifecycleStatus(), [
            AssetLifecycle::STATE_PUBLISHED,
        ])) {
            return $singleAsset;
        } else {
            return false;
        }
    }

    public function findAllAssetForNonAdmin(
        $condition = [],
        $sort = null,
        $limit = null,
        $offset = null,
    ) {
        $assetList = $this->findBy($condition, $sort, $limit, $offset);
        $assetFilteredList = [];

        /**
         * Only allow non-admins to access resources in specific states
         */
        $allowedStates = [
            AssetLifecycle::STATE_PUBLISHED,
        ];
        foreach ($assetList as $asset) {
            if (in_array($asset->getLifecycleStatus(), $allowedStates)) {
                $assetFilteredList[] = $asset;
            }
        }
        return $assetFilteredList;
    }

    public function findAllAssetForNonAdminCount($condition = '')
    {
        $condition = ['lifecycleStatus' => [AssetLifecycle::STATE_PUBLISHED]];
        $assetCount = $this->findAllCount($condition);

        if (!empty($assetCount)) {
            return $assetCount;
        }
        return false;
    }

    /**
     * Builds the company, investor object from the info collection
     *
     * @param Asset $asset
     * @param $infos
     */
    protected function buildFromInfo($asset, $infos)
    {
        //cycle through all the info and try and map them to a object fields
        foreach ($infos as $type => $value) {
            switch ($type) {
                case 'funding_goal':
                    $asset->setFundingGoal($value);
                    break;
                case 'amount_of_shares':
                    $asset->setAmountOfShares($value);
                    break;
                case 'setup_fee':
                    $asset->setSetupFee($value);
                    break;
                case 'admin_fee':
                    $asset->setAdminFee($value);
                    break;
                case 'management_fee':
                    $asset->setManagementFee($value);
                    break;
                case 'profit_share':
                    $asset->setProfitShare($value);
                    break;
                case 'stamp_duty_user':
                    $asset->setStampDutyUser($value);
                    break;
                case 'asset_type':
                    $asset->setAssetType($value);
                    break;
                case 'gross_yield':
                    $asset->setGrossYield($value);
                    break;
                case 'investment_term':
                    $asset->setInvestmentTerm($value);
                    break;
                case 'gross_rental_return_pa':
                    $asset->setGrossRentalReturnPA($value);
                    break;
                case 'net_rental_return_pa':
                    $asset->setNetRentalReturnPA($value);
                    break;
                case 'gross_capital_appreciation':
                    $asset->setGrossCapitalAppreciation($value);
                    break;
                case 'net_capital_appreciation':
                    $asset->setNetCapitalAppreciation($value);
                    break;
                case 'net_capital_appreciation_yield':
                    $asset->setNetCapitalAppreciationYield($value);
                    break;
                case 'points_of_interest':
                    $asset->setPointsOfInterest($value);
                    break;
                case 'blocked_for_sale':
                    $asset->setBlockedForSale($value);
                    break;
                case 'price_per_share':
                    $asset->setPricePerShare($value);
                    break;

                default:
                    $this->getLogger()->error('unprocessed info:' . $type);
            }
        }
    }

    /**
     * @param $param
     * @param AssetAddress $address
     * @return AssetAddress
     */
    protected function buildAddress($param, $address = null)
    {
        if (empty($address)) {
            $assetAddress = new AssetAddress();
        } else {
            $assetAddress = $address;
        }

        if (!empty($param->address1)) {
            $assetAddress->setAddress1($param->address1);
        }
        // Alternative address line 1.
        // !!! MUST BE street_address as this is a param passed from 1020 front end !!!
        if (!empty($param->street_address)) {
            $assetAddress->setAddress1($param->street_address);
        }
        if (!empty($param->address2)) {
            $assetAddress->setAddress2($param->address2);
        }
        // Alternative address line 2.
        if (!empty($param->street_address)) {
            $assetAddress->setAddress2($param->street_address);
        }
        if (!empty($param->address3)) {
            $assetAddress->setAddress3($param->address3);
        }
        if (!empty($param->city)) {
            $assetAddress->setCity($param->city);
        }
        if (!empty($param->country)) {
            $assetAddress->setCountry(Helper::getCountryCode($param->country));
        }
        if (!empty($param->region)) {
            $assetAddress->setRegion($param->region);
        }
        if (!empty($param->postcode)) {
            $assetAddress->setPostCode($param->postcode);
        }
        if (!empty($param->postal_code)) {
            $assetAddress->setPostCode($param->postal_code);
        }
        if (!empty($param->longitude)) {
            $assetAddress->setLongitude($param->longitude);
        }
        if (!empty($param->latitude)) {
            $assetAddress->setLatitude($param->latitude);
        }

        return $assetAddress;
    }

    /**
     * Builds a Asset object from an Array
     *
     * @param $param
     * @param Asset $existAsset
     *
     */
    public function buildAsset($param, $existAsset = null)
    {
        if (empty($existAsset)) {
            /** @var Asset $asset */
            $asset = new Asset();
            $asset->setContactPoint($this->getUser());
            $asset->setCreatedById($this->getUser()->getId());

            //add the current user as author type to members
            $assetmember = new AssetMember();

            $assetmember->setMembertype(AssetMember::MEMBER_TYPE_AUTHOR);
            $assetmember->setUser($this->getUser());

            $asset->addMember($assetmember);
        } else {
            $asset = $existAsset;
        }

        if (!empty($param->display_name)) {
            $asset->setDisplayName($param->display_name);
        }

        //if name isnt set the use display_name
        if (!empty($param->name)) {
            $asset->setName($param->name);
        } else {
            $asset->setName($asset->getDisplayName());
        }

        // Address object creation
        if (!empty($param->address)) {
            //do we have an existing address?
            $addressObj = $this->buildAddress(
                $param->address,
                $asset->getMainAddress(),
            );
            $asset->addAddress($addressObj);
        }

        //Set life_cycle_stage
        if (!empty($param->life_cycle_stage)) {
            $asset->setLifecycleStatus($param->life_cycle_stage);
        }

        if (!empty($param->info)) {
            $this->buildFromInfo($asset, $param->info);
        }

        if (!empty($param->additional_type)) {
            $asset->setAdditionalType($param->additional_type);
        }
        //special case for  #893, this should be removed once the issue is fixed in the front end
        if (!empty($param->addtional_type)) {
            $asset->setAdditionalType($param->addtional_type);
        }

        if (!empty($param->alternate_name)) {
            $asset->setAlternateName($param->alternate_name);
        }
        if (!empty($param->brief_description)) {
            $asset->setBriefDescription($param->brief_description);
        }
        if (!empty($param->brief_desc)) {
            $asset->setBriefDescription($param->brief_desc);
        }

        if (!empty($param->company_number)) {
            $asset->setCompanyNumber($param->company_number);
        }
        if (!empty($param->detailed_description)) {
            $asset->setDetailedDesc($param->detailed_description);
        }
        if (!empty($param->detail_desc)) {
            $asset->setDetailedDesc($param->detail_desc);
        }

        if (!empty($param->legal_name)) {
            $asset->setLegalName($param->legal_name);
        }
        if (!empty($param->org_email)) {
            $asset->setOrgEmail($param->org_email);
        }
        if (!empty($param->sector)) {
            $asset->setSector($param->sector);
        }
        if (!empty($param->tax_id)) {
            $asset->setTaxId($param->tax_id);
        }
        if (!empty($param->telephone)) {
            $asset->setTelephone($param->telephone);
        }
        if (!empty($param->funding_goal)) {
            $asset->setFundingGoal($param->funding_goal);
        }
        if (!empty($param->amount_of_shares)) {
            $asset->setAmountOfShares($param->amount_of_shares);
        }
        if (!empty($param->setup_fee)) {
            $asset->setSetupFee($param->setup_fee);
        }
        if (!empty($param->admin_fee)) {
            $asset->setAdminFee($param->admin_fee);
        }
        if (!empty($param->management_fee)) {
            $asset->setManagementFee($param->management_fee);
        }
        if (!empty($param->profit_share)) {
            $asset->setProfitShare($param->profit_share);
        }
        if (!empty($param->stamp_duty_user)) {
            $asset->setStampDutyUser($param->stamp_duty_user);
        }
        if (!empty($param->asset_type)) {
            $asset->setAssetType($param->asset_type);
        }
        if (!empty($param->investment_term)) {
            $asset->setInvestmentTerm($param->investment_term);
        }
        if (!empty($param->gross_rental_return_per_annum)) {
            $asset->setGrossRentalReturnPA($param->gross_rental_return_per_annum);
        }
        if (!empty($param->net_rental_return_per_annum)) {
            $asset->setNetRentalReturnPA($param->net_rental_return_per_annum);
        }
        if (!empty($param->blocked_for_sale)) {
            $asset->setBlockedForSale($param->blocked_for_sale);
        }
        if (!empty($param->price_per_share)) {
            $asset->setPricePerShare($param->price_per_share);
        }
        if (!empty($param->visibility)) {
            $asset->setVisibility($param->visibility);
        }
        if (!empty($param->gross_capital_appreciation)) {
            $asset->setGrossCapitalAppreciation($param->gross_capital_appreciation);
        }
        if (!empty($param->points_Of_interest)) {
            $asset->setPointsOfInterest($param->points_Of_interest);
        }

        return $asset;
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function draftArchiveAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_ARCHIVED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function draftSubmitAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_SUBMITTED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function draftCancelAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_CANCELLED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function submitArchiveAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_ARCHIVED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function submitRejectAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_REJECTED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function submitCancelAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_CANCELLED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function submitApproveAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_APPROVED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function approveArchiveAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_ARCHIVED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function approveRejectAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_REJECTED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function approveCancelAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_CANCELLED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function approvePublishAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_PUBLISHED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function publishArchiveAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_ARCHIVED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    public function publishCancelAction($asset)
    {
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);

            $asset->setLifecycleStatus(AssetLifecycle::STATE_CANCELLED);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function findPublicAssetById($id, $filterParam)
    {
        //Getting single Offering
        /** @var Offering $singleOffering */
        $singleAsset = $this->findOneById($id);

        if ($singleAsset->getVisibility() == intval($filterParam['visibility'])) {
            return $singleAsset;
        }
        return false;
    }

    public function setVisibility(Asset $asset, int $visibility): bool
    {
        $validStates = [
            Asset::VISIBILITY_AUTO,
            Asset::VISIBILITY_ADMIN,
            Asset::VISIBILITY_VIP,
        ];
        if (!in_array($visibility, $validStates)) {
            $visibility = Asset::VISIBILITY_AUTO;
        }
        try {
            $asset_repo = $this->getEntityManager()->getRepository(Asset::class);
            $asset->setVisibility($visibility);
            $asset_repo->save($asset, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    //TODO contact point not given
    public function newAssetCreatedMailSend($asset)
    {
        $user = $this->getUser();

        $sent = $this->getEmailService()->sendMail(
            $user,
            MailerService::TYPE_ASSET_NEW,
            ['asset' => $asset],
        );

        if ($sent == 1) {
            return true;
        } else {
            return false;
        }
    }
}
