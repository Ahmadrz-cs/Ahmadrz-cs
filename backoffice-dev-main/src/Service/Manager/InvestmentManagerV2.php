<?php

namespace App\Service\Manager;

use App\Dto\DocumentAssembler;
use App\Dto\DocumentDTO;
use App\Dto\InvestmentAssembler;
use App\Dto\InvestmentDTO;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Offering;
use App\Entity\ShareTrade;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\InvestmentDocumentRepository;
use App\Repository\InvestmentRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\Manager\DocumentManager;
use App\Service\MangoPay;
use App\Service\Util\Helper;
use BcMath\Number;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\TransactionStatus;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class InvestmentManagerV2
{
    public function __construct(
        private InvestmentRepository $investmentRepository,
        private InvestmentDocumentRepository $investmentDocumentRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private Security $security,
        private DocumentAssembler $documentAssembler,
        private DocumentManager $documentManager,
        private InvestmentAssembler $investmentAssembler,
        private MailerService $mailService,
        private MangoPay $mangopayService,
        private LoggerInterface $logger,
    ) {}

    public function getInvestment(int $invId): ?Investment
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentUserId = $user->getId();
        $investment = $this->investmentRepository->find($invId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $investment;
        } else {
            if (!empty($investment)) {
                if ($investment->getUser()->getId() == $currentUserId) {
                    return $investment;
                } else {
                    throw new AccessDeniedHttpException(sprintf('You do not have access to view Investment with ID '
                    . $invId));
                }
            }
        }
        return null;
    }

    public function getInvestments(
        ?int $page,
        ?int $limit,
        string $idFilter = '',
        ?string $statusFilter = null,
    ): ?Pagerfanta {
        $idArray = [];

        if (!empty($idFilter)) {
            $idArray = explode(',', $idFilter);
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->investmentRepository->findAllPagerfanta(
                $page,
                $limit,
                $idArray,
                $statusFilter,
            );
        } else {
            throw new AccessDeniedHttpException(sprintf(
                'You do not have access to view Investments',
            ));
        }
        return null;
    }

    public function addInvestment(InvestmentDTO $investmentDTO): ?Investment
    {
        $investment = $this->investmentAssembler->createInvestment($investmentDTO);
        $numberOfShares = $investment->getOffering()->getNoOfShares();
        if (empty($numberOfShares)) {
            $offeringGoal = $investment->getOffering()->getFundingGoal();
            // The pricePerShare in the investment has already been resolved in InvestmentAssembler.php:readDTO
            // Based on either the offering (if it exists) or the asset (if not set in offering)
            $numberOfShares = (int) round(
                $offeringGoal / $investment->getPricePerShare(),
            );
        }

        $sharesAvailable =
            $numberOfShares - $investment->getOffering()->getSharesSold();
        $investmentValue = $investment->getInvestmentValue();

        if (
            !(
                $investment->getUser()->getLifecycleStatus()
                === \App\Entity\Lifecycle\UserLifecycle::STATE_APPROVED
                or $investment->getUser()->getLifecycleStatus()
                === \App\Entity\Lifecycle\UserLifecycle::STATE_REGISTRATION_COMPLETE
            )
        ) {
            throw new \App\Exception\UserNotApprovedException(
                'User with id: '
                . $investment->getUser()->getId()
                . ' is not approved/registration complete',
            );
        }
        if ($investment->getNumberOfShares() > $sharesAvailable) {
            throw new \App\Exception\SharesNotAvailableException(
                'There is only: '
                    . $sharesAvailable
                    . ' shares available in offering id: '
                    . $investment->getOffering()->getId(),
            );
        }

        // if ($this->isValidInvestmentValue($investment, $this->getShareOffset($investment))) {
        //     if (!$this->isInvestmentStampDutyExempt($investment)) {
        //         $investmentValue += $this->calculateStampDuty($investmentValue, $investment->getAssetId());
        //     }

        //     $asset = $investment->getOffering()->getAsset();
        //     $user = $investment->getUser();
        //     $authorId = $user->getMangoPayUserId();
        //     $debitedWalletId = $user->getMangoPayWalletId();
        //     $creditedWalletId = $asset->getMangoPayWalletId();
        //     $tag = '';
        //     if ($asset->getName()) {
        //         $tag = "AstName:" . $asset->getName();
        //     }
        //     if ($asset->getCompanyNumber()) {
        //         $tag = $tag . ";AstCode:" . $asset->getCompanyNumber();
        //     }
        //     $mpTag = $tag . ';Type:Investment';

        //     $transfer = $this->mangopayService->createGenericTransfer($authorId, $debitedWalletId, $creditedWalletId, $investmentValue, 0, $mpTag);

        //     if ($transfer->Status == "FAILED") {
        //         if ($transfer->ResultCode == "001001") {
        //             throw new \App\Exception\InsufficientBalanceException("The user e-wallet does not contain enough funds to process the transaction");
        //         }

        //         throw new \App\Exception\MangopayServiceException("The was an issue processing this request");
        //     }

        //     if ($transfer->Status == "SUCCEEDED" or $transfer->Status == "CREATED") {
        //         $investment->setTransactionId($transfer->Id);
        //         $this->investmentRepository->save($investment);
        //         $this->entityManager->flush();
        //         $this->sendInvestmentCreatedMail($investment);
        //         return $investment;
        //     }
        // }
        $this->investmentRepository->save($investment);
        $this->entityManager->flush();
        return $investment;
    }

    public function updateInvestment(
        int $investmentId,
        InvestmentDTO $investmentDTO,
    ): ?Investment {
        $requestedInv = $this->investmentRepository->find($investmentId);

        if ($requestedInv) {
            $investment = $this->investmentAssembler->updateInvestment(
                $investmentDTO,
                $requestedInv,
            );
            $this->investmentRepository->save($investment);
            $this->entityManager->flush();

            return $investment;
        }

        return null;
    }

    public function addDocument(
        int $investmentId,
        DocumentDTO $documentDTO,
    ): ?InvestmentDocuments {
        $requestedInvestment = $this->investmentRepository->find($investmentId);

        if ($requestedInvestment) {
            $document = $this->documentAssembler->createDocument($documentDTO);
            $mimeType = Helper::getFileMimeType(
                (string) $document->getDocumentContent(),
            );

            /** @var User $user */
            $user = $this->security->getUser();
            $investmentDocument = new InvestmentDocuments();
            $investmentDocument->setCreatedById($user->getId());
            $investmentDocument->setDocument($document);

            if ($mimeType) {
                $investmentDocument->getDocument()->setType($mimeType);
            }

            $saveDocument = $this->documentManager->saveInFileStore(
                $investmentDocument->getDocument(),
                'private',
                'investment/' . $requestedInvestment->getId(),
            );

            //if document is saved in file store, persist to database
            if ($saveDocument) {
                $this->investmentDocumentRepository->save($investmentDocument);
                $this->entityManager->flush();

                return $investmentDocument;
            }
        }

        return null;
    }

    public function updateDocument(
        int $investmentId,
        int $investmentDocId,
        DocumentDTO $documentDTO,
    ): ?InvestmentDocuments {
        $requestedDocument = $this->investmentDocumentRepository->findByInvestmentIdAndDocId(
            $investmentId,
            $investmentDocId,
        );

        if ($requestedDocument) {
            $document = $this->documentAssembler->updateDocument(
                $requestedDocument->getDocument(),
                $documentDTO,
            );
            $mimeType = Helper::getFileMimeType(
                (string) $document->getDocumentContent(),
            );

            $requestedDocument->setDocument($document);

            if ($mimeType) {
                $requestedDocument->getDocument()->setType($mimeType);
            }

            if (!$documentDTO->getDocumentContent()) {
                $this->documentManager->getFileContent(
                    $requestedDocument->getDocument(),
                );
            }

            //update document in file store
            $updatedDocument = $this->documentManager->saveInFileStore(
                $requestedDocument->getDocument(),
                'private',
                'investment/' . $investmentId,
            );

            //if document is updated in file store, persist changes to database
            if ($updatedDocument) {
                $this->investmentDocumentRepository->save($requestedDocument);
                $this->entityManager->flush();

                return $requestedDocument;
            }
        }

        return null;
    }

    public function deleteDocument(int $investmentId, int $investmentDocId): ?bool
    {
        $requestedDocument = $this->investmentDocumentRepository->findByInvestmentIdAndDocId(
            $investmentId,
            $investmentDocId,
        );

        if ($requestedDocument) {
            //delete document from S3
            if ($requestedDocument->getDocument()->getDocumentUrl()) {
                $this->documentManager->deleteDocument(
                    $requestedDocument->getDocument(),
                );
            }
            //delete document from db
            $this->investmentDocumentRepository->remove($requestedDocument);
            $this->entityManager->flush();

            return true;
        }

        return null;
    }

    public function getDocument(int $investmentId, int $invDocId): ?InvestmentDocuments
    {
        $document = $this->investmentDocumentRepository->findByInvestmentIdAndDocumentId(
            $investmentId,
            $invDocId,
        );
        return $document;
    }

    public function getDocuments(int $investmentId): array
    {
        $documents =
            $this->investmentDocumentRepository->findByInvestmentId($investmentId);
        return $documents;
    }

    public function calculateStampDuty(float $investmentValue, ?int $assetId = null)
    {
        if ($assetId == 49) {
            return 0;
        }

        if ($investmentValue < 1000) {
            return 0;
        }

        return ceil($investmentValue / 1000) * 5;
    }

    public function calculateTradeValueStampDuty(Number $tradeValue): Number
    {
        if ($tradeValue >= 1000) {
            return $tradeValue->div(1000)->ceil()->mul(5);
        }
        return new Number(0);
    }

    public function getMangopayWallet(string $walletId): ?\MangoPay\Wallet
    {
        try {
            $wallet = $this->mangopayService->getSingleWallet($walletId);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error(
                'Error fetching mangopay wallet. Error code: '
                    . $e->GetCode()
                    . ' Error Message: '
                    . $e->getMessage()
                    . ' - '
                    . $e->GetErrorDetails(),
            );
            return null;
        } catch (\MangoPay\Libraries\Exception $e) {
            $this->logger->error(
                'Error fetching mangopay wallet. Error message: ' . $e->GetMessage(),
            );
            return null;
        }

        return $wallet;
    }

    public function createMangopayTransfer(
        string $authUserId,
        string $debitedWalletId,
        string $creditedWalletId,
        float $amount,
        float $fee,
        string $metadata = '',
    ): array {
        $state = [];

        try {
            $transfer = $this->mangopayService->createGenericTransfer(
                $authUserId,
                $debitedWalletId,
                $creditedWalletId,
                $amount,
                $fee,
                $metadata,
            );
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->error(
                'Error creating transfer. Error code: '
                    . $e->GetCode()
                    . ' Error Message: '
                    . $e->getMessage()
                    . ' - '
                    . $e->GetErrorDetails(),
            );
        } catch (\MangoPay\Libraries\Exception $e) {
            $this->logger->error(
                'Error creating transfer. Error message: ' . $e->GetMessage(),
            );
        }
        if ($transfer instanceof \MangoPay\Transfer) {
            if ($transfer->Status == 'SUCCEEDED') {
                $state['status'] = true;
                $state['message'] = 'Transfer successful';
            } else {
                $state['status'] = false;
                $state['message'] =
                    'mp code: '
                    . $transfer->ResultCode
                    . ' mp message: '
                    . $transfer->ResultMessage;
            }
        } else {
            $state['status'] = false;
            $state['message'] = 'Unkown problem. Please contact admin';
        }

        return $state;
    }

    /**
     * Returns true if the investment is exempt from stamp duty
     */
    #[\Deprecated('Switch to trade system and use isTradeStampDutyExempt')]
    public function isInvestmentStampDutyExempt(Investment $investment): bool
    {
        if ($investment->getInvestmentValue() < 1000) {
            return true;
        }

        $offering = $investment->getOffering();
        $asset = $offering->getAsset();
        return \in_array($asset->getAdditionalType(), ['development', 'prefunding'])
        || 'prefunding' == $offering->getOfferingType();
    }

    public function isTradeStampDutyExempt(ShareTrade $shareTrade): bool
    {
        $asset = $shareTrade->getBuyOrder()->getAsset();
        // Likely to change in future as we change how assets are categorised or classed
        return \in_array($asset->getAdditionalType(), ['development', 'duty-free'])
        || $shareTrade->getBuyOrder()->getType() == TradeOrderType::Prefunding;
    }

    #[\Deprecated(
        'Switch to trade system, no direct replacement. Use isTradeStampDutyExempt to check for exemption.',
    )]
    public function isOfferingStampDutyExempt(Offering $offering): bool
    {
        $asset = $offering->getAsset();
        return in_array($asset->getAdditionalType(), ['development', 'prefunding'])
        || 'prefunding' == $offering->getOfferingType();
    }

    /**
     * Helper method for determining if an investment is within the offering min and max amount
     */
    public function isValidInvestmentValue(
        Investment $investment,
        int $shareOffset = 0,
    ): bool {
        if ($investment->getOffering() && $investment->getPricePerShare() > 0) {
            $offering = $investment->getOffering();
            $numberOfShares = $investment->getNumberOfShares() + $shareOffset;
            $minShares = round(
                (float) $offering->getMinCommitUser() / $investment->getPricePerShare(),
            );
            $maxShares = round(
                (float) $offering->getMaxCommitUser() / $investment->getPricePerShare(),
            );

            if ($numberOfShares < $minShares) {
                throw new \App\Exception\MinCommitViolationException(
                    'The investment value £'
                        . $investment->getInvestmentValue()
                        . ' is below the minimum commitment of £'
                        . $offering->getMinCommitUser()
                        . ' for offering '
                        . $offering->getId(),
                );
            }
            if ($maxShares > 0 && $numberOfShares > $maxShares) {
                throw new \App\Exception\MaxCommitViolationException(
                    'The investment value £'
                        . $investment->getInvestmentValue()
                        . ' is below the minimum commitment of £'
                        . $offering->getMaxCommitUser()
                        . ' for offering '
                        . $offering->getId(),
                );
            }
            return true;
        }
        return false;
    }

    public function getShareOffset(Investment $investment): int
    {
        /**
         * Handle prefunding split investments
         * Allow prefunding investments to safely pass isValidInvestmentValue()
         * For regular retail (normal) investments, the offset will be 0
         */
        $sharesToKeepField = $investment->getAddedField('sharesToKeep');
        $prefundingIdField = $investment->getAddedField('prefundingId');
        if ($sharesToKeepField && 'prefunding' == $investment->getType()) {
            // liquidation portions should mention the retention intent
            $shareOffset = $sharesToKeepField->getFieldValue();
        }
        if ($prefundingIdField && 'prefunding' != $investment->getType()) {
            // retention portions should reference the liquidation investment
            $prefundingInvestment = $this->investmentRepository->find(
                $prefundingIdField->getFieldValue(),
            );
            $shareOffset = $prefundingInvestment->getNumberOfShares();
        }
        return $shareOffset ?? 0;
    }

    public function sendInvestmentCreatedMail(Investment $investment): bool
    {
        try {
            $user = $investment->getUser();
            $offering = $investment->getOffering();
            $asset = $offering->getAsset();
            $status = $this->mailService->sendMail(
                $user,
                MailerService::TYPE_INVESTMENT_NEW,
                [
                    'investment' => $investment,
                    'offering' => $offering,
                    'asset' => $asset,
                ],
            );

            if ($status == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    public function sendInvestmentSettledEmail(Investment $investment): bool
    {
        // set value for number of shares if not set (email template uses number of shares value)
        if (empty($investment->getNumberOfShares())) {
            $investment->setNumberOfShares($investment->getShareAmount());
        }

        try {
            $user = $investment->getUser();
            $offering = $investment->getOffering();
            $asset = $offering->getAsset();
            $status = $this->mailService->sendMail(
                $user,
                MailerService::TYPE_INVESTMENT_SETTLED,
                [
                    'investment' => $investment,
                    'asset' => $asset,
                ],
            );

            if ($status == 1) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * Investments should all belong to the same asset
     * @param Investment[] $investments
     */
    public function assetInvestmentsToShareholding(array $investments): array
    {
        // $expectedStructure = [
        //     'userId' => [
        //         'initial' => 0,
        //         'divested' => 0,
        //         'investments' => [],
        //     ]
        // ];
        $shareholdingSummary = [];
        foreach ($investments as $investment) {
            $userId = $investment->getUser()->getId();
            if (!array_key_exists($userId, $shareholdingSummary)) {
                $shareholdingSummary[$userId] = [
                    'initial' => 0,
                    'divested' => 0,
                    'investments' => [],
                ];
            }
            $shares = $investment->getNumberOfShares() ?? $investment->getShareAmount();
            $shareholdingSummary[$userId]['initial'] += $shares;
            $shareholdingSummary[$userId]['divested'] +=
                $investment->getDivestedShares();
            $shareholdingSummary[$userId]['investments'][] = $investment;
        }
        return $shareholdingSummary;
    }

    public function processPaymentOutcome(
        Investment $investment,
        bool $success,
        string $type = 'normal',
    ): Investment {
        $investmentStatus = $success
            ? InvestmentLifecycle::STATE_APPROVED
            : InvestmentLifecycle::STATE_WITHDRAWN;
        $transactionStatus = $success
            ? TransactionStatus::Succeeded
            : TransactionStatus::Failed;
        $transaction = $this->entityManager
            ->getRepository(Transaction::class)
            ->findOneBy([
                'external_id' => $investment->getTransactionId(),
                'inv_id' => $investment->getId(),
            ]);
        // Only update if necessary
        if ($investment->getLifecycleStatus() == InvestmentLifecycle::STATE_OPEN) {
            $investment->setLifecycleStatus($investmentStatus);
        }
        if ($transaction?->getPaymentStatus() == TransactionStatus::Created) {
            $transaction->setPaymentStatus($transactionStatus);
        }
        // Also update a prefunding split investment
        // Requires the current investment to be the retention portion
        if (
            $type == 'prefunding'
            && $investment->getType() == 'normal'
            && $investment->getAddedField('prefundingId')
        ) {
            $counterpartId = $investment
                ->getAddedField('prefundingId')
                ->getFieldValue();
            $this->logger->debug(
                "Looking for prefunding investment #{$counterpartId} to update",
            );
            $counterpart = $this->investmentRepository->find($counterpartId);
            if (
                $counterpart?->getLifecycleStatus() == InvestmentLifecycle::STATE_OPEN
            ) {
                $this->logger->debug(
                    "Prefunding investment counterpart #{$counterpartId} found and updating",
                );
                $counterpart->setLifecycleStatus($investmentStatus);
                // Attach the transaction to counterpart as well
                $counterpart->setTransactionId($transaction->getReferenceId());
            }
        }
        return $investment;
    }
}
