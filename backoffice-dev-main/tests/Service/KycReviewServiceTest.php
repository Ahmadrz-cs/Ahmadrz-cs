<?php

namespace App\Tests\Service;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\Enum\MangopayBlockActionCode;
use App\Entity\KycReport;
use App\Entity\KycReview;
use App\Entity\User;
use App\Repository\KycReviewRepository;
use App\Service\ContegoKycService;
use App\Service\KycReviewService;
use App\Service\MangopayKycService;
use App\Service\NotificationService;
use App\Test\Util\EntityIdTestUtil;
use MangoPay\PersonType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class KycReviewServiceTest extends KernelTestCase
{
    private KycReviewService $service;
    private KycReviewRepository|MockObject $kycReviewRepositoryMock;
    private NotificationService|MockObject $notificationServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->kycReviewRepositoryMock = $this->createMock(KycReviewRepository::class);
        static::getContainer()->set(
            KycReviewRepository::class,
            $this->kycReviewRepositoryMock,
        );

        $this->notificationServiceMock = $this->createMock(NotificationService::class);
        static::getContainer()->set(
            NotificationService::class,
            $this->notificationServiceMock,
        );

        $this->service = static::getContainer()->get(KycReviewService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kycReviewCreationProvider')]
    public function testCreateKycReview(
        KycReviewType $reviewType,
        User $subject,
        ?User $reviewedBy = null,
        ?string $notes = null,
        bool $isRecord = true,
    ): void {
        $currentTime = time();
        $actual = $this->service->createKycReview(
            $reviewType,
            $subject,
            $reviewedBy,
            $notes,
            $isRecord,
        );
        $this->assertSame($subject, $actual->getSubject());
        $this->assertSame($reviewType, $actual->getReviewType());
        $this->assertSame($notes, $actual->getNotes());
        if (KycReviewType::Onboarding == $reviewType) {
            $this->assertTrue($actual->isIdentityReview());
            $this->assertTrue($actual->isAddressReview());
            $this->assertTrue($actual->isCountryReview());
            $this->assertTrue($actual->isKycProviderReview());
            $this->assertTrue($actual->isDueDiligenceLevelReview());
            $this->assertFalse($actual->isKycSurveyReview());
            $this->assertFalse($actual->isTransactionsReview());
        }
        if (KycReviewType::Vip == $reviewType) {
            $this->assertFalse($actual->isIdentityReview());
            $this->assertFalse($actual->isAddressReview());
            $this->assertFalse($actual->isCountryReview());
            $this->assertFalse($actual->isKycProviderReview());
            $this->assertFalse($actual->isDueDiligenceLevelReview());
            $this->assertFalse($actual->isKycSurveyReview());
            $this->assertTrue($actual->isTransactionsReview());
        }

        if (is_null($reviewedBy)) {
            $this->assertNull($actual->getReviewedBy());
        } else {
            $this->assertSame($reviewedBy, $actual->getReviewedBy());
        }

        if ($isRecord) {
            $this->assertSame(KycReviewStatus::Completed, $actual->getStatus());
            $this->assertNotNull($actual->getCompletedAt());
            $this->assertGreaterThanOrEqual(
                $currentTime,
                $actual->getCompletedAt()->getTimestamp(),
            );
        } else {
            $this->assertSame(KycReviewStatus::Open, $actual->getStatus());
            $this->assertNull($actual->getCompletedAt());
        }
    }

    public static function kycReviewCreationProvider(): \Generator
    {
        $subject = EntityIdTestUtil::setEntityId(new User(), 1245);
        $reviewer = EntityIdTestUtil::setEntityId(new User(), 16);
        $randomString = bin2hex(random_bytes(8));

        yield 'Onboarding review type' => [
            KycReviewType::Onboarding,
            $subject,
            $reviewer,
        ];
        yield 'Vip review type' => [KycReviewType::Vip, $subject, $reviewer];
        yield 'Other review type' => [
            KycReviewType::Adhoc,
            $subject,
            null,
            $randomString,
        ];
        yield 'Machine principal ignore reviewer' => [
            KycReviewType::Adhoc,
            $subject,
            $reviewer,
        ];
        yield 'Review as recurring process' => [
            KycReviewType::Recurring,
            $subject,
            null,
            $randomString,
            false,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kycReviewPresetProvider')]
    public function testApplyReviewPreset(
        KycReview $input,
        string $preset,
        array $expected,
    ): void {
        $this->service->applyReviewPreset($input, $preset);
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();
        foreach ($expected as $field => $expectedValue) {
            $this->assertEquals($expectedValue, $propertyAccessor->getValue(
                $input,
                $field,
            ));
        }
    }

    public static function kycReviewPresetProvider(): \Generator
    {
        $kycReview1 = new KycReview(KycReviewType::Adhoc, new User());
        // All unchanged if preset is not valid
        $invalidPreset = [
            'status' => $kycReview1->getStatus(),
            'notes' => $kycReview1->getNotes(),
            'identityReview' => $kycReview1->isIdentityReview(),
            'addressReview' => $kycReview1->isAddressReview(),
            'countryReview' => $kycReview1->isCountryReview(),
            'kycProviderReview' => $kycReview1->isKycProviderReview(),
            'dueDiligenceLevelReview' => $kycReview1->isDueDiligenceLevelReview(),
            'kycSurveyReview' => $kycReview1->isKycSurveyReview(),
            'transactionsReview' => $kycReview1->isTransactionsReview(),
        ];
        $mangopayIdDocRenewal = [
            'status' => KycReviewStatus::PendingSubjectAction,
            'notes' =>
                KycReviewService::KYC_REVIEW_PRESETS['mangopay_id_doc_renewal']['notes'],
            'identityReview' => true,
            'addressReview' => false,
            'countryReview' => false,
            'kycProviderReview' => true,
            'dueDiligenceLevelReview' => false,
            'kycSurveyReview' => false,
            'transactionsReview' => false,
        ];

        // Overriding behaviour
        $kycReview2 = new KycReview(KycReviewType::Adhoc, new User());
        $differentNote = bin2hex(random_bytes(6));
        $kycReview2->setNotes($differentNote);
        $kycReview2->setStatus(KycReviewStatus::Closed);
        $kycReview2->setIdentityReview(false);
        $kycReview2->setAddressReview(true);
        $kycReview2->setCountryReview(true);
        $kycReview2->setKycProviderReview(false);
        $kycReview2->setDueDiligenceLevelReview(true);
        $kycReview2->setKycSurveyReview(true);
        $kycReview2->setTransactionsReview(true);
        $overrideBehaviour = [
            'status' => KycReviewStatus::PendingSubjectAction, // will be overriden
            'notes' => $differentNote, // Won't be overriden
            'identityReview' => true, // all review actions will be overriden
            'addressReview' => false,
            'countryReview' => false,
            'kycProviderReview' => true,
            'dueDiligenceLevelReview' => false,
            'kycSurveyReview' => false,
            'transactionsReview' => false,
        ];

        yield 'Invalid preset' => [$kycReview1, 'unknown_or_invalid', $invalidPreset];
        yield 'Mangopay Id doc renewal' => [
            $kycReview1,
            'mangopay_id_doc_renewal',
            $mangopayIdDocRenewal,
        ];
        yield 'Overriding behaviour' => [
            $kycReview2,
            'mangopay_id_doc_renewal',
            $overrideBehaviour,
        ];
    }

    /**
     * @param KycReview[] $existingReviews
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayIdDocRenewalProvider')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testHandleMangopayIdRenewal(
        User $user,
        array $existingReviews,
        bool $reportVerified,
        string $checkType,
        string $score,
        string $result,
        bool $expectNewReview,
    ): void {
        $this->kycReviewRepositoryMock
            ->expects($this->once())
            ->method('findOpenReviews')
            ->with(
                $user,
                KycReviewType::Recurring,
                ['identityReview', 'kycProviderReview'],
            )
            ->willReturn($existingReviews);

        $kycReport = new KycReport(
            subject: $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: $checkType,
            result: $result,
            score: $score,
            verified: $reportVerified,
        );
        $kycReport = EntityIdTestUtil::setEntityId($kycReport, 414);
        $actual = $this->service->handleMangopayIdRenewal($kycReport);

        /**
         * There are several combinations to check
         * - No existing reviews AND kyc report says user is verified
         *     == no kyc review should be returned
         * - No existing reviews BUT kyc report says user is NOT verified AND it's a recognised id doc renewal
         *     == create new id doc renewal recurring kyc review
         * - Existing reviews AND kyc report says user is verified
         *     == resolve the earliest KYC review and return it
         * - Existing reviews BUT kyc report says the user is NOT verified
         *     == return the earliest open recurring KYC review
         */

        if (empty($existingReviews) && $reportVerified) {
            // If no open review and no KYC issue, do nothing
            // echo PHP_EOL . 'no reviews and not verified' . PHP_EOL;
            $this->assertNull($actual);
        }

        if (empty($existingReviews) && !$reportVerified && $expectNewReview) {
            // If no open review and has KYC issue, open new review
            // echo PHP_EOL . 'no reviews and not verified' . PHP_EOL;
            $this->assertEquals(
                KycReviewStatus::PendingSubjectAction,
                $actual->getStatus(),
            );
            $this->assertEquals(KycReviewType::Recurring, $actual->getReviewType());
            $this->assertEquals($user, $actual->getSubject());
            $this->assertTrue($actual->isIdentityReview());
            $this->assertTrue($actual->isKycProviderReview());
            $this->assertFalse($actual->isAddressReview());
            $this->assertFalse($actual->isCountryReview());
            $this->assertFalse($actual->isDueDiligenceLevelReview());
            $this->assertFalse($actual->isKycSurveyReview());
            $this->assertFalse($actual->isTransactionsReview());
            $this->assertNull($actual->getCompletedAt());
            $this->assertNull($actual->isDecision());
            $this->assertNull($actual->getId());
        }

        // Verified report should complete the earliest review
        if (!empty($existingReviews) && $reportVerified) {
            // If open review, resolve that review
            // echo PHP_EOL . 'has reviews and verified' . PHP_EOL;
            $this->assertEquals(KycReviewStatus::Completed, $actual->getStatus());
            $this->assertEquals($user, $actual->getSubject());
            $this->assertTrue($actual->isDecision());
            $this->assertNotNull($actual->getCompletedAt());
            // Should be treated by reviewed by the system
            $this->assertNull($actual->getReviewedBy());
            $this->assertNotNull($actual->getId());
        }
        // Failed report should leave kyc review as is
        if (!empty($existingReviews) && !$reportVerified) {
            // If open review already, just return the open review
            // echo PHP_EOL . 'has reviews and not verified' . PHP_EOL;
            $this->assertEquals($existingReviews[0], $actual);
            $this->assertEquals(KycReviewStatus::Ready, $actual->getStatus());
            $this->assertNull($actual->getCompletedAt());
            $this->assertNull($actual->isDecision());
            $this->assertNotNull($actual->getId());
        }
    }

    public static function mangopayIdDocRenewalProvider(): \Generator
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 51714);
        $user->setMangoPayUserId('id_doc_ewnewal_test_' . bin2hex(random_bytes(8)));

        $kycReview1 = EntityIdTestUtil::setEntityId(
            new KycReview(KycReviewType::Recurring, $user),
            1231,
        );
        $kycReview1->setIdentityReview(true);
        $kycReview1->setKycProviderReview(true);
        $kycReview1->setStatus(KycReviewStatus::Ready);

        $kycReview2 = EntityIdTestUtil::setEntityId(
            new KycReview(KycReviewType::Recurring, $user),
            1652,
        );
        $kycReview2->setIdentityReview(true);
        $kycReview2->setKycProviderReview(true);
        $kycReview2->setStatus(KycReviewStatus::Open);

        $kycReview3 = EntityIdTestUtil::setEntityId(
            new KycReview(KycReviewType::Recurring, $user),
            2445,
        );
        $kycReview3->setIdentityReview(true);
        $kycReview3->setKycProviderReview(true);
        $kycReview3->setStatus(KycReviewStatus::Ready);

        $kycReview4 = EntityIdTestUtil::setEntityId(
            new KycReview(KycReviewType::Recurring, $user),
            id: 6614,
        );
        $kycReview4->setIdentityReview(true);
        $kycReview4->setKycProviderReview(true);
        $kycReview4->setStatus(KycReviewStatus::Open);

        yield 'Verified regulatory, no open reviews' => [
            $user,
            [],
            true,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            MangopayBlockActionCode::NewIdDocRequired->value,
            '',
            false,
        ];
        yield 'Not verified regulatory, no open reviews' => [
            $user,
            [],
            false,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            MangopayBlockActionCode::NewIdDocRequired->value,
            '',
            true,
        ];
        yield 'Not verified regulatory, no open reviews, alt code' => [
            $user,
            [],
            false,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            MangopayBlockActionCode::KycVerificationRequired->value,
            '',
            true,
        ];
        yield 'Not verified regulatory, no open reviews, but non matching code' => [
            $user,
            [],
            false,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            'diff',
            '',
            false,
        ];
        yield 'Verified regulatory, has open reviews' => [
            $user,
            [$kycReview1, $kycReview2],
            true,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            MangopayBlockActionCode::NewIdDocRequired->value,
            '',
            false,
        ];
        yield 'Not verified regulatory, has open reviews' => [
            $user,
            [$kycReview3, $kycReview4],
            false,
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            MangopayBlockActionCode::NewIdDocRequired->value,
            '',
            false,
        ];

        // For USER_KYC_LIGHT downgrades
        yield 'Verified kyc, no open reviews' => [
            $user,
            [],
            true,
            PersonType::Legal,
            '1',
            'REGULAR',
            false,
        ];
        yield 'Not verified kyc, no open reviews' => [
            $user,
            [],
            false,
            PersonType::Legal,
            '0',
            'LIGHT',
            true,
        ];
        // Score is ignored, the result is what is checked
        yield 'Not verified kyc, no open reviews, but score is not 0' => [
            $user,
            [],
            false,
            PersonType::Legal,
            '1',
            'LIGHT',
            true,
        ];
        yield 'Not verified kyc, no open reviews, but regular' => [
            $user,
            [],
            false,
            PersonType::Legal,
            '0',
            'REGULAR',
            false,
        ];
        yield 'Verified kyc, has open reviews' => [
            $user,
            [$kycReview1, $kycReview2],
            true,
            PersonType::Legal,
            '1',
            'REGULAR',
            false,
        ];
        yield 'Not verified kyc, has open reviews' => [
            $user,
            [$kycReview3, $kycReview4],
            false,
            PersonType::Legal,
            '0',
            'LIGHT',
            false,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('idDocReviewProvider')]
    public function testCanSendNotification(bool $expected, KycReview $kycReview): void
    {
        $actual = $this->service->canSendNotification($kycReview);
        $this->assertSame($expected, $actual);
    }

    public static function idDocReviewProvider(): \Generator
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $idOnlyReview = new KycReview(KycReviewType::Recurring, $user);
        $idOnlyReview->setIdentityReview(true);
        $idOnlyReview->setStatus(KycReviewStatus::PendingSubjectAction);

        $mangopayIdReview = new KycReview(KycReviewType::Recurring, $user);
        $mangopayIdReview->setIdentityReview(true);
        $mangopayIdReview->setKycProviderReview(true);
        $mangopayIdReview->setStatus(KycReviewStatus::PendingSubjectAction);

        $kycProviderOnlyReview = new KycReview(KycReviewType::Recurring, $user);
        $kycProviderOnlyReview->setKycProviderReview(true);
        $kycProviderOnlyReview->setStatus(KycReviewStatus::PendingSubjectAction);

        $idOnlyReviewNoAction = new KycReview(KycReviewType::Recurring, $user);
        $idOnlyReviewNoAction->setIdentityReview(true);
        $idOnlyReviewNoAction->setStatus(KycReviewStatus::Ready);

        yield 'ID review' => [true, $idOnlyReview];
        yield 'Mangopay id review' => [true, $mangopayIdReview];
        yield 'KYC provider only' => [false, $kycProviderOnlyReview];
        yield 'ID review no action' => [false, $idOnlyReviewNoAction];
    }

    public function testSendIdConfirmationNotification(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $kycReview = EntityIdTestUtil::setEntityId(
            new KycReview(KycReviewType::Recurring, $user),
            554,
        );

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Confirm your personal details',
                "As a regulated financial institution, we need to ensure information we hold on you is up to date and accurate.
                \nPlease review and confirm your personal details from your profile.
                \nTo get started, visit our website, log in to your account, and follow the prompts.
                \nCertain functionality involving your wallet (e.g. investments, dividends, withdrawals) may be restricted until this is complete.",
                ['title' => 'Confirm Your Personal Details'],
            );

        $this->service->sendIdConfirmationNotification($kycReview);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('isIdDocRenewalProvider')]
    public function testIsIdDocRenewal(bool $expected, KycReport $input): void
    {
        $actual = $this->service->isIdDocRenewal($input);
        $this->assertSame($expected, $actual);
    }

    public static function isIdDocRenewalProvider(): \Generator
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $user->setMangoPayUserId('id_doc_ewnewal_test_' . bin2hex(random_bytes(8)));
        $otherCheckType = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: 'Individual only',
            result: json_encode([]),
            score: '100',
            verified: false,
        );
        $diffProvider = new KycReport(
            $user,
            providerName: ContegoKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: 'Individual only',
            result: json_encode([]),
            score: '100',
            verified: false,
        );
        $regulatoryUnknownActionCode = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            result: json_encode([]),
            score: MangopayBlockActionCode::NewAddressDocRequired->value,
            verified: false,
        );
        $regulatoryIdDocActionCode = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            result: 'LIGHT',
            score: MangopayBlockActionCode::NewIdDocRequired->value,
            verified: false,
        );
        $regulatoryKycVerifyActionCode = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            result: 'LIGHT',
            score: MangopayBlockActionCode::KycVerificationRequired->value,
            verified: false,
        );
        $naturalLight = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Natural,
            result: 'LIGHT',
            score: '0',
            verified: false,
        );
        $legalLight = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Legal,
            result: 'LIGHT',
            score: '0',
            verified: false,
        );
        $naturalRegular = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Natural,
            result: 'REGULAR',
            score: '0',
            verified: false,
        );
        $legalRegular = new KycReport(
            $user,
            providerName: MangopayKycService::PROVIDER_NAME,
            providerReferenceId: $user->getMangoPayUserId(),
            checkType: PersonType::Legal,
            result: 'REGULAR',
            score: '0', // not checked
            verified: false,
        );
        yield 'Unsupported checktype' => [false, $otherCheckType];
        yield 'Unsupported provider' => [false, $diffProvider];
        yield 'Unsupported action code' => [false, $regulatoryUnknownActionCode];
        yield 'Action code id doc' => [true, $regulatoryIdDocActionCode];
        yield 'Action code kyc verify' => [true, $regulatoryKycVerifyActionCode];
        yield 'Natural kyc fail' => [true, $naturalLight];
        yield 'Legal kyc fail' => [true, $legalLight];
        yield 'Natural kyc regular' => [false, $naturalRegular];
        yield 'Legal kyc regular' => [false, $legalRegular];
    }
}
