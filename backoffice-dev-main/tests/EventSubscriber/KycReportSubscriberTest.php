<?php

declare(strict_types=1);

namespace App\Tests\EventSubscriber;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReport;
use App\Entity\KycReview;
use App\Entity\User;
use App\Event\Kyc\KycReportCreatedEvent;
use App\EventSubscriber\KycReportSubscriber;
use App\Repository\HoldingRepository;
use App\Service\KycReviewService;
use App\Service\MangopayKycService;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\PersonType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class KycReportSubscriberTest extends KernelTestCase
{
    private KycReportSubscriber $service;
    private KycReviewService|MockObject $kycReviewServiceMock;
    private HoldingRepository|MockObject $holdingRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->kycReviewServiceMock = $this->createMock(KycReviewService::class);
        static::getContainer()->set(
            KycReviewService::class,
            $this->kycReviewServiceMock,
        );

        $this->holdingRepositoryMock = $this->createMock(HoldingRepository::class);
        static::getContainer()->set(
            HoldingRepository::class,
            $this->holdingRepositoryMock,
        );

        static::getContainer()->set(
            EntityManagerInterface::class,
            $this->createStub(EntityManagerInterface::class),
        );

        $this->service = static::getContainer()->get(KycReportSubscriber::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('processKycReportProvider')]
    public function testProcessKycReportCreation(
        bool $processingExpected,
        bool $notificationExpected,
        KycReport $kycReport,
        bool $isShareholder = false,
        bool $existingReview = false,
        KycReviewStatus $reviewStatus = KycReviewStatus::PendingSubjectAction,
    ): void {
        // processingExpected is for calls to handleMangopayIdRenewal
        if ($processingExpected) {
            $review = new KycReview(KycReviewType::Recurring, $kycReport->subject);
            $review->setIdentityReview(true);
            $review->setStatus($reviewStatus);
            // If existingReview is true, then the review will have an id (usually auto-generated on persist to database)
            if ($existingReview) {
                $review = EntityIdTestUtil::setEntityId($review, 4);
            }
        } else {
            $review = null;
        }
        // If processing is expected, will always call getShareHoldings as a KycReview is returned
        // isShareholder determines whether a non-empty list of shareholdings is returned
        $this->holdingRepositoryMock
            ->expects($this->exactly((int) $processingExpected))
            ->method('getShareHoldings')
            ->with([
                'currentHolding' => 1,
                'capitalRepayments' => false,
                'userId' => $kycReport->subject->getId(),
            ])
            ->willReturn($isShareholder ? [1] : []);
        $this->kycReviewServiceMock
            ->expects($this->exactly((int) $processingExpected))
            ->method('handleMangopayIdRenewal')
            ->with($kycReport)
            ->willReturn($review);

        // If we expect a notification
        // canSendNotification is called is a new KycReview is generated and they are a shareholder
        // notification should only be sent if the review status is PendingSubjectAction
        $this->kycReviewServiceMock
            ->expects($this->exactly((int) (
                $processingExpected
                && $isShareholder
                && !$existingReview
            )))
            ->method('canSendNotification')
            ->with($review)
            ->willReturn($reviewStatus == KycReviewStatus::PendingSubjectAction);
        $this->kycReviewServiceMock
            ->expects($this->exactly((int) $notificationExpected))
            ->method('sendIdConfirmationNotification')
            ->with($review);
        $event = new KycReportCreatedEvent($kycReport);
        $this->service->processKycReportCreation($event);
    }

    public static function processKycReportProvider(): \Generator
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 51714);
        $user->setMangoPayUserId('id_doc_ewnewal_test_' . bin2hex(random_bytes(8)));

        // Note that the verified status is "true"
        // The execution is based on the type of report not the outcome
        // The outcome decides on subsequent execution, e.g. handleMangopayIdRenewal
        // But we're not testing that behaviour in this test
        $mangopayRegStatusReport = new KycReport(
            subject: $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            result: '',
            score: '',
            verified: true,
        );
        $mangopayKycLightNaturalReport = new KycReport(
            subject: $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Natural,
            result: '',
            score: '',
            verified: true,
        );
        $mangopayKycLightLegalReport = new KycReport(
            subject: $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Legal,
            result: '',
            score: '',
            verified: true,
        );
        $otherRegStatusReport = new KycReport(
            subject: $user,
            providerName: 'diffKyc',
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            result: '',
            score: '',
            verified: true,
        );
        $mangopayRegStatusReport = EntityIdTestUtil::setEntityId(
            $mangopayRegStatusReport,
            8824,
        );
        $otherRegStatusReport = EntityIdTestUtil::setEntityId(
            $otherRegStatusReport,
            8826,
        );
        $mangopayKycLightNaturalReport = EntityIdTestUtil::setEntityId(
            $mangopayKycLightNaturalReport,
            8828,
        );
        $mangopayKycLightLegalReport = EntityIdTestUtil::setEntityId(
            $mangopayKycLightLegalReport,
            8848,
        );

        yield 'Process Mangopay regulatory status report' => [
            true,
            true,
            $mangopayRegStatusReport,
            true,
            false,
        ];
        yield 'Process Mangopay kyc light natural report' => [
            true,
            true,
            $mangopayKycLightNaturalReport,
            true,
            false,
        ];
        yield 'Process Mangopay kyc legal natural report' => [
            true,
            true,
            $mangopayKycLightLegalReport,
            true,
            false,
        ];
        yield 'Skip other regulatory status report' => [
            false,
            false,
            $otherRegStatusReport,
        ];
        yield 'Process Mangopay regulatory status report - existing review' => [
            true,
            false,
            $mangopayRegStatusReport,
            true,
            true,
        ];
        yield 'Process Mangopay regulatory status report - not shareholder' => [
            true,
            false,
            $mangopayRegStatusReport,
            false,
            false,
        ];
        yield 'Process Mangopay regulatory status report - review not in pending subject action state' =>
            [
                true,
                false,
                $mangopayRegStatusReport,
                true,
                false,
                KycReviewStatus::Ready,
            ];
    }
}
