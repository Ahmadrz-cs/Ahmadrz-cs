<?php

namespace App\Controller\ApiV1;

use App\Dto\BankAccount\BankAccountActionCompletionRequestDto;
use App\Dto\BankAccount\BankAccountQueryDto;
use App\Dto\BankAccount\BankAccountRequestDto;
use App\Dto\BankAccount\BankAccountSchemaQueryDto;
use App\Dto\BankAccount\BankAccountSyncRequestDto;
use App\Dto\Sca\ScaOutcomeRequestDto;
use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountStatus;
use App\Repository\BankAccountRepository;
use App\Service\BankAccountService;
use App\Service\BankAccountSyncService;
use App\Service\MangopayScaService;
use App\Service\MangopayWalletService;
use App\Service\Mapper\BankAccountMapper;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Uid\Uuid;

class SelfBankAccountsController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private BankAccountMapper $bankAccountMapper,
        private BankAccountService $bankAccountService,
        private BankAccountSyncService $bankAccountSyncService,
        private MangopayWalletService $mangopayWalletService,
    ) {}

    #[Get(
        '/%api_network_path%/self/bank-accounts/schema',
        name: 'api_get_self_bank_accounts_schema',
    )]
    public function getSchema(
        #[MapQueryString] BankAccountSchemaQueryDto $dto,
    ): Response {
        $countryAlpha2 = $dto->country ?: 'GB';
        $this->logger->debug('Get bank account schema', ['country' => $countryAlpha2]);
        try {
            $schema = $this->bankAccountService->getSchemaForRecipient($countryAlpha2);
            $schema = $this->bankAccountService->normalizeSchema(
                $schema,
                $countryAlpha2,
            );
        } catch (\Mangopay\Libraries\ResponseException $th) {
            $this->logger->error("Unable to retrieve schema for {$countryAlpha2}", [
                $th->GetErrorDetails()->Errors,
            ]);
            throw new NotFoundHttpException(
                "Unable to retrieve schema for country: {$countryAlpha2}",
            );
        } catch (\Throwable $th) {
            $this->logger->error(
                "Unable to retrieve schema for country: {$countryAlpha2}",
                [$th->getMessage()],
            );
            throw new NotFoundHttpException(
                "Unable to retrieve schema for country: {$countryAlpha2}",
            );
        }
        return $this->json(data: $schema);
    }

    #[Post(
        '/%api_network_path%/self/bank-accounts',
        name: 'api_post_self_bank_accounts',
    )]
    public function createRegistration(
        #[MapRequestPayload(validationGroups: ['create'])] BankAccountRequestDto $dto,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Create bank account registrations for user', ['user' => $user->getId()]);
        $bankAccount = $this->bankAccountMapper->mapToEntity($dto);
        $bankAccount->setUser($user);
        // Check for duplicates via fingerprint, if not a duplicate, persist, if duplicate throw exception
        if (!$this->bankAccountService->isNotDuplicated($bankAccount)) {
            throw new BadRequestHttpException(
                'A similar bank account registration already exists.',
            );
        }
        $this->bankAccountService->validateWithMangopay($bankAccount);
        $this->em->persist($bankAccount);
        $this->em->flush();

        $this->bankAccountService->sendCreationNotification($bankAccount);
        $this->bankAccountService->sendReviewNotification($bankAccount);
        return $this->json(
            data: $this->bankAccountMapper->mapToDto($bankAccount),
            status: Response::HTTP_CREATED,
        );
    }

    #[Get('/%api_network_path%/self/bank-accounts', name: 'api_get_self_bank_accounts')]
    public function listRegistrations(
        #[MapQueryString] BankAccountQueryDto $dto,
        BankAccountRepository $bankAccountRepository,
        NormalizerInterface $normalizer,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Get bank accounts', ['user' => $user->getId()]);
        $filters = array_merge($normalizer->normalize($dto), ['userId' =>
            $user->getId()]);
        $registrations = $bankAccountRepository->findByWithAssociations($filters);
        $dto = $this->bankAccountMapper->mapMultipleToDto($registrations);
        return $this->json($dto);
    }

    #[Post(
        '/%api_network_path%/self/bank-accounts/mangopay-sync',
        name: 'api_get_self_bank_accounts_mangopay_sync',
    )]
    public function syncRegistrations(
        #[MapRequestPayload] BankAccountSyncRequestDto $dto,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $registrations = [];
        if ($user->getBankAccountsSyncedAt() === null) {
            // We'll sync with legacy bank accounts as that is the main purpose of this route
            $registrations = $this->bankAccountSyncService->syncBankAccounts(
                user: $user,
                limit: $dto->limit,
                useRecipients: false,
            );
            $this->em->flush();
        } else {
            $this->logger->debug('Bank accounts previously synced. Not syncing again.', [
                'user' => $user->getId(),
                'lastSynced' => $user->getBankAccountsSyncedAt()->format(\DateTime::ATOM),
            ]);
        }
        $this->logger->debug(
            'Number of registrations synced: ' . count($registrations),
        );

        $dto = $this->bankAccountMapper->mapMultipleToDto($registrations);
        return $this->json($dto);
    }

    #[Get(
        '/%api_network_path%/self/bank-accounts/{id}',
        name: 'api_get_self_bank_accounts_single',
    )]
    public function retrieveRegistration(
        string $id,
        BankAccountRepository $bankAccountRepository,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $filters = ['user' => $user->getId()];
        if (Uuid::isValid($id)) {
            $filters['uuid'] = $id;
        } else {
            $filters['id'] = $id;
        }
        $this->logger->debug('Get self bank account', [
            'user' => $user->getId(),
            'id' => $id,
        ]);
        $registration = $bankAccountRepository->findOneBy($filters);
        if ($registration === null) {
            throw new NotFoundHttpException('No bank account registration found');
        }
        // Detach the retrieved entity to avoid accidentally committing changes to database as part of syncing process
        $this->em->detach($registration);
        $registration =
            $this->bankAccountSyncService->loadAccountDetails($registration);
        $dto = $this->bankAccountMapper->mapToDto($registration);
        return $this->json($dto);
    }

    #[Post(
        '/%api_network_path%/self/bank-accounts/{bankAccountId}/activation',
        name: 'api_post_self_bank_accounts_activation',
    )]
    public function createActivation(
        #[MapEntity(id: 'bankAccountId')] BankAccount $bankAccount,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Activate bank account registrations', [
            'user' => $user->getId(),
            'bankAccount' => $bankAccount->getId(),
            'owner' => $bankAccount->getUser()->getId(),
        ]);
        if ($bankAccount->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedException(
                'Can only access your own bank account registrations',
            );
        }
        try {
            $dto = $this->bankAccountService->activateBankAccount($bankAccount);
            $this->em->flush();
        } catch (\Throwable $th) {
            // Log and rethrow the exception
            $this->logger->error('Unable to activate bank account', [
                'user' => $user->getId(),
                'bankAccount' => $bankAccount->getId(),
                'error' => $th->getMessage(),
            ]);
            throw $th;
        }

        return $this->json($dto);
    }

    #[Post(
        '/%api_network_path%/self/bank-accounts/{bankAccountId}/action-completion',
        name: 'api_post_self_bank_accounts_action_completion',
    )]
    public function createActionCompletion(
        #[MapRequestPayload] BankAccountActionCompletionRequestDto $dto,
        #[MapEntity(id: 'bankAccountId')] BankAccount $bankAccount,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Process action completion bank account registrations', [
            'user' => $user->getId(),
            'bankAccount' => $bankAccount->getId(),
            'actions' => $dto->actionRequests,
        ]);
        if ($bankAccount->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedException(
                'Can only access your own bank account registrations',
            );
        }

        $pendingActions = $this->bankAccountService->actionRequestsAsEnum(
            $bankAccount->getMetadata()['actionRequests'] ?? [],
        );
        $completedActions = $this->bankAccountService->actionRequestsAsEnum($dto->actionRequests);
        $newActions = array_udiff(
            $pendingActions,
            $completedActions,
            fn($a1, $a2) => $a1->value <=> $a2->value,
        );
        $metadata = $bankAccount->getMetadata();
        if (empty($newActions)) {
            unset($metadata['actionRequests']);
            if (in_array($bankAccount->getStatus()->value, [
                BankAccountStatus::Pending->value,
                BankAccountStatus::Validated->value,
            ])) {
                $this->bankAccountService->sendReviewNotification($bankAccount, true);
            }
        } else {
            $metadata['actionRequests'] = $newActions;
        }
        $bankAccount->setMetadata($metadata);
        $this->em->flush();

        return $this->json($this->bankAccountMapper->mapToDto($bankAccount));
    }

    #[Post(
        '/%api_network_path%/self/bank-accounts/{bankAccountId}/activation-outcome',
        name: 'api_post_self_bank_accounts_activation_outcome',
    )]
    public function createActivationOutcome(
        #[MapRequestPayload] ScaOutcomeRequestDto $dto,
        #[MapEntity(id: 'bankAccountId')] BankAccount $bankAccount,
        MangopayScaService $mangopayScaService,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Process activation SCA outcome bank account registrations', [
            'user' => $user->getId(),
            'bankAccount' => $bankAccount->getId(),
            'owner' => $bankAccount->getUser()->getId(),
        ]);
        if ($bankAccount->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedException(
                'Can only access your own bank account registrations',
            );
        }
        $success = $dto->success;
        if ($dto->verify) {
            $this->logger->debug('Verifying activation SCA outcome');
            $success = $mangopayScaService->isRecipientActivated($bankAccount->getProviderId());
        }
        $this->bankAccountService->processActivationOutcome($bankAccount, $success);
        $this->em->flush();

        return $this->json($this->bankAccountMapper->mapToDto($bankAccount));
    }

    #[Delete(
        '/%api_network_path%/self/bank-accounts/{bankAccountId}',
        name: 'api_delete_self_bank_accounts_single',
    )]
    public function deleteAndDeactivate(
        #[MapEntity(id: 'bankAccountId')] BankAccount $bankAccount,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Deactivate bank account', [
            'user' => $user->getId(),
            'bankAccount' => $bankAccount->getId(),
            'owner' => $bankAccount->getUser()->getId(),
        ]);

        if ($bankAccount->getUser()->getId() != $user->getId()) {
            throw new AccessDeniedException(
                'Can only access your own bank account registrations',
            );
        }
        try {
            $this->bankAccountService->disableBankAccount($bankAccount);
            $this->em->flush();
        } catch (\Throwable $th) {
            // Log and rethrow the exception
            $this->logger->error('Unable to deactivate bank account', [
                'user' => $user->getId(),
                'bankAccount' => $bankAccount->getId(),
                'providerId' => $bankAccount->getProviderId(),
                'error' => $th->getMessage(),
            ]);
            // throw new BadRequestHttpException("An error occured when attempting to deactive your bank account registration");
            throw $th;
        }

        return $this->json($this->bankAccountMapper->mapToDto($bankAccount));

        // return new JsonResponse(null, 204);
    }
}
