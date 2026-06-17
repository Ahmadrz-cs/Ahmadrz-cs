<?php

namespace App\Tests\Controller\Webhooks;

use App\Entity\Asset;
use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderStatus;
use App\Entity\Enum\WalletUserVersion;
use App\Entity\KycReport;
use App\Entity\TradeOrder;
use App\Entity\Transaction;
use App\Entity\User;
use App\Test\FixtureWebTestCase;
use BcMath\Number;
use MangoPay\EventType;
use MangoPay\TransactionStatus;
use Symfony\Component\HttpFoundation\Response;

final class MangopayControllerTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayWebhookEndpointProvider')]
    public function testMangopayWebookEndpointReturns200(string $url): void
    {
        $this->client->request('GET', $url);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public static function mangopayWebhookEndpointProvider(): \Generator
    {
        yield ['/webhooks/mangopay/kyc'];
        yield ['/webhooks/mangopay/sca'];
        yield ['/webhooks/mangopay/report'];
        yield ['/webhooks/mangopay/transfers'];
        yield ['/webhooks/mangopay/recipients'];
        yield ['/webhooks/mangopay/payins'];

        // yield ['/webhooks/mangopay/user'];
        // yield ['/webhooks/mangopay/pay-out'];
    }

    public function testMangopayWebookKycAction(): void
    {
        // Note that this user should already be KYC regular on Mangopay
        /** @var User $sampleUser */
        $sampleUser = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];

        $kycReportCountBefore = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);

        $this->client->request(
            'GET',
            '/webhooks/mangopay/kyc?'
                . http_build_query([
                    'EventType' => EventType::UserKycRegular,
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $kycReportCountAfter = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);
        $this->assertEquals($kycReportCountBefore + 1, $kycReportCountAfter);

        // Repeating the request immediately after should not create a new KycReport
        // Due to the minute cooldown on ignoring duplicate events
        $this->client->request(
            'GET',
            '/webhooks/mangopay/kyc?'
                . http_build_query([
                    'EventType' => EventType::UserKycRegular,
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $kycReportCountAgain = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);
        $this->assertEquals($kycReportCountAfter, $kycReportCountAgain);
    }

    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testMangopayWebookKycRegulatoryFlowsAction(): void
    {
        // Note that this user should already be KYC regular on Mangopay
        /** @var User $sampleUser */
        $sampleUser = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];

        $kycReportCountBefore = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);

        $this->client->request(
            'GET',
            '/webhooks/mangopay/kyc/regulatory-flows?'
                . http_build_query([
                    'EventType' => EventType::UserInflowsUnblocked,
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $kycReportCountAfter = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);
        $this->assertEquals($kycReportCountBefore + 1, $kycReportCountAfter);

        // Repeating the request immediately after should not create a new KycReport
        // Due to the minute cooldown on ignoring duplicate events
        $this->client->request(
            'GET',
            '/webhooks/mangopay/kyc/regulatory-flows?'
                . http_build_query([
                    'EventType' => EventType::UserInflowsUnblocked,
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $kycReportCountAgain = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);
        $this->assertEquals($kycReportCountAfter, $kycReportCountAgain);

        // Repeating the request but for a outflows immediately after should not create a new KycReport
        // Due to the (5) minute cooldown on ignoring similar regulatory flow status changes
        $this->client->request(
            'GET',
            '/webhooks/mangopay/kyc/regulatory-flows?'
                . http_build_query([
                    'EventType' => EventType::UserOutflowsUnblocked,
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $kycReportCountAgain = $this->entityManager
            ->getRepository(KycReport::class)
            ->count([]);
        $this->assertEquals($kycReportCountAfter, $kycReportCountAgain);
    }

    public function testMangopayWebookScaAction(): void
    {
        /** @var User $sampleUser */
        $sampleUser = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR,
        ])[0];

        $this->client->request(
            'GET',
            '/webhooks/mangopay/sca?'
                . http_build_query([
                    'EventType' => 'USER_ACCOUNT_ACTIVATED',
                    'RessourceId' => $sampleUser->getMangoPayUserId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals(
            WalletUserVersion::UserScaEnrollment,
            $sampleUser->getWalletUserVersion(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transferWebhookProvider')]
    public function testMangopayWebookTransfersAction(
        string $eventType,
        string $transactionStatus,
        TradeOrderStatus $tradeOrderStatus,
    ): void {
        $validMangopayTransferId = match ($eventType) {
            EventType::TransferNormalSucceeded => 'xfer_c_01JWY0GX5V2TDFF166PJHA8GG6',
            EventType::TransferNormalFailed => 'xfer_c_01JXAWKVKWAG4EZYHAW5033H9R',
            default => 'DOESNT_MATTER_NOT_CHECKED',
        };
        $transaction = new Transaction();
        // Known succeeded transfer id in Mangopay sandbox
        $transaction->setReferenceId($validMangopayTransferId);
        $transaction->setPaymentStatus(TransactionStatus::Created);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Royal Eversea Glades - Cambridge']);

        $tradeOrder = new TradeOrder(
            direction: TradeDirection::Buy,
            asset: $asset,
            user: $user,
            numberOfShares: 1,
            pricePerShare: new Number($asset->getPricePerShare()),
        );
        $tradeOrder->setTransactionReference($transaction->getReferenceId());
        $tradeOrder->setTransaction($transaction);

        $this->entityManager->persist($tradeOrder);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/webhooks/mangopay/transfers?'
                . http_build_query([
                    'EventType' => $eventType,
                    'RessourceId' => $transaction->getReferenceId(),
                    'Date' => time(),
                ]),
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals($transactionStatus, $transaction->getPaymentStatus());
        $this->assertEquals($tradeOrderStatus, $tradeOrder->getStatus());
    }

    public static function transferWebhookProvider(): \Generator
    {
        yield 'Success' => [
            EventType::TransferNormalSucceeded,
            TransactionStatus::Succeeded,
            TradeOrderStatus::Active,
        ];
        yield 'Fail' => [
            EventType::TransferNormalFailed,
            TransactionStatus::Failed,
            TradeOrderStatus::Cancelled,
        ];
        yield 'Created' => [
            EventType::TransferNormalCreated,
            TransactionStatus::Created,
            TradeOrderStatus::Draft,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('recipientWebhookProvider')]
    public function testMangopayWebookRecipientAction(
        BankAccountStatus $expected,
        string $eventType,
    ): void {
        $validMangopayRecipientId = match ($eventType) {
            EventType::RecipientActive => 'bankacc_m_01HW5RPBZ3JHTXG97AEV316761',
            EventType::RecipientCanceled => 'rec_01JZ34CHC26DET1YH7JHCR1AAP',
            EventType::RecipientDeactivated => 'bankacc_m_01JNGH3HNKMDVVAKAYQJMDGR8W',
            default => '',
        };

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $bar = new BankAccount();
        $bar->setUser($user);
        $bar->setStatus(BankAccountStatus::Approved);
        $bar->setCountry('GB');
        $bar->setAccountType(BankAccountType::GB);
        $bar->setAccountNumber('55779911');
        $bar->setBankIdentifierCode('200000');
        $bar->setDescription('Mangopay webhook recipient automated test');
        $bar->setProviderId($validMangopayRecipientId);
        $this->entityManager->persist($bar);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/webhooks/mangopay/recipients?'
                . http_build_query([
                    'EventType' => $eventType,
                    'RessourceId' => $bar->getProviderId(),
                    'Date' => time(),
                ]),
        );
        // Refresh from database
        $bar = $this->entityManager
            ->getRepository(BankAccount::class)
            ->find($bar->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertEquals($expected, $bar->getStatus());
        $this->assertNotEmpty($bar->getProviderId());
        if ($eventType == EventType::RecipientCanceled) {
            // Account details should also be cleared out
            $this->assertNotEmpty($bar->getAccountNumber());
            $this->assertNotEmpty($bar->getBankIdentifierCode());
        } else {
            // Account details should also be cleared out
            $this->assertEmpty($bar->getAccountNumber());
            $this->assertEmpty($bar->getBankIdentifierCode());
        }
    }

    public static function recipientWebhookProvider(): \Generator
    {
        yield 'Successful activation' => [
            BankAccountStatus::Active,
            EventType::RecipientActive,
        ];
        yield 'Cancelled after SCA - no status change' => [
            BankAccountStatus::Approved,
            EventType::RecipientCanceled,
        ];
        yield 'Deactivated' => [
            BankAccountStatus::Closed,
            EventType::RecipientDeactivated,
        ];
    }
}
