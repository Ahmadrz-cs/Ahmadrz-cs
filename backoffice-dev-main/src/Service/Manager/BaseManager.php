<?php

namespace App\Service\Manager;

use App\Service\DocumentService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @deprecated Use per service dependency injection or configure parent services
 * https://symfony.com/doc/current/service_container/parent_services.html
 */
class BaseManager
{
    protected $entityClass;

    private $repository;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private MailerService $mailerService,
        private DocumentManager $documentManager,
        private DocumentService $documentService,
        private WorkflowInterface $investmentStateMachine,
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->mailerService = $mailerService;
        $this->documentManager = $documentManager;
        $this->documentService = $documentService;
        $this->investmentStateMachine = $investmentStateMachine;

        $this->repository = $this->entityManager->getRepository($this->entityClass);
    }

    /**
     * Wrapper around doctrine's findBy but repository is set by respective manager
     * General purpose for our use case
     * array is optional, default no criteria
     *
     * @param array      $criteria
     * @param array|null $sort
     * @param int|null   $limit
     * @param int|null   $offset
     *
     * @return array The objects.
     */
    public function findBy(
        array $criteria = [],
        ?array $sort = null,
        $limit = null,
        $offset = null,
    ) {
        return $this->repository->findBy($criteria, $sort, $limit, $offset);
    }

    /**
     * Return all items for the entity
     * @param $offset
     * @param $limit
     * @param null $sort
     * @return array
     */
    public function getAll($offset, $limit, $sort = null)
    {
        return $this->repository->findBy([], $sort, $limit, $offset);
    }

    public function findAll($offset, $limit)
    {
        return $this->repository->findBy(
            [
                'createdBy' => $this->getUser(),
            ],
            [
                'updatedAt' => 'DESC',
            ],
            $limit,
            $offset,
        );
    }

    public function findAllOrderById()
    {
        return $this->repository->findBy([], [
            'id' => 'DESC',
        ]);
    }

    public function getCount()
    {
        return $this->repository->count([
            'createdBy' => $this->getUser(),
        ]);
    }

    public function findAllValue($offset = '', $limit = '', $conditions = [])
    {
        // convert to int
        $offset = (int) $offset;
        $limit = (int) $limit;

        if (empty($offset) && empty($limit)) {
            if (empty($conditions)) {
                $conditions = [];
            }
            return $this->repository->findBy($conditions, [
                'updatedAt' => 'DESC',
            ]);
        } elseif (!empty($conditions)) {
            return $this->repository->findBy(
                $conditions,
                [
                    'updatedAt' => 'DESC',
                ],
                $limit,
                $offset,
            );
        } else {
            return $this->repository->findBy(
                [],
                [
                    'updatedAt' => 'DESC',
                ],
                $limit,
                $offset,
            );
        }
    }

    public function findByValue($offset, $limit, $conditions = [])
    {
        if (!empty($conditions)) {
            return $this->repository->findBy(
                $conditions,
                [
                    'updatedAt' => 'DESC',
                ],
                $limit,
                $offset,
            );
        } else {
            return $this->repository->findBy(
                [],
                [
                    'updatedAt' => 'DESC',
                ],
                $limit,
                $offset,
            );
        }
    }

    public function findAllCount($conditions = [])
    {
        if (!empty($conditions)) {
            return $this->repository->count($conditions);
        } else {
            return $this->repository->count([], []);
        }
    }

    public function findOneById($id)
    {
        return $this->repository->findOneById(['id' => $id]);
    }

    /**
     * Sort QueryParam comma separated string
     * +/- optional prefix to define ASC/DESC order respectively
     * If no symbol provided, default to DESC
     */
    public function getSortPreferences($sortString): array
    {
        // default sorts updatedAt DESC - can be overriden by provided params
        $sortCriteria = [];
        if (!empty($sortString)) {
            $criteria = explode(',', $sortString);
            foreach ($criteria as $critItem) {
                $firstChar = substr($critItem, 0, 1);
                if ($firstChar == '+') {
                    $sortCriteria[substr($critItem, 1)] = 'ASC';
                } elseif ($firstChar == '-') {
                    $sortCriteria[substr($critItem, 1)] = 'DESC';
                } else {
                    // don't allow just ordering - compatability for current frontend
                    if (!in_array($critItem, ['desc', 'asc', 'DESC', 'ASC'])) {
                        $sortCriteria[$critItem] = 'DESC';
                    }
                }
            }
        }
        if (!array_key_exists('updatedAt', $sortCriteria)) {
            $sortCriteria['updatedAt'] = 'DESC';
        }
        return $sortCriteria;
    }

    /**
     * Gets currently logged in User
     *
     * @return mixed
     * @see TokenInterface::getUser()
     */
    protected function getUser()
    {
        $token = $this->security->getToken();
        if (null === $token) {
            return;
        }

        if (!is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return;
        }

        return $user;
    }

    protected function getEmailService()
    {
        return $this->mailerService;
    }

    protected function getWorkflowManager()
    {
        // Specifically the investment workflow
        return $this->investmentStateMachine;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getDocumentManager()
    {
        return $this->documentManager;
    }

    protected function getLogger()
    {
        // Specifically the crowdtek logger
        return $this->logger;
    }

    protected function getDocumentService()
    {
        return $this->documentService;
    }
}
