<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Entity\Enum\EmailTemplate;
use App\Entity\Enum\PaymentType;
use App\Entity\Enum\TradeStatus;
use App\Entity\Enum\TransferType;
use App\Entity\Investment;
use App\Entity\PaymentOrder;
use App\Entity\PaymentRequest;
use App\Entity\ShareTrade;
use App\Entity\TradeOrder;
use App\Entity\TransferOrder;
use App\Entity\TransferRequest;
use App\Entity\User;
use App\Service\MailerService;
use App\Service\MonthEndEmailService;
use App\Service\MonthEndService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MonthEndEmailServiceTest extends KernelTestCase
{
    private MonthEndEmailService $service;
    private MailerService|MockObject $mailerServiceMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Ideally replace with Symfony mailer testing mode which won't send emails
        // But emails can still be captured for testing
        $this->mailerServiceMock = $this->createMock(MailerService::class);
        static::getContainer()->set(MailerService::class, $this->mailerServiceMock);
        $this->service = static::getContainer()->get(MonthEndEmailService::class);
    }

    public function testSendAllPaymentNotifications(): void
    {
        // Want to create a new MonthEndService with a mail service mock
        // Check that the sendMail method was called the expeced number of times
        $asset = new Asset();
        $asset->setName('Exiting asset example');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);
        $paymentOrder->setScheduledFor(new \DateTime());
        $paymentOrder->setPaymentType(PaymentType::Divestment->value);
        $user = new User();
        $notifiedAt = new \DateTime();
        // Paid but without a notification record
        foreach (range(1, 6) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setShareholding($i);
            $payment->setStatus(PaymentRequest::STATE_PAID);
            $paymentOrder->addPayment($payment);
            $expected[] = $payment;
        }
        // Not paid yet - should be filtered out
        foreach (range(1, 3) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setShareholding($i);
            $payment->setStatus(PaymentRequest::STATE_PENDING);
            $paymentOrder->addPayment($payment);
        }
        // Paid and has previous notification record - should be filtered out
        foreach (range(1, 4) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setShareholding($i);
            $payment->setStatus(PaymentRequest::STATE_PAID);
            $payment->setPayeeNotifiedAt($notifiedAt);
            $paymentOrder->addPayment($payment);
        }
        $this->mailerServiceMock->expects(self::exactly(6))->method('sendMail');

        $this->service->sendAllPaymentNotifications($paymentOrder);

        foreach ($paymentOrder->getPayments() as $paymentRequest) {
            if (PaymentRequest::STATE_PAID == $paymentRequest->getStatus()) {
                $this->assertNotNull($paymentRequest->getPayeeNotifiedAt());
            }
        }
    }

    public function testsendOnePaymentNotification(): void
    {
        // Want to create a new MonthEndService with a mail service mock
        // Expect the sendMail to be called once and with the relevant parameters
        $user = new User();
        $asset = new Asset();
        $asset->setName('Exiting asset example');
        $paymentOrder = new PaymentOrder();
        $paymentOrder->setAsset($asset);
        $paymentOrder->setScheduledFor(new \DateTime('2020-06-04'));
        $paymentOrder->setPaymentType(PaymentType::Dividend->value);
        $paymentRequest = new PaymentRequest();
        $paymentRequest->setStatus(PaymentRequest::STATE_PAID);
        $paymentRequest->setPayee($user);
        $paymentRequest->setAmount('211.67');
        $paymentRequest->setShareholding('124');
        $paymentOrder->addPayment($paymentRequest);

        $this->mailerServiceMock
            ->expects(self::once())
            ->method('sendMail')
            ->with($user, MailerService::TYPE_DIVIDEND_PAYMENT, [
                'asset' => 'Exiting asset example',
                'paymentDate' => 'June 2020',
                'paymentAmount' => '211.67',
                'shareholding' => '124',
                'user' => $user,
                'assetName' => 'Exiting asset example',
                'month' => 'June 2020',
                'amount' => '211.67',
                'numOfShares' => '124',
            ]);
        $this->service->sendOnePaymentNotification($paymentRequest);
        $this->assertNotNull($paymentRequest->getPayeeNotifiedAt());
    }

    public function testFilterPaymentsPendingNotification(): void
    {
        $expected = [];
        $paymentOrder = new PaymentOrder();
        $user = new User();
        $notifiedAt = new \DateTime();
        // Not paid yet - should be filtered out
        foreach (range(1, 3) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setStatus(PaymentRequest::STATE_PENDING);
            $paymentOrder->addPayment($payment);
        }
        // Paid but amount is 0, should be filtered out
        foreach (range(1, 3) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount(0);
            $payment->setStatus(PaymentRequest::STATE_PAID);
            $paymentOrder->addPayment($payment);
        }
        // Paid and has previous notification record - should be filtered out
        foreach (range(1, 4) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setStatus(PaymentRequest::STATE_PAID);
            $payment->setPayeeNotifiedAt($notifiedAt);
            $paymentOrder->addPayment($payment);
        }
        // Paid but without a notification record
        foreach (range(1, 6) as $i) {
            $payment = new PaymentRequest();
            $payment->setPayee($user);
            $payment->setAmount($i);
            $payment->setStatus(PaymentRequest::STATE_PAID);
            $paymentOrder->addPayment($payment);
            $expected[] = $payment;
        }
        $actual = $this->service->filterPaymentsPendingNotification($paymentOrder);
        $this->assertCount(6, $actual);
        $this->assertEquals($expected, $actual);
    }

    public function testSendAllSettlementNotifications(): void
    {
        // Want to create a new MonthEndService with a mail service mock
        // Check that the sendTemplatedEmail method was called the expeced number of times
        $asset = new Asset();
        $asset->setName('Exiting asset example');

        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);
        $transferOrder->setScheduledFor(new \DateTime());
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);
        $user = new User();
        $notifiedAt = new \DateTime();
        // Completed but without a notification record
        foreach (range(1, 6) as $i) {
            $buyOrder = new TradeOrder(asset: $asset, user: $user);
            $shareTrade = new ShareTrade(buyOrder: $buyOrder);
            $shareTrade->setStatus(TradeStatus::Settled);

            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $transfer->setShareTrade($shareTrade);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transferOrder->addTransfer($transfer);
            $expected[] = $transfer;
        }
        // Completed but not a settlement transfer - should be filtered out
        foreach (range(1, 2) as $i) {
            $buyOrder = new TradeOrder(asset: $asset, user: $user);
            $shareTrade = new ShareTrade(buyOrder: $buyOrder);
            $shareTrade->setStatus(TradeStatus::Settled);

            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . 'extra info',
            );
            $transfer->setShareTrade($shareTrade);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transferOrder->addTransfer($transfer);
            $expected[] = $transfer;
        }
        // Not completed yet - should be filtered out
        foreach (range(1, 3) as $i) {
            $buyOrder = new TradeOrder(asset: $asset, user: $user);
            $shareTrade = new ShareTrade(buyOrder: $buyOrder);
            // Note that it doesn't matter what the trade status is
            $shareTrade->setStatus(TradeStatus::Settled);

            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $transfer->setShareTrade($shareTrade);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }
        // Completed and has previous notification record - should be filtered out
        foreach (range(1, 4) as $i) {
            $buyOrder = new TradeOrder(asset: $asset, user: $user);
            $shareTrade = new ShareTrade(buyOrder: $buyOrder);
            $shareTrade->setStatus(TradeStatus::Settled);

            $transfer = new TransferRequest();
            $transfer->setShareTrade($shareTrade);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transfer->setUserNotifiedAt($notifiedAt);
            $transferOrder->addTransfer($transfer);
        }
        $this->mailerServiceMock
            ->expects(self::exactly(6))
            ->method('sendTemplatedEmail');

        $this->service->sendAllSettlementNotifications($transferOrder);

        foreach ($transferOrder->getTransfers() as $transferRequest) {
            if (
                TransferRequest::STATE_COMPLETE == $transferRequest->getStatus()
                && str_contains(
                    $transfer->getDescription(),
                    MonthEndService::DESCRIPTION_PRESETS['settlement'],
                )
            ) {
                $this->assertNotNull($transferRequest->getUserNotifiedAt());
            }
        }
    }

    public function testsendOneSettlementNotification(): void
    {
        // Want to create a new MonthEndService with a mail service mock
        // Expect the sendTemplatedEmail to be called once and with the relevant parameters
        $user = new User();
        $user->setFirstname('Tester');
        $asset = new Asset();
        $asset->setName('Exiting asset example');
        $transferOrder = new TransferOrder();
        $transferOrder->setAsset($asset);
        $transferOrder->setScheduledFor(new \DateTime('2020-06-04'));
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);

        $buyOrder = new TradeOrder(asset: $asset, user: $user);
        $shareTrade = new ShareTrade(buyOrder: $buyOrder, numberOfShares: 242);
        $shareTrade->setStatus(TradeStatus::Settled);

        $transferRequest = new TransferRequest();
        $transferRequest->setDescription(
            MonthEndService::DESCRIPTION_PRESETS['settlement'],
        );
        $transferRequest->setStatus(TransferRequest::STATE_COMPLETE);
        $transferRequest->setShareTrade($shareTrade);
        $transferRequest->setAmount('211.67');
        $transferOrder->addTransfer($transferRequest);

        $this->mailerServiceMock
            ->expects(self::once())
            ->method('sendTemplatedEmail')
            ->with(
                $user,
                'Your investment has been settled',
                'Your investment of 242 shares in Exiting asset example has been settled. These shares are now eligible for future dividends.',
                ['title' => 'Investment Settled', 'recipient' => $user->getFirstname()],
                EmailTemplate::BasicCustomer,
                false,
            );
        $this->service->sendOneSettlementNotification($transferRequest);
        $this->assertNotNull($transferRequest->getUserNotifiedAt());
    }

    public function testFilterSettlementsPendingNotification(): void
    {
        $expected = [];
        $transferOrder = new TransferOrder();
        $transferOrder->setTransferType(TransferType::InvestmentSettlement);
        $user = new User();
        $notifiedAt = new \DateTime();
        // Not completed yet - should be filtered out
        foreach (range(1, 3) as $i) {
            $investment = new Investment();
            $investment->setUser($user);
            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $transfer->setInvestment($investment);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_PENDING);
            $transferOrder->addTransfer($transfer);
        }
        // Completed and has previous notification record - should be filtered out
        foreach (range(1, 4) as $i) {
            $investment = new Investment();
            $investment->setUser($user);
            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $transfer->setInvestment($investment);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transfer->setUserNotifiedAt($notifiedAt);
            $transferOrder->addTransfer($transfer);
        }
        // Completed but wrong description so not settlement - should be filtered out
        foreach (range(1, 2) as $i) {
            $investment = new Investment();
            $investment->setUser($user);
            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['stamp duty'] . 'extra info',
            );
            $transfer->setInvestment($investment);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transferOrder->addTransfer($transfer);
        }
        // Completed but without a notification record
        foreach (range(1, 6) as $i) {
            $investment = new Investment();
            $investment->setUser($user);
            $transfer = new TransferRequest();
            $transfer->setDescription(
                MonthEndService::DESCRIPTION_PRESETS['settlement'] . 'extra info',
            );
            $transfer->setInvestment($investment);
            $transfer->setAmount($i);
            $transfer->setStatus(TransferRequest::STATE_COMPLETE);
            $transferOrder->addTransfer($transfer);
            $expected[] = $transfer;
        }
        $actual = $this->service->filterSettlementsPendingNotification($transferOrder);
        $this->assertCount(6, $actual);
        $this->assertEquals($expected, $actual);
    }
}
