<?php

namespace App\Tests\Service;

use App\Service\MangopayCardService;
use App\Service\MangopayWalletService;
use MangoPay\Card;
use MangoPay\PayIn;
use MangoPay\PayInPaymentDetailsCard;
use MangoPay\PayInPaymentType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayCardServiceTest extends KernelTestCase
{
    private MangopayCardService $service;
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

        $this->service = static::getContainer()->get(MangopayCardService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payinTypeProvider')]
    public function testGetPayInCardId(?string $expected, string $paymentType): void
    {
        $payinId = 'payin_test_' . bin2hex(random_bytes(8));
        $payin = new PayIn();
        $payin->Id = $payinId;
        $payin->PaymentType = $paymentType;
        $details = new PayInPaymentDetailsCard();
        $details->CardId = $expected;
        $payin->PaymentDetails = $details;

        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('retrievePayin')
            ->with($payinId)
            ->willReturn($payin);

        $actual = $this->service->getPayInCardId($payinId);
        $this->assertSame($expected, $actual);
    }

    public static function payinTypeProvider(): \Generator
    {
        yield 'Is card payin' => [
            'card_test_' . bin2hex(random_bytes(8)),
            PayInPaymentType::Card,
        ];
        yield 'Not card payin' => [
            null,
            PayInPaymentType::BankWire,
        ];
    }

    public function testGetPayInCardIdExceptionOnRetrieve(): void
    {
        $payinId = 'payin_test_' . bin2hex(random_bytes(8));

        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('retrievePayin')
            ->with($payinId)
            ->willThrowException(new \Exception('Test simulation'));

        $actual = $this->service->getPayInCardId($payinId);
        $this->assertNull($actual);
    }

    public function testDeactivateCardById(): void
    {
        $cardId = 'card_test_' . bin2hex(random_bytes(8));
        $activeCard = new Card();
        $activeCard->Id = $cardId;
        $activeCard->Active = true;

        $blockedCard = new Card();
        $blockedCard->Id = $cardId;
        $blockedCard->Active = false;

        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('retrieveCard')
            ->with($cardId)
            ->willReturn($activeCard);
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('deactivateCard')
            ->with($activeCard)
            ->willReturn($blockedCard);

        $actual = $this->service->deactivateCardById($cardId);
        $this->assertEquals($blockedCard, $actual);
    }

    public function testDeactivateCardByIdAlreadyDeactivated(): void
    {
        $cardId = 'card_test_' . bin2hex(random_bytes(8));
        $card = new Card();
        $card->Id = $cardId;
        $card->Active = false;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('retrieveCard')
            ->with($cardId)
            ->willReturn($card);
        $this->mangopayWalletServiceMock
            ->expects($this->never())
            ->method('deactivateCard');

        $actual = $this->service->deactivateCardById($cardId);
        $this->assertEquals($card, $actual);
    }

    public function testDeactivateCardByIdExceptionOnRetrieve(): void
    {
        $cardId = 'card_test_' . bin2hex(random_bytes(8));
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('retrieveCard')
            ->with($cardId)
            ->willThrowException(new \Exception('Test simulation'));

        $actual = $this->service->deactivateCardById($cardId);
        $this->assertNull($actual);
    }
}
