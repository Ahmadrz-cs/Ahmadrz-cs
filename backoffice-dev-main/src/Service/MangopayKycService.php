<?php

namespace App\Service;

use App\Entity\Enum\MangopayBlockActionCode;
use App\Entity\KycReport;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Service\KycProviderInterface;
use MangoPay\KycDocument;
use MangoPay\KycDocumentStatus;
use MangoPay\KycDocumentType;
use MangoPay\KycPage;
use MangoPay\MangoPayApi;
use MangoPay\UserBlockStatus;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class MangopayKycService implements KycProviderInterface
{
    public const PROVIDER_NAME = 'mangopay';
    public const CHECK_TYPE_REGULATORY_STATUS = 'REGULATORY_STATUS';

    /**
     * Not all methods in this class are implemented.
     * Once persistence has been added to a kyc entity the remaining methods will be implemented.
     */

    public function __construct(
        private LoggerInterface $logger,
        private MangoPayApi $mangopayApi,
        private DocumentService $documentService,
    ) {}

    public function isUserKycReady(User $user): bool
    {
        throw new \RuntimeException('Not yet implemented');
        return false;
    }

    public function isCompanyKycReady(User $user): bool
    {
        throw new \RuntimeException('Not yet implemented');
        return false;
    }

    public function createUser(User $user): KycReport
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function createCompany(User $user): KycReport
    {
        throw new \RuntimeException('Not yet implemented');
    }

    public function submitDocument(UserDocument $userDocument): KycReport
    {
        $kycDocument = $this->createKycDocument(
            $userDocument->getUser()->getMangoPayUserId(),
            KycDocumentType::IdentityProof,
        );

        $this->addPageToKycDocument($kycDocument->Id, $userDocument);

        $kycDocument = $this->submitKycDocument($kycDocument);

        $refusalReason = null;
        if ($kycDocument->RefusedReasonType) {
            $refusalReason = "[{$kycDocument->RefusedReasonType}] {$kycDocument->RefusedReasonMessage}";
        }

        $report = new KycReport(
            $userDocument->getUser(),
            self::PROVIDER_NAME,
            $kycDocument->Id,
            $kycDocument->Type,
            $kycDocument->Status,
            'N/A',
            false,
            note: $refusalReason,
        );
        return $report;
    }

    public function viewReport(
        User $user,
        string $reference,
        ?string $notes = null,
    ): KycReport {
        // $this->logger->debug('Retrieving user kyc doc');
        // $kycDoc = $this->mangopayApi->Users->GetKycDocument($user->getMangoPayUserId(), $reference);
        if ($user->getMangoPayUserId() != $reference) {
            $this->logger->warning('Mismatched mangopay user id and reference when creating KycReport', [
                'mangopay user id' => $user->getMangoPayUserId(),
                'reference' => $reference,
            ]);
        }
        $kycUser = $this->mangopayApi->Users->GetSca($user->getMangoPayUserId());
        // $kycUser->KYCLevel = 'LIGHT'; // for debug testing
        $report = new KycReport(
            $user,
            self::PROVIDER_NAME,
            $reference,
            $kycUser->PersonType,
            $kycUser->KYCLevel,
            \MangoPay\KycLevel::Regular == $kycUser->KYCLevel ? '1' : '0',
            \MangoPay\KycLevel::Regular == $kycUser->KYCLevel,
            note: $notes,
        );
        return $report;
    }

    public function getAllKYCDocuments(
        ?\MangoPay\Pagination $pagination = null,
        ?\MangoPay\Sorting $sorting = null,
        ?\MangoPay\FilterKycDocuments $filter = null,
    ): array {
        return $this->mangopayApi->KycDocuments->GetAll($pagination, $sorting, $filter);
    }

    public function getAllUserKYCDocuments(
        string $userId,
        ?\MangoPay\Pagination $pagination = null,
        ?\MangoPay\Sorting $sorting = null,
        ?\MangoPay\FilterKycDocuments $filter = null,
    ): array {
        return $this->mangopayApi->Users->GetKycDocuments(
            $userId,
            $pagination,
            $sorting,
            $filter,
        );
    }

    public function createKycDocument(
        string $userId,
        string $type = KycDocumentType::IdentityProof,
        ?string $tag = null,
    ): KycDocument {
        $this->logger->debug('Create draft Mangopay KYC doc', [
            'userId' => $userId,
            'type' => $type,
        ]);
        if (!in_array($type, $this->getSupportedKycTypes())) {
            throw new \InvalidArgumentException(
                "argument 'type' must be one of KycDocumentType class constants",
            );
        }
        $kycDocument = new KycDocument();
        $kycDocument->Type = $type;
        $kycDocument->UserId = $userId;
        $kycDocument->Tag = $tag;

        return $this->mangopayApi->Users->CreateKycDocument($userId, $kycDocument);
    }

    public function addPageToKycDocument(
        string $kycDocumentId,
        UserDocument $userDocument,
    ): void {
        $page = new KycPage();
        $page->File = base64_encode($this->documentService->read(
            $userDocument->getDocument()->getDocumentUrl(),
            'private',
        ));
        $this->logger->debug('Add page to KycDocument', [
            'kycDocumentId' => $kycDocumentId,
        ]);
        $this->mangopayApi->Users->CreateKycPage(
            $userDocument->getUser()->getMangoPayUserId(),
            $kycDocumentId,
            $page,
        );
    }

    public function submitKycDocument(KycDocument $kycDocument): KycDocument
    {
        $kycDocument->Status = KycDocumentStatus::ValidationAsked;
        $this->logger->debug('Submitting KycDocument', [
            'mangopayUserId' => $kycDocument->UserId,
            'kycDocumentId' => $kycDocument->Id,
            'status' => $kycDocument->Status,
        ]);
        return $this->mangopayApi->Users->UpdateKycDocument(
            $kycDocument->UserId,
            $kycDocument,
        );
    }

    public function createUserRegulatoryReport(
        User $user,
        string $event,
        UserBlockStatus $userBlockStatus,
    ): KycReport {
        $outcome =
            empty($userBlockStatus->ActionCode)
            && !$userBlockStatus->ScopeBlocked->Inflows
            && !$userBlockStatus->ScopeBlocked->Outflows;

        // For dev testing block statuses
        // $userBlockStatus->ActionCode = MangopayBlockActionCode::NewIdDocRequired->value;
        // $outcome = false;

        $regulatoryReport = new KycReport(
            subject: $user,
            providerName: self::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: self::CHECK_TYPE_REGULATORY_STATUS,
            result: json_encode($userBlockStatus->ScopeBlocked),
            score: $userBlockStatus->ActionCode,
            verified: $outcome,
            note: $event,
        );
        return $regulatoryReport;
    }

    private function getSupportedKycTypes(): array
    {
        $reflectionClass = new ReflectionClass(KycDocumentType::class);
        return $reflectionClass->getConstants();
    }
}
