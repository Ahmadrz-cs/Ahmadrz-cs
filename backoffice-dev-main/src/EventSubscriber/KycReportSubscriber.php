<?php

namespace App\EventSubscriber;

use App\Event\Kyc\KycReportCreatedEvent;
use App\Repository\HoldingRepository;
use App\Service\KycReviewService;
use App\Service\MangopayKycService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class KycReportSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private KycReviewService $kycReviewService,
        private HoldingRepository $holdingRepository,
        private string $autoNotifyIdVerification,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KycReportCreatedEvent::class => 'processKycReportCreation',
        ];
    }

    public function processKycReportCreation(KycReportCreatedEvent $event)
    {
        $kycReport = $event->getKycReport();
        $this->logger->debug('Processing KycReport for follow-up actions', [
            'id' => $kycReport->id,
            'provider' => $kycReport->providerName,
            'type' => $kycReport->checkType,
        ]);
        if (
            $kycReport->providerName == MangopayKycService::PROVIDER_NAME
            && in_array(
                $kycReport->checkType,
                KycReviewService::ID_DOC_RENEWAL_CHECK_TYPES,
            )
        ) {
            $kycReview = $this->kycReviewService->handleMangopayIdRenewal($kycReport);
            if ($kycReview) {
                $this->entityManager->persist($kycReview);
                // Active shareholders/investors should be automatically notified if enabled
                if ($this->autoNotifyIdVerification) {
                    $userShareholdings = $this->holdingRepository->getShareHoldings([
                        'currentHolding' => 1,
                        'capitalRepayments' => false,
                        'userId' => $kycReview->getSubject()->getId(),
                    ]);
                    // Only send notification if a new KycReview was created (i.e. no id)
                    if (
                        !empty($userShareholdings)
                        && $kycReview->getId() === null
                        && $this->kycReviewService->canSendNotification($kycReview)
                    ) {
                        $this->kycReviewService->sendIdConfirmationNotification(
                            $kycReview,
                        );
                    }
                }
            }
        }
        $this->entityManager->flush();
    }
}
