<?php

namespace App\Tests\Service;

use App\Service\MangopayScaService;
use App\Service\MangopayWalletService;
use MangoPay\PendingUserAction;
use MangoPay\Recipient;
use MangoPay\TransactionStatus;
use MangoPay\UserConsent;
use MangoPay\UserEnrollmentResult;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayScaServiceTest extends KernelTestCase
{
    private MangopayScaService $service;
    private MangopayWalletService|MockObject $mangopayWalletServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Configure any services that we want to mock (due to interaction with external services)
        $this->mangopayWalletServiceMock = $this->createMock(MangopayWalletService::class);
        static::getContainer()->set(
            MangopayWalletService::class,
            $this->mangopayWalletServiceMock,
        );

        $this->service = static::getContainer()->get(MangopayScaService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayTransferStatusProvider')]
    public function testIsTransferSucceeded(
        bool $expected,
        string $transferStatus,
    ): void {
        $transferId = 'xfer_test_' . bin2hex(random_bytes(8));
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = $transferId;
        $transfer->Status = $transferStatus;

        $this->mangopayWalletServiceMock
            ->method('getTransfer')
            ->with($transferId)
            ->willReturn($transfer);

        $actual = $this->service->isTransferSucceeded($transferId);
        $this->assertSame($expected, $actual);
    }

    public static function mangopayTransferStatusProvider(): \Generator
    {
        yield 'Succeeded' => [
            true,
            TransactionStatus::Succeeded,
        ];
        yield 'Failed' => [
            false,
            TransactionStatus::Failed,
        ];
        yield 'Created' => [
            false,
            TransactionStatus::Created,
        ];
    }

    public function testIsTransferSucceededWithException(): void
    {
        $transferId = 'xfer_test_' . bin2hex(random_bytes(8));
        $this->mangopayWalletServiceMock
            ->method('getTransfer')
            ->with($transferId)
            ->willThrowException(new \Exception('Test simulation'));

        $actual = $this->service->isTransferSucceeded($transferId);
        $this->assertFalse($actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayRecipientStatusProvider')]
    public function testIsRecipientActivated(?bool $expected, string $status): void
    {
        $recipientId = 'rec_test_' . bin2hex(random_bytes(8));
        $recipient = new \MangoPay\Recipient();
        $recipient->Id = $recipientId;
        $recipient->Status = $status;

        $this->mangopayWalletServiceMock
            ->method('retrieveRecipient')
            ->with($recipientId)
            ->willReturn($recipient);

        $actual = $this->service->isRecipientActivated($recipientId);
        $this->assertSame($expected, $actual);
    }

    public static function mangopayRecipientStatusProvider(): \Generator
    {
        yield 'Succeeded' => [
            true,
            'ACTIVE',
        ];
        yield 'Already deactivated' => [
            false,
            'DEACTIVATED',
        ];
        yield 'Cancelled - Failed SCA' => [
            null,
            'CANCELED',
        ];
        yield 'Pending - still awaiting SCA' => [
            null,
            'PENDING',
        ];
    }

    public function testIsRecipientActivatedWithException(): void
    {
        $recipientId = 'rec_test_' . bin2hex(random_bytes(8));
        $this->mangopayWalletServiceMock
            ->method('retrieveRecipient')
            ->with($recipientId)
            ->willThrowException(new \Exception('Test simulation'));

        $actual = $this->service->isRecipientActivated($recipientId);
        $this->assertFalse($actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayScaUrlProvider')]
    public function testGetScaSessionUrl(
        string $expected,
        Recipient|UserConsent|UserEnrollmentResult $input,
        string $returnUrl,
    ): void {
        $actual = $this->service->getScaSessionUrl($input, $returnUrl);
        $this->assertEquals($expected, $actual);
    }

    public static function mangopayScaUrlProvider(): \Generator
    {
        $exampleToken = 'sca_exampletoken' . bin2hex(random_bytes(8));
        $pendingUserAction = new PendingUserAction();
        $pendingUserAction->RedirectUrl = "https://sca.sandbox.mangopay.com/?token={$exampleToken}";

        $pendingUserActionProd = new PendingUserAction();
        $pendingUserActionProd->RedirectUrl = "https://sca.mangopay.com/?token={$exampleToken}";

        $recipient = new Recipient();
        $recipient->PendingUserAction = $pendingUserAction;

        $enrollment = new UserEnrollmentResult();
        $enrollment->PendingUserAction = $pendingUserAction;

        $proxyConsent = new UserConsent();
        $proxyConsent->PendingUserAction = $pendingUserAction;

        $proxyConsentProd = new UserConsent();
        $proxyConsentProd->PendingUserAction = $pendingUserActionProd;

        yield 'Recipient' => [
            "https://sca.sandbox.mangopay.com/?token={$exampleToken}&returnUrl=http%3A%2F%2Fback.dev.local%2Fadmin%2Fbank-accounts%2F4%2Fenable%2Fsca-callback",
            $recipient,
            'http://back.dev.local/admin/bank-accounts/4/enable/sca-callback',
        ];
        yield 'Enrollment' => [
            "https://sca.sandbox.mangopay.com/?token={$exampleToken}&returnUrl=http%3A%2F%2Fback.dev.local%2Fadmin%2Fsettings%2Fsuperadmin-sca",
            $enrollment,
            'http://back.dev.local/admin/settings/superadmin-sca',
        ];
        yield 'Proxy manage' => [
            "https://sca.sandbox.mangopay.com/?token={$exampleToken}&returnUrl=http%3A%2F%2Fback.dev.local%2Fadmin%2Fusers%2F3%2Fdashboard%2Fmangopay-sca",
            $proxyConsent,
            'http://back.dev.local/admin/users/3/dashboard/mangopay-sca',
        ];
        yield 'Proxy manage prod' => [
            "https://sca.mangopay.com/?token={$exampleToken}&returnUrl=http%3A%2F%2Fback.dev.local%2Fadmin%2Fusers%2F3%2Fdashboard%2Fmangopay-sca",
            $proxyConsentProd,
            'http://back.dev.local/admin/users/3/dashboard/mangopay-sca',
        ];
    }

    public function testGetScaSessionUrlNoUrl(): void
    {
        $this->expectExceptionMessage('No Mangopay SCA session url found');
        $this->service->getScaSessionUrl(new Recipient(), '');
    }

    public function testGetScaSessionUrlInvalidUrl(): void
    {
        $this->expectExceptionMessage('Unknown Mangopay SCA session url');
        $mangopayScaObject = new UserConsent();
        $pendingUserAction = new PendingUserAction();
        // deliberate typo
        $pendingUserAction->RedirectUrl = 'https://sca.sandbox.mangopoy.com/?token=sca_019ae96a65397ff2a33cb4f5105c154f';
        $mangopayScaObject->PendingUserAction = $pendingUserAction;
        $this->service->getScaSessionUrl($mangopayScaObject, '');
    }
}
