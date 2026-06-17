<?php

namespace App\Service\Manager;

use App\Dto\AddressDTO;
use App\Dto\BankAccount;
use App\Dto\BankwireDetails;
use App\Dto\DocumentAssembler;
use App\Dto\DocumentDTO;
use App\Dto\UserAssembler;
use App\Dto\UserDTO;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Entity\UserClient;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use App\Event\User\UserCreatedEvent;
use App\Event\User\UserEmailVerifiedEvent;
use App\Event\User\UserUpdatedEvent;
use App\Event\UserDocument\UserDocumentCreatedEvent;
use App\Repository\UserDocumentRepository;
use App\Repository\UserRepository;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use App\Service\MangopayKycService;
use App\Service\MangopayWalletService;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * TODO add logging for exceptions which are thrown
 */

class UserManagerV2
{
    public function __construct(
        private UserRepository $userRepository,
        private UserDocumentRepository $userDocumentRepository,
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private Security $security,
        private UserAssembler $userAssembler,
        private DocumentAssembler $documentAssembler,
        private DocumentManager $documentManager,
        private Mangopay $mangopay,
        private MangopayWalletService $mangopayWalletService,
        private MangopayKycService $mangopayKycService,
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
        private UserClientManager $userClientManager,
    ) {}

    public function getUser($userId): ?User
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentUserId = $user->getId();
        $requestedUser = $this->userRepository->find($userId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $requestedUser;
        } else {
            if (!(null === $requestedUser)) {
                if ($currentUserId == $userId) {
                    return $requestedUser;
                } else {
                    throw new AccessDeniedHttpException('You do not have access to view User with ID '
                    . $userId);
                }
            }
            return null;
        }
    }

    public function getUsers($page, $limit): Pagerfanta
    {
        return $this->userRepository->findAllPagerfanta($page, $limit);
    }

    /**
     * @param UserDTO $userDTO
     * @return User
     */
    public function addUser(UserDTO $userDTO): User
    {
        $user = $this->userAssembler->createUser($userDTO);
        $managerClient = $this->findManagerClientForRequest();
        if ($managerClient) {
            $user->setManagedBy($managerClient->getUser());
            /**
             * If no referralCode in request
             * Provide fallback referralCode for managed users
             */
            if (!$user->getReferralCode()) {
                $user->setReferralCode($this->getManagerReferralCode($managerClient));
            }
        }
        $this->userRepository->save($user);
        $this->entityManager->flush();

        $event = new UserCreatedEvent($user);
        $this->eventDispatcher->dispatch($event, UserCreatedEvent::NAME);

        return $user;
    }

    public function getUserWallet(int $userId, string $walletId): ?\App\Dto\Wallet
    {
        $user = $this->userRepository->find($userId);
        if ($user) {
            if ($this->authorizationChecker->isGranted('get_wallet', $user)) {
                if (
                    $user->getLifecycleStatus()
                        == UserLifecycle::STATE_REGISTRATION_COMPLETE
                    or $user->getLifecycleStatus() == UserLifecycle::STATE_APPROVED
                ) {
                    try {
                        $mpWallet = $this->mangopay->getSingleWallet($walletId);
                    } catch (\MangoPay\Libraries\ResponseException $e) {
                        $this->logger->error(
                            'Error getting e-wallet for user id: '
                                . $userId
                                . ' Error code: '
                                . $e->GetCode()
                                . ' Error Message: '
                                . $e->getMessage()
                                . ' - '
                                . $e->GetErrorDetails(),
                        );
                        if ($e->GetCode() == 404) {
                            throw new NotFoundHttpException('No wallet found with ID: '
                            . $walletId);
                        }
                    } catch (\MangoPay\Libraries\Exception $e) {
                        $this->logger->error(
                            'Error getting e-wallet for user id: '
                                . $userId
                                . ' Error message: '
                                . $e->GetMessage(),
                        );
                        throw new \App\Exception\MangopayServiceException(
                            'There  was an issue processing this request',
                        );
                    }

                    $wallet = new \App\Dto\Wallet(
                        $mpWallet->Id,
                        \DateTime::createFromFormat('U', $mpWallet->CreationDate),
                        $mpWallet->Balance->Currency,
                        $mpWallet->Balance->Amount,
                    );

                    return $wallet;
                }
            } else {
                throw new AccessDeniedHttpException('Not authorized view wallet ID'
                . $walletId);
            }
        }

        return null;
    }

    /**
     * @param UserDTO $userDTO
     * @return User|null|AccessDeniedHttpException
     */
    public function updateUser(int $userId, UserDTO $userDTO): ?User
    {
        $canUpdate = false;
        $isAdmin = false;
        /** @var User $user */
        $user = $this->security->getUser();
        $currentUserId = $user->getId();
        $requestedUser = $this->userRepository->find($userId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $isAdmin = true;
            $canUpdate = true;
        }
        if ($currentUserId == $userId) {
            $canUpdate = true;
        }
        if (!$requestedUser) {
            return null;
        } elseif (!$isAdmin && $currentUserId != $userId) {
            throw new AccessDeniedHttpException('You do not have access to update User with ID '
            . $userId);
        }

        if ($canUpdate == true) {
            $user = $this->userAssembler->updateUser($requestedUser, $userDTO);
            $this->userRepository->save($user);
            $this->entityManager->flush();

            $event = new UserUpdatedEvent($user);
            $this->eventDispatcher->dispatch($event, UserUpdatedEvent::NAME);

            return $user;
        }

        return null;
    }

    /**
     * Add a document to a User object
     *
     * @param DocumentDTO $documentDTO
     * @return UserDocument
     */
    public function addDocument(int $userId, DocumentDTO $documentDTO): ?UserDocument
    {
        $requestedUser = $this->userRepository->find($userId);

        if ($requestedUser) {
            $document = $this->documentAssembler->createDocument($documentDTO);
            $mimeType = Helper::getFileMimeType((string) $document->getDocumentContent());

            /** @var User $user */
            $user = $this->security->getUser();

            $userDocument = new UserDocument();
            $userDocument->setCreatedById($user->getId());
            $userDocument->setUser($requestedUser);
            $userDocument->setDocument($document);

            if ($mimeType) {
                $userDocument->getDocument()->setType($mimeType);
            }

            $event = new UserDocumentCreatedEvent($userDocument);
            $this->eventDispatcher->dispatch($event, UserDocumentCreatedEvent::NAME);

            return $userDocument;
        }

        return null;
    }

    /**
     * Verify an email address related to a user object
     *
     * @param int $userId
     *
     * @return bool
     */
    public function verifyUserEmail(int $userId): bool
    {
        $user = $this->userRepository->find($userId);

        if ($user) {
            //restrict updating verification to only admin users
            if ($this->authorizationChecker->isGranted('verify_email', $user)) {
                if (
                    $user->getLifecycleStatus()
                    == UserLifecycle::STATE_EMAIL_NOT_VERIFIED
                ) {
                    $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_EMAIL_VERIFIED);

                    $log = new \App\Entity\UserLog();
                    $log
                        ->setUser($user)
                        ->setType(\App\Entity\UserLog::TYPE_USER)
                        ->setEvent(UserLifecycle::TRANSITION_EMAIL_VERIFICATION)
                        ->setMessage(
                            'Email verified for ['
                            . $user->getEmailCanonical()
                            . '] on %timestamp%',
                        );

                    $this->entityManager->flush();

                    $event = new UserEmailVerifiedEvent($user);
                    $this->eventDispatcher->dispatch(
                        $event,
                        UserEmailVerifiedEvent::NAME,
                    );
                }
                return true;
            }
            throw new AccessDeniedHttpException('Not authorized to verify email for User with ID '
            . $userId);
        }
        throw new NotFoundHttpException('No User with ID: ' . $userId . ' found');
    }

    /**
     * Helper to check if a user email is unique
     * Returns true if email is unique
     *
     * @param string $email
     * @return bool
     */
    public function checkEmailUnique(string $email): bool
    {
        try {
            $user = $this->userRepository->findByEmail($email);
            if (!$user) {
                return true;
            }
        } catch (NonUniqueResultException $e) {
            return false;
        }

        return false;
    }

    public function addBankwirePayin(
        int $userId,
        \App\Dto\BankwirePayinDTO $payinDTO,
    ): ?BankwireDetails {
        $user = $this->userRepository->find($userId);

        if ($user) {
            if ($this->authorizationChecker->isGranted('add_funds', $user)) {
                if (
                    $user->getLifecycleStatus()
                        == UserLifecycle::STATE_REGISTRATION_COMPLETE
                    or $user->getLifecycleStatus() == UserLifecycle::STATE_APPROVED
                ) {
                    return $this->createBankwirePayinRequest($user, $payinDTO);
                } else {
                    throw new \App\Exception\UserStatusException(
                        'User must be at least registration complete before adding funds',
                    );
                }
            } else {
                throw new AccessDeniedHttpException('You do not have permission to add funds for User with ID '
                . $userId);
            }
        }
        return null;
    }

    public function findManagerClientForRequest(): ?UserClient
    {
        if ($this->security->getUser() && !$this->security->isGranted('ROLE_VENDOR')) {
            // if there is a user but they're NOT a vendor, not a manager
            return null;
        }
        $userClient = $this->userClientManager->findClientByTokenId();
        if (is_null($userClient)) {
            return null;
        }
        $manager = $userClient->getUser();
        if (in_array('ROLE_SUPER_ADMIN', $manager->getRoles())) {
            // if client is for super admin (main Yielders client), not a manager
            return null;
        }
        return $userClient;
    }

    public function getManagerReferralCode(UserClient $userClient): ?string
    {
        if ($userClient->getAlias()) {
            return $userClient->getAlias();
        }
        if ($userClient->getUser()) {
            return $this->generateUserAffiliateCode($userClient->getUser());
        }
        return null;
    }

    public function setCompanyApproved(User $user, ?bool $approved = null): bool
    {
        $customField = new UserCustomFields();
        $customField->setFieldKey('companyApprovedOn');
        $userInvestor = $user->getInvestor();
        if (
            $approved
            || is_null($approved) && !$user->findCustomFieldValue('companyApprovedOn')
        ) {
            $customField->setFieldValue(date(DATE_ATOM));
            $state = true;
            $userInvestor->setCorporateInvestor($state);
        } else {
            $customField->setFieldValue(0);
            $state = false;
            $userInvestor->setCorporateInvestor($state);
        }
        $user->findReplaceCustomField($customField);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $state;
    }

    public function generateUserAffiliateCode(User $user): string
    {
        return (
            mb_substr($user->getFirstname(), 0, 1)
            . explode(' ', $user->getLastname())[0]
            . $user->getId()
        );
    }

    protected function buildBankAccountDetails(\Mangopay\Payin $payin)
    {
        $address = new AddressDTO(
            $payin->PaymentDetails->BankAccount->OwnerAddress->AddressLine1,
            $payin->PaymentDetails->BankAccount->OwnerAddress->AddressLine2,
            null,
            $payin->PaymentDetails->BankAccount->OwnerAddress->Region,
            $payin->PaymentDetails->BankAccount->OwnerAddress->City,
            $payin->PaymentDetails->BankAccount->OwnerAddress->PostalCode,
            $payin->PaymentDetails->BankAccount->OwnerAddress->Country,
        );

        $bankAccount = new BankAccount(
            $payin->PaymentDetails->BankAccount->OwnerName,
            $address,
            $payin->PaymentDetails->BankAccount->Details->IBAN,
            $payin->PaymentDetails->BankAccount->Details->BIC,
        );

        return $bankAccount;
    }

    protected function createBankwirePayinRequest(
        User $user,
        \App\Dto\BankwirePayinDTO $payinDTO,
    ): BankwireDetails {
        $payinDetails = null;
        $payin = $this->mangopay->createBankwirePayin($user, $payinDTO);

        //check $payin object is not null
        if ($payin) {
            //check status of $payin object
            if ($payin->Status == 'CREATED' or $payin->Status == 'SUCCEEDED') {
                $bankAccount = $this->buildBankAccountDetails($payin);
                $payinDetails = new BankwireDetails(
                    $bankAccount,
                    $payin->PaymentDetails->WireReference,
                    $payin->PaymentDetails->DeclaredDebitedFunds->Amount,
                    $payin->PaymentDetails->DeclaredDebitedFunds->Currency,
                );
            }
        }

        if (!$payinDetails) {
            throw new \App\Exception\MangopayServiceException(
                'There  was an issue processing this request',
            );
        }

        return $payinDetails;
    }

    public function getKycState(User $user): array
    {
        $state = [];
        $mangopayUser = $this->getMangopayUser($user);
        $mangopayRegulatory = $this->getMangopayUserRegulatory($user);

        $state['contegoScore'] = $user->getContegoScore();
        $state['mangopayDocs'] = $this->getAllUserMangopayKycDocs($user);
        $state['mangopayStatus'] = $mangopayUser?->KYCLevel;
        $state['mangopayUserCategory'] = $mangopayUser?->UserCategory;
        $state['mangopayTermsAccepted'] = $mangopayUser?->TermsAndConditionsAccepted;
        $state['mangopayPersonType'] = $mangopayUser?->PersonType;
        $state['mangopayUserProfile'] = $mangopayUser;
        $state['mangopayRegulatory'] = $mangopayRegulatory;

        return $state;
    }

    public function getMangopayUser(User $user): \MangoPay\UserNaturalSca|\MangoPay\UserLegalSca|null
    {
        // Wrapped method for getting the Mangopay user - maybe redundant?
        $id = $user->getMangoPayUserId();
        try {
            if (null === $id) {
                throw new \Exception('Current user missing Mangopay Id');
            }
            $mangopayUser = $this->mangopayWalletService->getScaUser($id);
            if ($mangopayUser instanceof \MangoPay\User) {
                return $mangopayUser;
            }
        } catch (\Exception $e) {
            $this->logger->error('Mangopay error ' . $e->getMessage());
        }
        return null;
    }

    public function getMangopayUserRegulatory(User $user): ?\MangoPay\UserBlockStatus
    {
        // Wrapped method for getting the Mangopay regulatory - maybe redundant?
        $id = $user->getMangoPayUserId();
        try {
            if (null === $id) {
                throw new \Exception('Current user missing Mangopay Id');
            }
            $mangopayRegulatory = $this->mangopayWalletService->getUserRegulatory($id);
            if ($mangopayRegulatory instanceof \MangoPay\UserBlockStatus) {
                return $mangopayRegulatory;
            }
        } catch (\Exception $e) {
            $this->logger->error('Mangopay error ' . $e->getMessage());
        }
        return null;
    }

    public function getAllUserMangopayKycDocs(User $user): array
    {
        $docs = [];
        $id = $user->getMangoPayUserId();
        try {
            if (null === $id) {
                throw new \Exception('Current user missing Mangopay Id');
            }
            $sorting = new \MangoPay\Sorting();
            $sorting->AddField('CreationDate', 'DESC');
            $result = $this->mangopayKycService->getAllUserKYCDocuments(
                userId: $id,
                sorting: $sorting,
            );
            if (!empty($result)) {
                $docs = $result;
            }
        } catch (\Exception $e) {
            $this->logger->error('Mangopay error ' . $e->getMessage());
        }

        return $docs;
    }

    public function getSuperAdmin(): ?User
    {
        /** @var User[] $users */
        $users = $this->userRepository->findByRole('ROLE_SUPER_ADMIN');
        if (count($users) < 1) {
            $this->logger->error('Superadmin is not set');
            throw new \RuntimeException('Superadmin is not set');
        }
        if (count($users) > 1) {
            $this->logger->error('Superadmin is not unique');
            throw new \RuntimeException('Superadmin is not unique');
        }
        // Iterate over result as only sure the result is iterable, not necessarily array accessible
        foreach ($users as $user) {
            return $user;
        }
        return null;
    }
}
