<?php

namespace App\Service\Manager;

use App\Entity\Investment;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\OfferingDocuments;
use App\Entity\User;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;

class OfferingManager extends BaseManager
{
    protected $entityClass = Offering::class;

    /** Builds Offering documents
     *
     * @param Offering $offering
     * @param $documents
     **/
    protected function buildFromDocuments($offering, $documents)
    {
        $docmgr = $this->getDocumentManager();

        //cycle through all the documents and try and map them to a object fields
        foreach ($documents as $doc) {
            $off_doc = new OfferingDocuments();
            $off_doc->setDocument($docmgr->buildDocument(
                $doc,
                'public',
                'offering/' . $offering->getId(),
            ));

            $offering->addDocument($off_doc);
        }
    }

    /** Builds Offering info
     *
     * @param Offering $offering
     * @param $infos
     **/
    protected function buildFromInfo($offering, $infos)
    {
        //cycle through all the info and try and map them to a object fields
        foreach ($infos as $type => $value) {
            switch ($type) {
                case 'county':
                    $this->getLogger()->info('County no longer part of offering');
                    break;

                case 'net_rent_projected':
                    $offering->setNetRentProjected($value);
                    break;

                case 'gross_rent_projected_return':
                    $offering->setGrossRentProjected($value);
                    break;

                case 'gross_projected_return':
                    $offering->setGrossProjectReturn($value);
                    break;

                default:
                    $this->getLogger()->error('unprocessed info:' . $type);
            }
        }
    }

    /**
     * Builds the offering from params
     *
     * @param Offering $existOffering
     * @param $param
     */
    public function buildOffering($param, $existOffering = null)
    {
        $this->getLogger()->info('');

        if (empty($existOffering)) {
            $offering = new Offering();
            $offering->setCreatedById($this->getUser()->getId());
        } else {
            $offering = $existOffering;
        }

        if (!empty($param->name)) {
            $offering->setName($param->name);
        }

        if (!empty($param->deal_description)) {
            $offering->setDealDescription($param->deal_description);
        }

        if (!empty($param->info)) {
            $this->buildFromInfo($offering, $param->info);
        }

        if (!empty($param->documents)) {
            $this->buildFromDocuments($offering, $param->documents);
        }

        if (!empty($param->sell_investment)) {
            //get the investment that triggered the offeirng
            $inv_repo = $this->getEntityManager()->getRepository(Investment::class);

            $sell_inv = $inv_repo->find($param->sell_investment);

            $offering->setSellInvestment($sell_inv);
        }

        //Set life_cycle_stage
        if (!empty($param->life_cycle_stage)) {
            $offering->setLifecycleStatus($param->life_cycle_stage);
        }

        if (!empty($param->is_secondary_offering)) {
            $offering->setIsSecondaryMrkt($param->is_secondary_offering);
        }

        if (!empty($param->funding_goal)) {
            $offering->setFundingGoal($param->funding_goal);
        }

        if (!empty($param->primary_offering_id)) {
            $offering->setPrimaryOfferingId($param->primary_offering_id);
        }
        if (!empty($param->valuation)) {
            $offering->setValuation($param->valuation);
        }
        if (!empty($param->equity_offered)) {
            $offering->setEquityOffered($param->equity_offered);
        }
        if (!empty($param->num_of_shares)) {
            $offering->setNoOfShares($param->num_of_shares);
        }
        if (!empty($param->price_per_share)) {
            $offering->setPricePerShare($param->price_per_share);
        }

        if (!empty($param->net_rent_projected)) {
            $offering->setNetRentProjected($param->net_rent_projected);
        }

        if (!empty($param->gross_project_return)) {
            $offering->setGrossProjectReturn($param->gross_project_return);
        }
        if (!empty($param->open_date)) {
            $offering->setOpenDate(new \DateTime($param->open_date));
        }
        if (!empty($param->close_date)) {
            $offering->setCloseDate(new \DateTime($param->close_date));
        }
        if (!empty($param->min_commit_user)) {
            $offering->setMinCommitUser($param->min_commit_user);
        }
        if (!empty($param->max_commitment)) {
            $offering->setMaxCommitUser($param->max_commitment);
        }
        if (!empty($param->max_overfunding_amount)) {
            $offering->setMaxOverFunding($param->max_overfunding_amount);
        }
        if (!empty($param->category)) {
            $offering->setCategory($param->category);
        }
        if (!empty($param->visibility)) {
            $offering->setVisibility($param->visibility);
        }

        if (!empty($param->term)) {
            $offering->setOfferingTerm($param->term);
        }

        return $offering;
    }

    /**
     * function findOfferingById($id)
     */
    public function findOfferingById($id)
    {
        //Getting single Offering
        /** @var Offering $singleOffering */
        $singleOffering = $this->findOneById($id);

        $this->getLogger()->debug(
            'Get Offering Id=> ' . $id . ' : Is a offering state=>'
                . $singleOffering->getLifecycleStatus(),
        );

        /*
         *** where state equal to Published, Closing, Settled
         */
        if (in_array($singleOffering->getLifecycleStatus(), [
            OfferingLifecycle::STATE_PUBLISHED,
            OfferingLifecycle::STATE_CLOSED,
            OfferingLifecycle::STATE_SETTELED,
        ])) {
            return $singleOffering;
        } else {
            return false;
        }
    }

    /**
     * function findInvestmentById($id)
     */
    public function findInvestmentByOffering($id, $filterParam)
    {
        /** @var User $user */
        $user = $this->getUser();

        $investments = $this
            ->getEntityManager()
            ->getRepository(Investment::class)
            ->findBy(['offering' => $id]);

        if ($user->isSuperAdmin() === true) {
            return $investments;
        } else {
            //walkthrough the investments and return onlt the approved/settled
            $invFinalList = [];

            //only return where the lifecycle state is Approved, settled
            /** @var Investment $singleinv */
            foreach ($investments as $singleinv) {
                switch ($singleinv->getLifecycleStatus()) {
                    case InvestmentLifecycle::STATE_SETTLED:
                    case InvestmentLifecycle::STATE_APPROVED:
                        $invFinalList[] = $singleinv;
                        unset($singleinv);
                        break;
                }
            }
            return $invFinalList;
        }
    }

    public function findPublicOfferingById($id, $filterParam)
    {
        //Getting single Offering
        /** @var Offering $singleOffering */
        $singleOffering = $this->findOneById($id);

        if ($singleOffering->getVisibility() == intval($filterParam['visibility'])) {
            return $singleOffering;
        }
        return false;
    }

    public function findAllOffering()
    {
        //dump($this->getUser()->getId());
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
     * - id, term
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
            $criteria['offeringType'] = explode(',', $queryParams['type']);
        }
        if (!empty($queryParams['term'])) {
            $criteria['offeringTerm'] = explode(',', $queryParams['term']);
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
        $defaultStates = [
            OfferingLifecycle::STATE_PUBLISHED_INT,
            OfferingLifecycle::STATE_SETTELED_INT,
            OfferingLifecycle::STATE_CLOSED_INT,
        ];
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
                    OfferingLifecycle::StateAsInt($item->getLifecycleStatus()),
                    $filters['status'],
                )
            ) {
                continue;
            }
            $filteredCollection[] = $item;
        }
        return $filteredCollection;
    }

    public function findAllOfferingForNonAdmin(
        $condition = [],
        $sort = null,
        $limit = null,
        $offset = null,
    ) {
        // /** @var User $current_user */
        // $current_user = $this->getUser();
        // if ($current_user->getisVIP()) {
        //     $condition_vip = array('visibility' => array(BaseEntity::VISIBILITY_AUTO, BaseEntity::VISIBILITY_ALL));
        // } else {
        //     $condition_vip = array('visibility' => array(BaseEntity::VISIBILITY_AUTO, BaseEntity::VISIBILITY_ALL, BaseEntity::VISIBILITY_VIP));
        // }
        // $condition = array_merge($condition, $condition_vip);
        // $this->getLogger()->debug('condition:' . $condition);

        $offeringList = $this->findBy($condition, $sort, $limit, $offset);
        $this->getLogger()->notice('Offerings found: ' . count($offeringList));
        $offeringFilteredList = [];

        /**
         * Only allow non-admins to access resources in specific states
         */
        $allowedStates = [
            OfferingLifecycle::STATE_PUBLISHED,
            OfferingLifecycle::STATE_SETTELED,
            OfferingLifecycle::STATE_CLOSED,
        ];
        foreach ($offeringList as $offering) {
            $this->getLogger()->notice(
                'Offerings status: ' . $offering->getLifecycleStatus(),
            );
            if (in_array($offering->getLifecycleStatus(), $allowedStates)) {
                $offeringFilteredList[] = $offering;
            }
        }
        $this->getLogger()->notice('Offerings found: ' . count($offeringList));
        return $offeringFilteredList;
    }

    /**
     * return the filter the offering baseed on attributes of the offering and user
     * @param Offering $offering
     * @return mixed
     */
    public function filterForUserType($offering)
    {
        /** @var User $current_user */
        $current_user = $this->getUser();

        if ($current_user->getInvestor()->getCxbRestrictedUser() == true) {
            if ($offering->getIsSecondaryMrkt() == true) {
                return $offering;
            }
        } else {
            return $offering;
        }

        return null;
    }

    public function findAllSecondaryOffering()
    {
        $resultValues = $this->findAllValue('', '', [
            'offeringType' => 'secondaryoffering',
        ]);

        return $resultValues;
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function draftArchiveAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_ARCHIVED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function validateMinCommit(Offering $offering): bool
    {
        $pricePerShare = (float) $offering->getPricePerShare();
        // if empty offering share price set, use asset share price as fallback
        if (empty($pricePerShare)) {
            $pricePerShare = (float) $offering->getAsset()->getPricePerShare();
        }
        // if asset share price is also empty, don't update the minCommit as it cannot be calculated
        if (empty($pricePerShare)) {
            return true;
        }
        /**
         * Add 2 zeros (decimal left shift twice) to convert to smallest unit (pence)
         * Round to get rid of floating point inprecision
         * Convert to integer for integer arithmetic
         */
        $pricePerShare = (int) round($pricePerShare * 100);
        $minCommitUnit = (int) round($offering->getMinCommitUser() * 100);
        $remainder = $minCommitUnit % $pricePerShare;
        if ($remainder > 0) {
            $minCommitUnit += $pricePerShare - $remainder;
            $offering->setMinCommitUser($minCommitUnit / 100);
            return false;
        }
        return true;
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function draftSubmitAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_SUBMITTED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function draftCancelAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
            $offering_repo->save($offering, true);
            $this->sendOfferingCancelledMail($offering);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function submitArchiveAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_ARCHIVED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function submitRejectAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_REJECTED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function submitCancelAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
            $offering_repo->save($offering, true);
            $this->sendOfferingCancelledMail($offering);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function submitApproveAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_APPROVED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function approveArchiveAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_ARCHIVED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function approveRejectAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_REJECTED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function approveCancelAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
            $offering_repo->save($offering, true);
            $this->sendOfferingCancelledMail($offering);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function approvePublishAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_PUBLISHED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function publishRestrictAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            if ($offering->getStatus()->getIsRestricted()) {
                //if restricted them move it to published - ie to remove the restricted status
                $offering->setLifecycleStatus(OfferingLifecycle::STATE_PUBLISHED);
            } else {
                $offering->setLifecycleStatus(OfferingLifecycle::STATE_RESTRICTED);
            }

            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function publishArchiveAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_ARCHIVED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function publishCancelAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
            $offering_repo->save($offering, true);
            $this->sendOfferingCancelledMail($offering);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function publishCloseAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CLOSED);
            $offering_repo->save($offering, true);

            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function closeSettleAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_SETTELED);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Offering $offering
     * @return bool
     */
    public function closeCancelAction($offering)
    {
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);

            $offering->setLifecycleStatus(OfferingLifecycle::STATE_CANCELLED);
            $offering_repo->save($offering, true);
            $this->sendOfferingCancelledMail($offering);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendNewOfferingCreationMail(Offering $offering)
    {
        try {
            $user = $offering->getAsset()->getContactPoint();
            $asset = $offering->getAsset();
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_OFFERING_NEW,
                [
                    'offering' => $offering,
                    'asset' => $asset,
                ],
            );

            if ($sent == 1) {
                //dump("hii");die;
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendOfferingCancelledMail(Offering $offering)
    {
        try {
            $asset = $offering->getAsset();
            $offeringinvestments = $offering->getInvestments();
            foreach ($offeringinvestments as $singleInvestments) {
                $user = $singleInvestments->getUser();
                $sent = $this->getEmailService()->sendMail(
                    $user,
                    MailerService::TYPE_OFFERING_CANCELLED,
                    [
                        'offering' => $offering,
                        'asset' => $asset,
                        'investment' => $singleInvestments,
                    ],
                );
                if ($sent == 1) {
                    //dump("hii");die;
                    return true;
                } else {
                    return false;
                }
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function setVisibility(Offering $offering, int $visibility): bool
    {
        $validStates = [
            Offering::VISIBILITY_AUTO,
            Offering::VISIBILITY_ADMIN,
            Offering::VISIBILITY_VIP,
        ];
        if (!in_array($visibility, $validStates)) {
            $visibility = Offering::VISIBILITY_AUTO;
        }
        try {
            $offering_repo = $this->getEntityManager()->getRepository(Offering::class);
            $offering->setVisibility($visibility);
            $offering_repo->save($offering, true);
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function switchOfferingMode(Offering $offering, string $mode): void
    {
        if ('retail' === $mode) {
            $offering->setOfferingType('retail');
            $offering->setVisibility(Offering::VISIBILITY_AUTO);
        }
        if ('prefunding' === $mode) {
            $offering->setOfferingType('prefunding');
            $offering->setVisibility(Offering::VISIBILITY_VIP);
        }
        $this->getEntityManager()->persist($offering);
        $this->getEntityManager()->flush();
    }

    public function toggleOfferingFeaturedStatus(Offering $offering): void
    {
        if ($offering->getIsFeatured()) {
            $offering->setIsFeatured(false);
        } else {
            $offering->setIsFeatured(true);
        }
        $this->getEntityManager()->persist($offering);
        $this->getEntityManager()->flush();
    }

    public function sendRelistOfferingCreationMail(Offering $offering, $user)
    {
        try {
            // $user = $offering->getAsset()->getContactPoint();
            $asset = $offering->getAsset();
            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_RELIST_OFFERING_NEW,
                [
                    'offering' => $offering,
                    'asset' => $asset,
                    'user' => $user,
                ],
            );

            if ($sent == 1) {
                //dump("hii");die;
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            //log exception
            $this->getLogger()->error('sendRelistOfferingCreationMail:' . $ex);
            return false;
        }
    }

    public function getAssetsWithExternalCommits(): array
    {
        /** @var \App\Repository\OfferingRepository */
        $offeringRepository = $this->getEntityManager()->getRepository(Offering::class);
        $offeringsWithExternalCommits =
            $offeringRepository->getOfferingsWithExternalCommits();
        return $this->aggregateExternalCommits($offeringsWithExternalCommits);
    }

    public function aggregateExternalCommits(array $offerings): array
    {
        $assetExternalCommits = [];
        foreach ($offerings as $offering) {
            $assetId = $offering->getAsset()->getId();
            $pricePerShare = $offering->getPricePerShare();
            if ($pricePerShare <= 0) {
                $pricePerShare = $offering->getAsset()->getPricePerShare();
            }

            if (array_key_exists(
                $offering->getAsset()->getId(),
                $assetExternalCommits,
            )) {
                $assetExternalCommits[$assetId]['monetary'] +=
                    $offering->getExternalCommitments();
                $assetExternalCommits[$assetId]['shares'] +=
                    $offering->getExternalCommitments() / $pricePerShare;
            } else {
                $assetExternalCommits[$assetId]['monetary'] =
                    $offering->getExternalCommitments();
                $assetExternalCommits[$assetId]['shares'] =
                    $offering->getExternalCommitments() / $pricePerShare;
            }
        }
        return $assetExternalCommits;
    }
}
