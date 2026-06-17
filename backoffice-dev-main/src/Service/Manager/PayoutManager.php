<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 18/12/16
 * Time: 17:07
 */

namespace App\Service\Manager;

use App\Entity\Payout;
use App\Entity\User;
use App\Repository\CommunicationRepository;
use App\Repository\PayoutRepository;
use App\Repository\UserRepository;
use App\Service\DocumentService;
use App\Service\MailerService;
use App\Service\Manager\BaseManager;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

class PayoutManager extends BaseManager
{
    protected $entityClass = Payout::class;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private MailerService $mailservice,
        private MangoPay $mangopay,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
        private WorkflowInterface $investmentStateMachine,
        private UserRepository $userRepository,
        private PayoutRepository $payoutRepository,
        private CommunicationRepository $communicationRepository,
    ) {
        $this->mangopay = $mangopay;
        $this->userRepository = $userRepository;
        $this->payoutRepository = $payoutRepository;
        $this->communicationRepository = $communicationRepository;
        parent::__construct(
            $logger,
            $entityManager,
            $security,
            $mailservice,
            $documentManager,
            $documentService,
            $investmentStateMachine,
        );
    }

    public function findAllPayout()
    {
        $resultValues = $this->findAllValue();
        return $resultValues;
    }

    /**
     * Get all investments
     *
     * @return array
     */
    public function findAllPayoutById()
    {
        $resultValues = $this->findAllOrderById();
        return $resultValues;
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

    public function findByQuery(array $queryParams, bool $admin = false): array
    {
        $criteria = $this->getCriteria($queryParams);
        $sort = $this->getSortPreferences($queryParams['sort']);
        // payouts don't support any auxiliary filters - the entity is fairly simple and standalone

        $results = $this->findBy(
            $criteria,
            $sort,
            $queryParams['limit'],
            $queryParams['offset'],
        );

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
        $criteriaAllowed = ['id'];
        foreach ($queryParams as $key => $query) {
            if (in_array($key, $criteriaAllowed)) {
                if (!empty($query)) {
                    $criteria[$key] = explode(',', $query);
                }
            }
        }

        // criteria with mapped property names
        if (!empty($queryParams['type']) || $queryParams['type'] === '0') {
            $typesAsString = explode(',', $queryParams['type']);
            foreach ($typesAsString as $type) {
                if ($type === 'dividend' || $type === '0') {
                    $criteria['payoutType'][] = 0;
                }
                if ($type === 'profitshare' || $type === '1') {
                    $criteria['payoutType'][] = 1;
                }
            }
        }

        // $this->getLogger()->info('Criteria: ' . json_encode($criteria));

        return $criteria;
    }
}
