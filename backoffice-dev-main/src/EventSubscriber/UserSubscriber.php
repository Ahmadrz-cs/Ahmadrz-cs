<?php

namespace App\EventSubscriber;

use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\UserCustomFields;
use App\Event\User\UserCreatedEvent;
use App\Event\User\UserEmailVerifiedEvent;
use App\Event\User\UserEvent;
use App\Event\User\UserUpdatedEvent;
use App\Event\UserDocument\UserDocumentCreatedEvent;
use App\Event\UserDocument\UserDocumentEvent;
use App\Repository\UserDocumentRepository;
use App\Service\ContegoService;
use App\Service\MailerService;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use App\Service\SalesforceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private MangoPay $mangopay,
        private UserDocumentRepository $userDocumentRepository,
        private DocumentManager $documentManager,
        private SalesforceService $salesforceservice,
        private ContegoService $contegoService,
        private MailerService $mailerService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::NAME => 'userCreated',
            UserUpdatedEvent::NAME => 'userUpdated',
            UserEmailVerifiedEvent::NAME => 'emailVerified',
            UserDocumentCreatedEvent::NAME => 'userDocumentCreated',
        ];
    }

    /**
     * Runs mangopay actions (mangopay sign up and wallet creation) and updates user status
     *
     * @param UserEvent $event
     *
     */
    public function userCreated(UserEvent $event)
    {
        $user = $event->getUser();
        $this->mangopayActions($user);
        $this->updateUserStatus($user);
    }

    /**
     * Runs mangopay actions (mangopay sign up and wallet creation) and updates user status
     *
     * @param UserEvent $event
     *
     */
    public function userUpdated(UserEvent $event)
    {
        $user = $event->getUser();
        $this->mangopayActions($user);
        $this->updateUserStatus($user);
        $this->salesforceActions($user);
    }

    /**
     * Runs mangopay actions (mangopay sign up and wallet creation) and updates user status
     *
     * @param UserEvent $event
     *
     */
    public function emailVerified(UserEvent $event)
    {
        $user = $event->getUser();
        $this->mangopayActions($user);
        $this->updateUserStatus($user);
        $this->salesforceActions($user);
    }

    /**
     * Runs mangopay actions (mangopay sign up and wallet creation).
     * Uploads file to S3 and runs KYC actions if file is proof_of_identity doc.
     * Updates user status.
     *
     * @param UserDocumentEvent $event
     *
     */
    public function userDocumentCreated(UserDocumentEvent $event)
    {
        $user = $event->getUserDocument()->getUser();
        $userDocument = $event->getUserDocument();

        $this->mangopayActions($user);
        $this->uploadFile($userDocument);
        $this->updateUserStatus($user);
    }

    /**
     * Registers the user to Mangopay if the Mangopay user creation criteria is met
     *
     * @param UserEvent $event
     *
     */
    protected function mangopayActions(\App\Entity\User $user)
    {
        $mangopayValid = $this->isValidForMangopay($user);
        $mangopayWalletId = $user->getMangoPayWalletId();

        //Register user to mangopay if the user does not have a mangopay account
        if ($mangopayValid) {
            $mangopayUser = $this->registerToMangopay($user);
            if ($mangopayUser) {
                $user->setMangoPayUserId($mangopayUser->Id);
                $this->entityManager->flush();
            }
        }

        //Create a mangopay wallet if the user does not have one
        if (!$mangopayWalletId and $user->getMangoPayUserId()) {
            $mangopayWallet = $this->createMangopayWallet($user);
            if ($mangopayWallet) {
                $user->setMangoPayWalletId($mangopayWallet->Id);
                $this->entityManager->flush();
            }
        }
    }

    /**
     * @param UserEvent $event
     */
    protected function uploadFile(\App\Entity\UserDocument $userDocument)
    {
        if ($userDocument->getDocument()->getTag() == 'proof_of_identity') {
            $this->kycActions($userDocument);
        }

        $saveDocument = $this->documentManager->saveInFileStore(
            $userDocument->getDocument(),
            'private',
            'user/' . $userDocument->getUser()->getId(),
        );

        //if document is saved in file store, persist to database
        if ($saveDocument) {
            $this->userDocumentRepository->save($userDocument);
            $this->entityManager->flush();
        }
    }

    /**
     * Updates the lifecycle status of a user object
     *
     * @param User $user
     *
     */
    protected function updateUserStatus(\App\Entity\User $user)
    {
        $status = $user->getStatus();
        $emailVerified = false;

        if ($status->getIsEmailNotVerifed() and !$status->getIsEmailValidated()) {
            $status->setLifecycleStatus(UserLifecycle::STATE_EMAIL_NOT_VERIFIED);
            $this->entityManager->flush();
            return;
        }
        if ($status->getIsEmailValidated()) {
            $emailVerified = true;
        }
        if ($this->isOnboardingComplete($user) and $emailVerified) {
            if ($user->getContegoScore()->getRAG() == 'GREEN') {
                $status->setLifecycleStatus(UserLifecycle::STATE_REGISTRATION_COMPLETE);
                $status->setLifecycleStatus(UserLifecycle::STATE_APPROVED);
                $status->setIsRegCompleted(true);
                $this->entityManager->flush();
            }
            //For backwards comabtibility with cms user tags, set ob step to 'Onboarding Complete'
            if ($user->getOBStep() !== 5) {
                if (
                    $user->getContegoScore()->getRAG() == 'GREEN'
                    or $user->getContegoScore()->getRAG() == 'AMBER'
                    or $user->getContegoScore()->getRAG() == 'RED'
                ) {
                    $user->setOBStep(5);
                    $this->entityManager->flush();
                }
            }
        }
    }

    public function salesforceActions(\App\Entity\User $user): void
    {
        if ($user->getStatus()->getIsEmailValidated()) {
            $sf_id = $user->findCustomFieldValue('salesforce_id');

            if ($sf_id == '') {
                $this->createSaleforceUser($user);
            } else {
                $this->updateSalesforceUser($user, $sf_id);
            }
        }
    }

    public function createSaleforceUser(\App\Entity\User $user): void
    {
        try {
            $response = $this->salesforceservice->create(
                'Contact',
                $user->getSalesforceJson(true),
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->logger->error(
                'Salesforce user not created: '
                    . $e->getResponse()->getStatusCode()
                    . ' - '
                    . $e->getResponse()->getBody(),
            );
            return;
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->logger->error(
                'Salesforce user not created: '
                . $e->getResponse()->getStatusCode()
                . ' - Unable to reach Salesforce',
            );
            return;
        } catch (\Exception $e) {
            $this->logger->error('Salesforce user not created: Error when trying to contact Salesforce: '
            . $e);
            return;
        }

        $rspcode = $response->getStatusCode();
        if ($rspcode == 201) {
            $rspbody = json_decode($response->getBody(), true);
            $salesforceId = new UserCustomFields();
            $salesforceId->setUser($user);
            $salesforceId->setFieldKey('salesforce_id');
            $salesforceId->setFieldValue($rspbody['id']);
            $user->addCustomField($salesforceId);
            $this->entityManager->flush();
        }
    }

    public function updateSalesforceUser(\App\Entity\User $user, string $sfId): void
    {
        try {
            $this->salesforceservice->update(
                'Contact',
                $sfId,
                $user->getSalesforceJson(true),
            );
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $this->logger->error(
                'Salesforce user not updated: '
                    . $e->getResponse()->getStatusCode()
                    . ' - '
                    . $e->getResponse()->getBody(),
            );
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $this->logger->error(
                'Salesforce user not updated: '
                . $e->getResponse()->getStatusCode()
                . ' - Unable to reach Salesforce',
            );
        } catch (\Exception $e) {
            $this->logger->error('Salesforce user not updated: Error when trying to contact Salesforce: '
            . $e);
        }
    }

    /**
     * Run contegeo kyc check and upload the document to mangopay
     *
     * @param UserDocument $userDocument
     */
    protected function kycActions(\App\Entity\UserDocument $userDocument)
    {
        $user = $userDocument->getUser();

        if ($this->isUserKycValid($user)) {
            $this->contegoCheck($userDocument);
            $this->uploadDocToMangopay($userDocument);
        }
    }

    /**
     * Checks if a user object is in an acceptable state to register to Mangopay.
     *
     * @param User $user
     *
     * @return bool
     */
    protected function isValidForMangopay(\App\Entity\User $user)
    {
        //check user is not already registered with mangopay
        if ($user->getMangoPayUserId()) {
            return false;
        }
        if (!$user->getFirstname()) {
            return false;
        }

        if (!$user->getLastname()) {
            return false;
        }

        if (!$user->getBirthDate()) {
            return false;
        }

        if (!$user->getNationality()) {
            return false;
        }

        if (!$user->getMainAddress()->getCountry()) {
            return false;
        }

        return true;
    }

    /**
     * Register user to Mangopay.
     *
     * @param User $user
     *
     * @return \Mangopay\UserNaturalSca|null
     */
    protected function registerToMangopay(\App\Entity\User $user)
    {
        //TODO check type of user - register as legal user if user represents a company
        // try {
        //     $mangopayUser = $this->mangopay->createNaturalUser($user);
        //     return $mangopayUser;
        // } catch (\Exception $e) {
        //     $this->logger->error("Error occured in Mangopay createNaturalUser for user with id: " . $user->getId() . " Exception message: " . json_encode($e->getMessage()));
        // }
    }

    /**
     * Create a Mangopay wallet for a user object.
     *
     * @param User $user
     *
     * @return \Mangopay\Wallet|null
     */
    protected function createMangopayWallet(\App\Entity\User $user)
    {
        try {
            $mangopayWallet = $this->mangopay->createUserWallet($user);
            return $mangopayWallet;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay createUserWallet for user with id: '
                    . $user->getId()
                    . ' Exception message: '
                    . json_encode($e->getMessage()),
            );
        }
    }

    /**
     * Check if a user object state is onboarding complete
     *
     * @param User $user
     *
     * @return bool
     */
    protected function isOnboardingComplete(\App\Entity\User $user)
    {
        $registrationComplete = [
            'firstName',
            'lastName',
            'nationality',
            'gender',
            'birthday',
            'phone',
            'address',
            'mangopayUserId',
            'mangopayWalletId',
            'proofOfId',
            'proofOfAddress',
        ];

        $userStatus = [];

        if ($user->getFirstname()) {
            array_push($userStatus, 'firstName');
        }
        if ($user->getLastname()) {
            array_push($userStatus, 'lastName');
        }
        if ($user->getNationality()) {
            array_push($userStatus, 'nationality');
        }
        if ($user->getGender()) {
            array_push($userStatus, 'gender');
        }
        if ($user->getBirthDate()) {
            array_push($userStatus, 'birthday');
        }
        if ($user->getPhone1() or $user->getMobile()) {
            array_push($userStatus, 'phone');
        }
        if ($user->getMainAddress()->getCountry()) {
            array_push($userStatus, 'address');
        }
        if ($user->getMangoPayUserId()) {
            array_push($userStatus, 'mangopayUserId');
        }
        if ($user->getMangoPayWalletId()) {
            array_push($userStatus, 'mangopayWalletId');
        }
        if ($user->getProofOfIdDocument()) {
            array_push($userStatus, 'proofOfId');
        }
        if ($user->getProofOfAddressDocument()) {
            array_push($userStatus, 'proofOfAddress');
        }

        if ($registrationComplete == $userStatus) {
            return true;
        }

        return false;
    }

    /**
     * Checks a proof of identity document with the Contego service
     *
     * @param UserDocument $userDocument
     *
     * @return void
     * @throws ContegoServiceException
     */
    protected function contegoCheck(\App\Entity\UserDocument $userDocument): void
    {
        $user = $userDocument->getUser();

        try {
            $this->contegoService->createUserKYC($user, true);
        } catch (\Exception $e) {
            $this->logger->error(
                'There was an error attempting basic contego check: '
                    . $e->getMessage(),
            );
            throw new \App\Exception\ContegoServiceException();
        }

        if ($user->getContegoScore()->getRAG() != 'GREEN') {
            try {
                $this->contegoService->createUserKYCWithDoc($user, $userDocument, true);
            } catch (\Exception $e) {
                $this->logger->error(
                    'There was an error attempting contego check with document: '
                        . $e->getMessage(),
                );
                throw new \App\Exception\ContegoServiceException();
            }
        }

        //Send KYC confirmation emails
        if ($user->getContegoScore()->getRAG() == 'GREEN') {
            $this->mailerService->sendMail($user, MailerService::TYPE_OB_COMPLETE, [
                'user' => $user,
            ]);
            $this->mailerService->adminMailEntry(
                $user,
                MailerService::TYPE_OB_COMPLETE_ADMIN,
                ['user' => $user, null],
                null,
            );
        } else {
            $this->mailerService->sendMail($user, MailerService::TYPE_OB_CONTACT, [
                'user' => $user,
            ]);
            $this->mailerService->adminMailEntry(
                $user,
                MailerService::TYPE_OB_CONTACT_ADMIN,
                ['user' => $user],
                null,
            );
        }
    }

    /**
     * Uploads a proof of identity document to mangopay
     *
     * @param UserDocument $userDocument
     *
     * @return void
     * @throws MangopayServiceException
     */
    protected function uploadDocToMangopay(\App\Entity\UserDocument $userDocument): void
    {
        try {
            $this->mangopay->addKYCDocument($userDocument);
        } catch (\Exception $e) {
            $this->logger->error(
                'There was an error attempting to upload document to mangopay: '
                    . $e->getMessage(),
            );
            throw new \App\Exception\MangopayServiceException();
        }
    }

    /**
     * Checks if a user object is valid for a proof of identity checks
     *
     * @param UserDocument $userDocument
     * @return bool
     * @throws NationalityNotFoundException
     * @throws BirthdateNotFoundException
     * @throws AddressNotFoundException
     */
    protected function isUserKycValid(\App\Entity\User $user)
    {
        if (empty($user->getNationality())) {
            throw new \App\Exception\NationalityNotFoundException(
                'User must have a nationality before adding proof of identity document',
            );
        }
        if (empty($user->getBirthDate())) {
            throw new \App\Exception\BirthdateNotFoundException(
                'User must have a date of birth before adding proof of identity document',
            );
        }
        if (empty($user->getMainAddress()->getCountry())) {
            throw new \App\Exception\AddressNotFoundException(
                'User must have an Address with a country before adding proof of identity document',
            );
        }

        return true;
    }
}
