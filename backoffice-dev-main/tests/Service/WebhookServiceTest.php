<?php

namespace App\Tests\Service;

use App\Entity\WebhookEvent;
use App\Repository\WebhookEventRepository;
use App\Service\MangopayWalletService;
use App\Service\WebhookService;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\EventType;
use MangoPay\KycLevel;
use MangoPay\MangoPayApi;
use MangoPay\PayIn;
use MangoPay\PayInStatus;
use MangoPay\Recipient;
use MangoPay\ReportRequest;
use MangoPay\ReportStatus;
use MangoPay\ScopeBlocked;
use MangoPay\TransactionStatus;
use MangoPay\UserBlockStatus;
use MangoPay\UserNaturalSca;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class WebhookServiceTest extends KernelTestCase
{
    private WebhookService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(WebhookService::class);
    }

    public function testIsNew(): void
    {
        $eventType = 'testWebhookEvent' . bin2hex(random_bytes(8));
        $resourceId = 'testResourceId' . bin2hex(random_bytes(8));
        $actual = $this->service->isNew($eventType, $resourceId);
        $this->assertSame(true, $actual);

        $actual = $this->service->isNew($eventType, $resourceId);
        $this->assertSame(false, $actual);
    }

    public function testCleanOldWebhookEvents(): void
    {
        $webhookEventRepository = static::getContainer()->get(WebhookEventRepository::class);
        $startTime = time();
        $offset = 8;
        $rangeEnd = 24;
        // Create a bunch of events around the default cutoff cooldown
        // Expect around 7-8 to be cleaned by default
        foreach (range(0, $rangeEnd, 2) as $iteration) {
            $eventTime = $startTime - WebhookService::COOLDOWN + $iteration - $offset;
            $newWebhookEvent = new WebhookEvent(
                'testEventType',
                'testResourceId',
                'testFingerprint',
                $eventTime,
            );
            $webhookEventRepository->save($newWebhookEvent, $iteration === $rangeEnd);
        }
        // echo PHP_EOL . 'remaining ' . $webhookEventRepository->count([]);

        // Otherwise default to cooldown as cutoff
        $cutoffTime = time() - WebhookService::COOLDOWN;
        $this->service->cleanOldWebhookEvents();
        /** @var WebhookEvent $event */
        foreach ($webhookEventRepository->findAll() as $event) {
            $this->assertGreaterThanOrEqual($cutoffTime, $event->getLastReceived());
        }

        // Should work for any offset as well, e.g. clean up even newer ones
        $customCuttoff = 50;
        $cutoffTime = time() - $customCuttoff;
        $this->service->cleanOldWebhookEvents($customCuttoff);
        /** @var WebhookEvent $event */
        foreach ($webhookEventRepository->findAll() as $event) {
            $this->assertGreaterThanOrEqual($cutoffTime, $event->getLastReceived());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayWebhookEventsProvider')]
    public function testIsValidMangopayHook(
        bool $expected,
        string $eventType,
        ?string $resourceId,
        string $mangopayMethod,
        \MangoPay\KycDocument|\MangoPay\User|\MangoPay\Transfer|Recipient|PayIn|ReportRequest|UserBlockStatus|null $mangopayObject = null,
    ): void {
        // Clear out cached values (from Mangopay retrieves used for verification checks)
        /** @var TagAwareCacheInterface $cache */
        $cache = static::getContainer()->get(TagAwareCacheInterface::class);
        $cache->invalidateTags(['webhook']);

        // $mangopayServiceMock = $this->getMockBuilder(MangopayWalletService::class)
        //     ->disableOriginalConstructor()
        //     ->getMock();
        $mangopayServiceMock = $this->createStub(MangopayWalletService::class);
        if ($mangopayObject !== null) {
            $mangopayServiceMock
                ->method($mangopayMethod)
                ->with($resourceId)
                ->willReturn($mangopayObject);
        } else {
            $mangopayServiceMock
                ->method($mangopayMethod)
                ->with($resourceId)
                ->willThrowException(new \Exception());
        }

        /** @var MangopayWalletService $mangopayServiceMock */
        /** @var MangoPayApi $mangopayApiMock */
        $service = new WebhookService(
            static::getContainer()->get(LoggerInterface::class),
            static::getContainer()->get(EntityManagerInterface::class),
            static::getContainer()->get(TagAwareCacheInterface::class),
            static::getContainer()->get(WebhookEventRepository::class),
            $mangopayServiceMock,
        );
        // Do a cleanup before the actual test run
        $service->cleanOldWebhookEvents(0);
        $actual = $service->isValidMangopayHook($eventType, $resourceId);
        $this->assertSame($expected, $actual);
    }

    public static function mangopayWebhookEventsProvider(): \Generator
    {
        $resourceId = '123' . bin2hex(random_bytes(8));

        $kycDocumentOutdated = new \MangoPay\KycDocument($resourceId);
        $kycDocumentOutdated->Status = \MangoPay\KycDocumentStatus::OutOfDate;

        $kycDocumentFailed = new \MangoPay\KycDocument($resourceId);
        $kycDocumentFailed->Status = \MangoPay\KycDocumentStatus::Refused;

        $kycUserLight = new UserNaturalSca();
        $kycUserLight->KYCLevel = KycLevel::Light;

        $kycUserRegular = new UserNaturalSca();
        $kycUserRegular->KYCLevel = KycLevel::Regular;

        $scaUserActive = new UserNaturalSca();
        $scaUserActive->UserStatus = 'ACTIVE';

        $scaUserPending = new UserNaturalSca();
        $scaUserPending->UserStatus = 'PENDING_USER_ACTION';

        $report = new ReportRequest($resourceId);
        $report->Status = ReportStatus::ReadyForDownload;

        $transferSucceeded = new \MangoPay\Transfer($resourceId);
        $transferSucceeded->Status = TransactionStatus::Succeeded;

        $transferFailed = new \MangoPay\Transfer($resourceId);
        $transferFailed->Status = TransactionStatus::Failed;

        $recipientActive = new Recipient();
        $recipientActive->Status = 'ACTIVE';

        $recipientCancelled = new Recipient();
        $recipientCancelled->Status = 'CANCELED';

        $recipientClosed = new Recipient();
        $recipientClosed->Status = 'DEACTIVATED';

        $payinSucceeded = new PayIn();
        $payinSucceeded->Status = PayInStatus::Succeeded;

        $payinFailed = new PayIn();
        $payinFailed->Status = PayInStatus::Failed;

        $userBlocked = new UserBlockStatus();
        $scopeBlocked = new ScopeBlocked();
        $scopeBlocked->Inflows = true;
        $scopeBlocked->Outflows = true;
        $userBlocked->ScopeBlocked = $scopeBlocked;

        $userNotBlocked = new UserBlockStatus();
        $scopeNotBlocked = new ScopeBlocked();
        $scopeNotBlocked->Inflows = false;
        $scopeNotBlocked->Outflows = false;
        $userNotBlocked->ScopeBlocked = $scopeNotBlocked;

        // KYC Document status changes
        yield 'Kyc doc outdated' => [
            true,
            EventType::KycOutdated,
            $resourceId,
            'getKycDocument',
            $kycDocumentOutdated,
        ];
        yield 'Kyc doc failed' => [
            true,
            EventType::KycFailed,
            $resourceId,
            'getKycDocument',
            $kycDocumentFailed,
        ];

        // User KYC changes
        yield 'Kyc user light' => [
            true,
            EventType::UserKycLight,
            $resourceId,
            'getScaUser',
            $kycUserLight,
        ];
        yield 'Kyc user regular' => [
            true,
            EventType::UserKycRegular,
            $resourceId,
            'getScaUser',
            $kycUserRegular,
        ];

        // Sca enrollment
        yield 'Sca user active' => [
            true,
            'USER_ACCOUNT_ACTIVATED',
            $resourceId,
            'getScaUser',
            $scaUserActive,
        ];
        yield 'Sca user status mismatch' => [
            false,
            'USER_ACCOUNT_ACTIVATED',
            $resourceId,
            'getScaUser',
            $scaUserPending,
        ];
        yield 'Sca user enrollment success' => [
            true,
            'SCA_ENROLLMENT_SUCCEEDED',
            $resourceId,
            'getScaUser',
            $scaUserActive,
        ];
        yield 'Sca user enrollment mismatch' => [
            false,
            'SCA_ENROLLMENT_SUCCEEDED',
            $resourceId,
            'getScaUser',
            $scaUserPending,
        ];

        // Transfers
        yield 'Transfer succeeded' => [
            true,
            EventType::TransferNormalSucceeded,
            $resourceId,
            'getTransfer',
            $transferSucceeded,
        ];
        yield 'Transfer failed' => [
            true,
            EventType::TransferNormalFailed,
            $resourceId,
            'getTransfer',
            $transferFailed,
        ];

        yield 'Mismatched transfer event type to expected' => [
            false,
            EventType::UserKycLight,
            $resourceId,
            'getScaUser',
            $kycUserRegular,
        ];

        // Recipient
        yield 'Recipient activate' => [
            true,
            EventType::RecipientActive,
            $resourceId,
            'retrieveRecipient',
            $recipientActive,
        ];
        yield 'Recipient failed' => [
            true,
            EventType::RecipientCanceled,
            $resourceId,
            'retrieveRecipient',
            $recipientCancelled,
        ];
        yield 'Recipient deactivated' => [
            true,
            EventType::RecipientDeactivated,
            $resourceId,
            'retrieveRecipient',
            $recipientClosed,
        ];
        yield 'Mismatched recipient event type to expected' => [
            false,
            EventType::RecipientActive,
            $resourceId,
            'retrieveRecipient',
            $recipientClosed,
        ];

        // PayIns
        yield 'Payin success' => [
            true,
            EventType::PayinNormalSucceeded,
            $resourceId,
            'retrievePayin',
            $payinSucceeded,
        ];
        yield 'Payin failed' => [
            true,
            EventType::PayinNormalFailed,
            $resourceId,
            'retrievePayin',
            $payinFailed,
        ];
        yield 'Mismatched payin event type to expected' => [
            false,
            EventType::PayinNormalSucceeded,
            $resourceId,
            'retrievePayin',
            $payinFailed,
        ];

        // User Regulatory Blocks
        yield 'User inflow blocked' => [
            true,
            EventType::UserInflowsBlocked,
            $resourceId,
            'getUserRegulatory',
            $userBlocked,
        ];
        yield 'User outflow blocked' => [
            true,
            EventType::UserOutflowsBlocked,
            $resourceId,
            'getUserRegulatory',
            $userBlocked,
        ];
        yield 'User inflow unblocked' => [
            true,
            EventType::UserInflowsUnblocked,
            $resourceId,
            'getUserRegulatory',
            $userNotBlocked,
        ];
        yield 'User outflow unblocked' => [
            true,
            EventType::UserOutflowsUnblocked,
            $resourceId,
            'getUserRegulatory',
            $userNotBlocked,
        ];
        yield 'User inflow mismatch' => [
            false,
            EventType::UserInflowsBlocked,
            $resourceId,
            'getUserRegulatory',
            $userNotBlocked,
        ];
        yield 'User outflow mismatch' => [
            false,
            EventType::UserOutflowsUnblocked,
            $resourceId,
            'getUserRegulatory',
            $userBlocked,
        ];

        // Reports
        yield 'Report ready' => [
            true,
            'REPORT_READY_FOR_DOWNLOAD',
            $resourceId,
            'getReport',
            $report,
        ];

        // Generics
        yield 'Unknown resource id' => [
            false,
            EventType::KycOutdated,
            'unknownResourceId',
            'getKycDocument',
        ];

        yield 'Unknown event type' => [
            false,
            'unknownEventType',
            $resourceId,
            'getKycDocument',
        ];

        yield 'Missing resource id' => [
            false,
            EventType::KycOutdated,
            null,
            'getKycDocument',
        ];
    }
}
