<?php

namespace App\Service\Manager;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use App\Entity\InvestmentRepository;
use App\Entity\InvestmentStatus;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Payout;
use App\Entity\User;
use App\Entity\Wallet;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Workflow\Workflow;

/**
 * Class InvestmentManager
 * @package App\Service\Manager
 */
class InvestmentManager extends BaseManager
{
    protected $entityClass = Investment::class;

    /**
     * function findInvestmentById($id,$userId)
     */
    public function findInvestmentById($id, $userId)
    {
        /** @var Investment $singleInvestment */
        $singleInvestment = $this->findOneById($id);

        //Verifing the user
        if ($singleInvestment->getUser()->getId() == $userId) {
            return $singleInvestment;
        } else {
            return false;
        }
    }

    /**
     * Get all investments
     *
     * @return array
     */
    public function findAllInvestment()
    {
        $resultValues = $this->findAllValue();
        return $resultValues;
    }

    /**
     * Get all investments
     *
     * @return array
     */
    public function findAllInvestmentById()
    {
        $resultValues = $this->findAllOrderById();
        return $resultValues;
    }

    public function filterShareTrades(array $filters = []): array
    {
        $investments = $this->findBy([], [
            'updatedAt' => 'DESC',
            'offering' => 'ASC',
        ]);

        if (empty($filters)) {
            return $investments;
        }

        $filters = array_filter($filters, function ($v) {
            return is_numeric($v);
        });
        $matches = [];

        foreach ($investments as $item) {
            if (
                isset($filters['assetId'])
                && $item->getOffering()->getAsset()->getId() != $filters['assetId']
            ) {
                continue;
            }
            if (
                isset($filters['buyerId'])
                && $item->getUser()->getId() != $filters['buyerId']
            ) {
                continue;
            }
            if (
                isset($filters['sellerId'])
                && (
                    empty($item->getOffering()->getSellInvestment())
                    || $item->getOffering()->getSellInvestment()->getUser()->getId()
                    != $filters['sellerId']
                )
            ) {
                continue;
            }
            $matches[] = $item;
        }
        return $matches;
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
     * - id, type
     */
    public function getCriteria(array $queryParams): array
    {
        $criteria = [];

        // criteria with matching property names
        $criteriaAllowed = ['id', 'type'];
        foreach ($queryParams as $key => $query) {
            if (in_array($key, $criteriaAllowed)) {
                if (!empty($query)) {
                    $criteria[$key] = explode(',', $query);
                }
            }
        }

        // criteria with mapped property names

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

        if (
            $admin
            && (!empty($queryParams['status']) || $queryParams['status'] === '0')
        ) {
            $auxiliaryFilters['status'] = explode(',', $queryParams['status']);
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
                    InvestmentLifecycle::StateAsInt($item->getLifecycleStatus()),
                    $filters['status'],
                )
            ) {
                continue;
            }
            $filteredCollection[] = $item;
        }
        return $filteredCollection;
    }

    /***
     * Return the approved investments for a particular Asset
     *
     * @param  int $assetid
     * @return array
     */
    public function findByAsset($assetid)
    {
        $this->getLogger()->warning('get Investments for Asset id:' . $assetid);
        $investmentFinalList = [];

        $investmentList = $this->findAllValue();
        /** @var  Investment $singInv */
        foreach ($investmentList as $singInv) {
            if ($singInv->getOffering()->getAsset()->getId() == $assetid) {
                switch ($singInv->getLifecycleStatus()) {
                    case InvestmentLifecycle::STATE_SETTLED:
                        $investmentFinalList[] = $singInv;
                        break;
                }
            }
            unset($singInv);
        }

        return $investmentFinalList;

        //only return where the lifecycle state is Approved
        /** @var Investment $singInv */
        foreach ($investmentList as $singInv) {
            $this->getLogger()->warning(
                'Asset id:'
                    . $singInv->getOffering()->getAsset()->getId()
                    . ', inv id:'
                    . $singInv->getId()
                    . ':state:'
                    . $singInv->getLifecycleStatus(),
            );
            //            $this->getLogger()->warning('Asset id:' . $singInv->getOffering()->getAsset()->getId());
            if ($singInv->getOffering()->getAsset()->getId() == $assetid) {
                $this->getLogger()->debug(
                    'inv id:' . $singInv->getId() . ':state:'
                        . $singInv->getLifecycleStatus(),
                );
                $this->getLogger()->warning(
                    'Asset id:' . $singInv->getOffering()->getAsset()->getId(),
                );
                $investmentFinalList[] = $singInv;
                //}
                unset($singInv);

                /*                switch ($singInv->getLifecycleStatus()) {
                 * case InvestmentLifecycle::STATE_SETTLED:
                 * $this->getLogger()->warning('Asset id:' . $singInv->getOffering()->getAsset()->getId());
                 * // if ( $singInv->getOffering()->getAsset()->getId() == $assetid)
                 * //{
                 * $investmentFinalList[] = $singInv;
                 * //}
                 * unset($singInv);
                 * break;
                 * }*/
            }
        }
        return $investmentFinalList;
    }

    /**
     * Builds the investment from params
     *
     * @param Investment $existInvestment
     * @param $param
     */
    public function buildInvestment($param, $existInvestment = null)
    {
        if (empty($existInvestment)) {
            $investment = new Investment();
            $investment->setCreatedById($this->getUser()->getId());
            $investment->setUser($this->getUser());
        } else {
            $investment = $existInvestment;
        }

        if (!empty($param->investment_amount)) {
            $investment->setInvestmentValue($param->investment_amount);
        }

        if (!empty($param->number_of_shares)) {
            $investment->setNumberOfShares($param->number_of_shares);
        }
        if (!empty($param->currency)) {
            $investment->setCurrency($param->currency);
        }
        if (!empty($param->term)) {
            $investment->setTerm($param->term);
        }

        if (!empty($param->comments)) {
            $investment->setComments($param->comments);
        }
        if (!empty($param->visibility)) {
            $investment->setVisibility($param->visibility);
        }

        if (empty($param->name)) {
            if (empty($investment->getName())) {
                $investment->generateName();
            }
        } else {
            $investment->setName($param->name);
        }

        //Set life_cycle_stage
        if (!empty($param->life_cycle_stage)) {
            $investment->setLifecycleStatus($param->life_cycle_stage);
        }

        if (!empty($param->info)) {
            $this->buildFromInfo($investment, $param->info);
        }

        if (!empty($param->documents)) {
            $this->buildFromDocuments($investment, $param->documents);
        }

        return $investment;
    }

    /** Builds Investment info
     *
     * @param Investment $investment
     * @param $infos
     **/
    protected function buildFromInfo($investment, $infos)
    {
        foreach ($infos as $type => $value) {
            switch ($type) {
                case 'share_amount':
                    $investment->setShareAmount($value);
                    break;

                case 'org_price_per_share':
                    $investment->setOrgPricePerShare($value);
                    break;

                case 'price_per_share':
                    $investment->setPricePerShare($value);
                    break;

                case 'transaction_id':
                    $investment->setTransactionId($value);
                    break;

                case 'for_sale':
                    $investment->setForSale($value);
                    break;

                default:
                    $this->getLogger()->error('unprocessed info:' . $type);
            }
        }
    }

    /** Builds investment documents
     *
     * @param Investment $investment
     * @param $documents
     **/
    protected function buildFromDocuments($investment, $documents)
    {
        $docmgr = $this->getDocumentManager();

        //cycle through all the documents and try and map them to a object fields
        foreach ($documents as $doc) {
            $inv_doc = new InvestmentDocuments();
            $inv_doc->setDocument($docmgr->buildDocument(
                $doc,
                'private',
                'investment/' . $investment->getId(),
            ));

            $investment->addDocument($inv_doc);
        }
    }

    /**
     * validate, submit investment and update the investors committed balance
     *
     * @param @new_investment
     *
     * @return mixed
     */
    public function submitInvestment(Investment $new_investment)
    {
        $validate_result = $this->validateInvestment($new_investment);

        if ($validate_result === true) {
            //get the investor and the latest wallet
            /** @var UserRepository $user_repo */
            $user_repo = $this->getEntityManager()->getRepository(User::class);
            /** @var User $investor */
            $investor = $user_repo->find($new_investment->getUser()->getId());

            /** @var InvestmentRepository $inv_repo */
            $inv_repo = $this->getEntityManager()->getRepository(Investment::class);

            //create a payout schedule for the investment
            $payout = new Payout();

            $payout->setPayoutAmount($new_investment->getInvestmentValue());
            $payout->setCurrency('GBP');

            $new_investment->addPayout($payout);

            $investor->addInvestment($new_investment);

            /** @var Investment $result */
            $inv_repo->save($new_investment, true);

            //@todo do we need to check if it saved correctly ?
            $new_committed_balance =
                $investor->getWallet()->getCommittedBalance()
                + $new_investment->getInvestmentValue();
            $investor->getWallet()->setCommittedBalance($new_committed_balance);
            $new_free_balance =
                $investor->getWallet()->getFreeBalance()
                - $new_investment->getInvestmentValue();
            $investor->getWallet()->setFreeBalance($new_free_balance);

            /** @var WalletRepository $wallet_repo */
            $wallet_repo = $this->getEntityManager()->getRepository(Wallet::class);

            $wallet_repo->save($investor->getWallet(), true);

            return true;
        } else {
            //investment cannot be submitted
            return $validate_result;
        }
    }

    /**
     * Validate that an investment can be made. Checks Wallet balances
     *
     * @param @new_investment
     *
     * @return mixed
     */
    public function validateInvestment(Investment $new_investment)
    {
        //@todo
        //check investment amount against the wallet free_balance for the investor

        //get the investor and the latest wallet
        $user_repo = $this->getEntityManager()->getRepository(User::class);
        /** @var User $investor */
        $investor = $user_repo->find($new_investment->getUser()->getId());

        if ($investor->getWallet() === null) {
            return new ErrorResponse(ErrorResponse::ERROR_WALLET_USER_MISSING_WALLET);
        }

        //@todo should we check the user isn't blocked ?

        //@todo should we return an exception code object here ?

        if (
            $investor->getWallet()->getFreeBalance() > $new_investment->getInvestmentValue()
        ) {
            // Means we have funds.
            return true;
        } else {
            // Means we dont have funds.
            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'message' => 'Insufficient funds',
                    'wallet' => strval($investor->getWallet()),
                ],
                'status' => 400,
            ]);
        }
    }

    /**
     * @param Investment $investment
     * @return mixed
     */
    public function approveInvestment(Investment $investment)
    {
        switch ($investment->getLifecycleStatus()) {
            case InvestmentLifecycle::STATE_OPEN:
                $next_state = InvestmentLifecycle::TRANSITION_OPEN_APPROVAL;
                break;
            case InvestmentLifecycle::STATE_REJECTED:
                $next_state = InvestmentLifecycle::TRANSITION_REJECTED_APPROVED;
                break;
            default:
                throw new \Exception('Unhandled state transition');
        }

        /** @var Workflow $workflow */
        $workflow = $this->getWorkflowManager();

        if ($workflow->can($investment, $next_state) == true) {
            $workflow->apply($investment, $next_state);

            /** @var InvestmentRepository $invrep */
            $invrep = $this->getEntityManager()->getRepository(Investment::class);

            $invrep->save($investment, true);

            return true;
        }

        //@todo exception handing
        return false;
    }

    /**
     * @param Investment $investment
     * @return mixed
     */
    public function rejectInvestment(Investment $investment)
    {
        switch ($investment->getLifecycleStatus()) {
            case InvestmentLifecycle::STATE_OPEN:
                $next_state = InvestmentLifecycle::TRANSITION_OPEN_APPROVAL;
                break;
            case InvestmentLifecycle::STATE_APPROVED:
                $next_state = InvestmentLifecycle::TRANSITION_APPROVED_REJECTED;
                break;
            default:
                throw new \Exception('Unhandled state transition');
        }

        /** @var Workflow $workflow */
        $workflow = $this->getWorkflowManager();

        if ($workflow->can($investment, $next_state) == true) {
            $workflow->apply($investment, $next_state);

            /** @var InvestmentRepository $invrep */
            $invrep = $this->getEntityManager()->getRepository(Investment::class);

            $invrep->save($investment, true);

            return true;
        }

        //@todo exception handing
        return false;
    }

    /**
     * @param Investment $investment
     * @return mixed
     */
    public function withdrawInvestment(Investment $investment)
    {
        switch ($investment->getLifecycleStatus()) {
            case InvestmentLifecycle::STATE_OPEN:
                $next_state = InvestmentLifecycle::TRANSITION_OPEN_WITHDRAWN;
                break;
            case InvestmentLifecycle::STATE_APPROVED:
                $next_state = InvestmentLifecycle::TRANSITION_APPROVE_WITHDRAWN;
                break;
            default:
                throw new \Exception('Unhandled state transition');
        }

        /** @var Workflow $workflow */
        $workflow = $this->getWorkflowManager();

        if ($workflow->can($investment, $next_state) == true) {
            $workflow->apply($investment, $next_state);

            /** @var InvestmentRepository $invrep */
            $invrep = $this->getEntityManager()->getRepository(Investment::class);

            $invrep->save($investment, true);
            return true;
        }

        return false;
    }

    /**
     * @param $investmentId
     * @return mixed
     */
    public function checkInvestmentExists($investmentId)
    {
        /* @var Offering $resultOffering */
        $resultInvestment = $this->findOneById($investmentId);

        //check we have an offering
        if (is_null($resultInvestment)) {
            return false;
        } else {
            return $resultInvestment;
        }
    }

    //Function Using for investment creation Email

    public function sendInvestmentCreationMail(Investment $investment)
    {
        try {
            //User of the investment
            $user = $investment->getUser();

            $offering = $investment->getOffering();

            //Asset on which the investment done
            $asset = $offering->getAsset();

            $sent = $this->getEmailService()->sendMail(
                $user,
                MailerService::TYPE_INVESTMENT_NEW,
                [
                    'investment' => $investment,
                    'offering' => $offering,
                    'asset' => $asset,
                ],
            );

            if ($sent == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }
}
