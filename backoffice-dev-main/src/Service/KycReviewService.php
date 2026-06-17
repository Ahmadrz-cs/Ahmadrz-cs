<?php

namespace App\Service;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\Enum\MangopayBlockActionCode;
use App\Entity\KycReport;
use App\Entity\KycReview;
use App\Entity\User;
use App\Repository\KycReviewRepository;
use MangoPay\PersonType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Support service for KYC actions that don't involve an external provider
 */
class KycReviewService
{
    public const array ID_DOC_RENEWAL_CHECK_TYPES = [
        MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
        PersonType::Natural,
        PersonType::Legal,
    ];

    public const array ID_DOC_RENEWAL_ACTION_CODES = [
        MangopayBlockActionCode::NewIdDocRequired->value,
        MangopayBlockActionCode::KycVerificationRequired->value,
    ];

    public const array KYC_REVIEW_PRESETS = [
        'mangopay_id_doc_renewal' => [
            'type' => KycReviewType::Recurring,
            'actions' => ['identityReview', 'kycProviderReview'],
            'notes' => 'Mangopay user identity document expired',
            'status' => KycReviewStatus::PendingSubjectAction,
        ],
    ];

    public const array CONFIGURABLE_ACTIONS = [
        'identityReview',
        'addressReview',
        'countryReview',
        'kycProviderReview',
        'dueDiligenceLevelReview',
        'kycSurveyReview',
        'transactionsReview',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private Security $security,
        private KycReviewRepository $kycReviewRepository,
        private NotificationService $notificationService,
    ) {}

    public function createKycReview(
        KycReviewType $reviewType,
        User $subject,
        ?User $reviewedBy = null,
        ?string $notes = null,
        bool $isRecord = true,
    ): KycReview {
        $kycReview = new KycReview(
            reviewType: $reviewType,
            subject: $subject,
            reviewedBy: $reviewedBy,
        );
        $kycReview = match ($reviewType) {
            KycReviewType::Onboarding => $this->configureOnboardingReview($kycReview),
            KycReviewType::Vip => $this->configureVipReview($kycReview),
            default => $kycReview,
        };
        $kycReview->setNotes($notes);
        /**
         * If the review is intended as record for a completed review
         * rather than the beginning of an recurring process
         * Set status to completed
         */
        if ($isRecord) {
            $kycReview->setStatus(KycReviewStatus::Completed);
            $kycReview->setCompletedAt(new \DateTime());
        }
        return $kycReview;
    }

    public function applyReviewPreset(KycReview $kycReview, string $preset): KycReview
    {
        if (in_array($preset, array_keys(self::KYC_REVIEW_PRESETS))) {
            $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
                ->disableExceptionOnInvalidPropertyPath()
                ->getPropertyAccessor();
            // All actions are boolean, so set true/false based on whether the action is defined in the preset
            foreach (self::CONFIGURABLE_ACTIONS as $action) {
                $propertyAccessor->setValue(
                    $kycReview,
                    $action,
                    in_array($action, self::KYC_REVIEW_PRESETS[$preset]['actions']),
                );
            }
            // Set notes (description) if available and not already set
            if (
                !empty(self::KYC_REVIEW_PRESETS[$preset]['notes'])
                && empty($kycReview->getNotes())
            ) {
                $kycReview->setNotes(self::KYC_REVIEW_PRESETS[$preset]['notes']);
            }
            // Set the status if available
            // This will override existing status, so only use for initial review setup
            if (
                !empty(self::KYC_REVIEW_PRESETS[$preset]['status'])
                && self::KYC_REVIEW_PRESETS[$preset]['status']
                    instanceof KycReviewStatus
            ) {
                $kycReview->setStatus(self::KYC_REVIEW_PRESETS[$preset]['status']);
            }
            if (
                !empty(self::KYC_REVIEW_PRESETS[$preset]['type'])
                && self::KYC_REVIEW_PRESETS[$preset]['type'] instanceof KycReviewType
            ) {
                $kycReview->setReviewType(self::KYC_REVIEW_PRESETS[$preset]['type']);
            }
        }
        return $kycReview;
    }

    public function handleMangopayIdRenewal(KycReport $kycReport): ?KycReview
    {
        /**
         * Find any existing open reviews for Mangopay id doc renewals
         * If report says user is verified, resolve that review
         * If report says user is not verified AND the issue is specifically about an ID doc, then open a new review
         */
        $openIdRenewalReviews = $this->kycReviewRepository->findOpenReviews(
            $kycReport->subject,
            KycReviewType::Recurring,
            [
                'identityReview',
                'kycProviderReview',
            ],
        );
        // $kycReview = array_first($openIdRenewalReviews); // Needs PHP 8.5
        $kycReview = \count($openIdRenewalReviews) > 0
            ? $openIdRenewalReviews[0]
            : null;
        $this->logger->debug('Deciding action for KycReport Mangopay Id doc renewal', [
            'reportId' => $kycReport?->id,
            'reviewId' => $kycReview?->getId(),
        ]);
        if ($kycReport->verified) {
            if ($kycReview) {
                // If there are multiple (duplicate) open reviews, we'll only resolve the first (oldest) one
                $kycReview->setStatus(KycReviewStatus::Completed);
                $kycReview->setDecision(true);
                $kycReview->setCompletedAt(new \DateTime());
                $this->logger->info('Resolving KycReview for Mangopay Id Doc renewal');
            } else {
                $this->logger->debug(
                    'KYC passed but no open kyc reviews. No action needed.',
                );
            }
        } else {
            if ($kycReview === null && $this->isIdDocRenewal($kycReport)) {
                $kycReview = new KycReview(
                    KycReviewType::Recurring,
                    $kycReport->subject,
                );
                $kycReview = $this->applyReviewPreset(
                    $kycReview,
                    'mangopay_id_doc_renewal',
                );
                $this->logger->info('Opening KycReview for Mangopay Id Doc renewal');
            } else {
                $this->logger->debug(
                    'KycReview already exists or not doc renewal. No action needed.',
                );
            }
        }
        return $kycReview;
    }

    public function canSendNotification(KycReview $kycReview): bool
    {
        // Only supporting id confirmation kyc reviews for now
        if (
            $kycReview->getStatus() == KycReviewStatus::PendingSubjectAction
            && $kycReview->getReviewType() == KycReviewType::Recurring
            && $kycReview->isIdentityReview()
        ) {
            return true;
        }
        return false;
    }

    public function sendIdConfirmationNotification(KycReview $kycReview): void
    {
        $this->notificationService->notifyUserByEmail(
            recipient: $kycReview->getSubject(),
            subject: 'Confirm your personal details',
            content: "As a regulated financial institution, we need to ensure information we hold on you is up to date and accurate.
                \nPlease review and confirm your personal details from your profile.
                \nTo get started, visit our website, log in to your account, and follow the prompts.
                \nCertain functionality involving your wallet (e.g. investments, dividends, withdrawals) may be restricted until this is complete.",
            context: [
                'title' => 'Confirm Your Personal Details',
            ],
        );
    }

    public function isIdDocRenewal(KycReport $kycReport): bool
    {
        if (
            $kycReport->providerName == MangopayKycService::PROVIDER_NAME
            && $kycReport->checkType == MangopayKycService::CHECK_TYPE_REGULATORY_STATUS
            && in_array($kycReport->score, self::ID_DOC_RENEWAL_ACTION_CODES)
        ) {
            return true;
        }
        if (
            $kycReport->providerName == MangopayKycService::PROVIDER_NAME
            && in_array($kycReport->checkType, [PersonType::Natural, PersonType::Legal])
            && $kycReport->result == 'LIGHT'
        ) {
            return true;
        }
        $this->logger->debug('Not id doc renewal');
        return false;
    }

    private function configureOnboardingReview(KycReview $kycReview): KycReview
    {
        $kycReview->setIdentityReview(true);
        $kycReview->setAddressReview(true);
        $kycReview->setCountryReview(true);
        $kycReview->setKycProviderReview(true);
        $kycReview->setDueDiligenceLevelReview(true);
        return $kycReview;
    }

    private function configureVipReview(KycReview $kycReview): KycReview
    {
        $kycReview->setTransactionsReview(true);
        return $kycReview;
    }
}
