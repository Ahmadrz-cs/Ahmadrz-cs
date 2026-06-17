<?php

namespace App\Tests\Service;

use App\Entity\BankAccount;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Service\BankAccountSyncService;
use App\Service\MangopayWalletService;
use App\Test\Util\EntityIdTestUtil;
use MangoPay\Recipient;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BankAccountSyncServiceTest extends KernelTestCase
{
    private BankAccountSyncService $service;
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

        $this->service = static::getContainer()->get(BankAccountSyncService::class);
    }

    public function testSyncBankAccountsLegacyBankAccounts(): void
    {
        $user = $this->prepUserForSync();

        // The legacy Mangopay bank accounts to be returned
        // $mba1 should already be synced, so won't be retrieved for syncing
        $mba1 = new \MangoPay\BankAccount();
        $mba1->Id = $user->getBankAccounts()->first()->getProviderId();
        $mba2 = new \MangoPay\BankAccount();
        $mba2->Id = 'rec_m_test_' . bin2hex(random_bytes(8));

        // The recipients that will be used for syncing
        $rec1 = new Recipient();
        $rec1->Id = $mba2->Id;
        $rec1->Currency = 'GBP';
        $rec1->Country = 'GB';
        $rec1->PayoutMethodType = 'LocalBankTransfer';
        $rec1->RecipientType = 'Individual';
        $rec1->Status = 'ACTIVE';
        $rec1->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => '55779911',
                'SortCode' => '200000',
            ],
        ];
        $this->mangopayWalletServiceMock
            ->expects(self::once())
            ->method('listUserBankAccounts')
            ->willReturn([$mba1, $mba2]);
        $this->mangopayWalletServiceMock
            ->expects(self::never())
            ->method('listUserRecipients');
        $this->mangopayWalletServiceMock
            ->expects(self::exactly(1))
            ->method('retrieveRecipient')
            ->with($mba2->Id)
            ->willReturn($rec1);

        $this->assertNull($user->getBankAccountsSyncedAt());
        $actual = $this->service->syncBankAccounts($user, 3, false);

        $this->assertCount(1, $actual);
        $this->assertNull($actual[0]->getAccountNumber());
        $this->assertNull($actual[0]->getBankIdentifierCode());
        $this->assertEquals(
            'b67d5d3c508d2e7f90f95ecca686d35d',
            $actual[0]->getFingerprint(),
        );
        $this->assertEquals('GBP GB _ 9911', $actual[0]->getDisplayName());
        $this->assertEquals($rec1->Country, $actual[0]->getCountry());
        $this->assertEquals(BankAccountType::GB, $actual[0]->getAccountType());
        $this->assertEquals(BankAccountStatus::Active, $actual[0]->getStatus());
        $this->assertEquals($user, $actual[0]->getUser());
        $this->assertNotNull($user->getBankAccountsSyncedAt());
    }

    public function testSyncBankAccountsRecipients(): void
    {
        $user = $this->prepUserForSync();

        // The Mangopay recipients to be returned
        // $rec1 should already be synced, so won't be retrieved for syncing
        $rec1 = new Recipient();
        $rec1->Id = $user->getBankAccounts()->first()->getProviderId();
        $rec1->Status = 'ACTIVE';
        $rec2 = new Recipient();
        $rec2->Id = 'rec_m_test_' . bin2hex(random_bytes(8));
        $rec2->Status = 'ACTIVE';

        $rec2->Currency = 'GBP';
        $rec2->Country = 'GB';
        $rec2->PayoutMethodType = 'LocalBankTransfer';
        $rec2->RecipientType = 'Individual';
        $rec2->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => '55779911',
                'SortCode' => '200000',
            ],
        ];
        $this->mangopayWalletServiceMock
            ->expects(self::once())
            ->method('listUserRecipients')
            ->willReturn([$rec1, $rec2]);
        $this->mangopayWalletServiceMock
            ->expects(self::never())
            ->method('listUserBankAccounts');
        $this->mangopayWalletServiceMock
            ->expects(self::exactly(1))
            ->method('retrieveRecipient')
            ->with($rec2->Id)
            ->willReturn($rec2);

        $this->assertNull($user->getBankAccountsSyncedAt());
        $actual = $this->service->syncBankAccounts($user, 3, true);
        $this->assertCount(1, $actual);
        $this->assertNull($actual[0]->getAccountNumber());
        $this->assertNull($actual[0]->getBankIdentifierCode());
        $this->assertEquals(
            'b67d5d3c508d2e7f90f95ecca686d35d',
            $actual[0]->getFingerprint(),
        );
        $this->assertEquals('GBP GB _ 9911', $actual[0]->getDisplayName());
        $this->assertEquals($rec2->Country, $actual[0]->getCountry());
        $this->assertEquals(BankAccountType::GB, $actual[0]->getAccountType());
        $this->assertEquals(BankAccountStatus::Active, $actual[0]->getStatus());
        $this->assertEquals($user, $actual[0]->getUser());
        $this->assertNotNull($user->getBankAccountsSyncedAt());
    }

    public function testGetUserSyncedRecipientIds(): void
    {
        $expected = [
            '15' => 'bank_acc_test_' . bin2hex(random_bytes(8)),
            '24' => 'rec_test_' . bin2hex(random_bytes(8)),
            '44' => 'rec_test_' . bin2hex(random_bytes(8)),
        ];
        $empty = ['16' => null, '38' => null];
        $closed = [
            '76' => 'bank_acc_test_' . bin2hex(random_bytes(8)),
            '3' => 'bank_acc_test_' . bin2hex(random_bytes(8)),
        ];
        $rejected = [
            '35' => 'rec_test_' . bin2hex(random_bytes(8)),
        ];
        $user = new User();
        foreach (array_replace(
            $expected,
            $empty,
            $closed,
            $rejected,
        ) as $id => $providerId) {
            $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), $id);
            $bankAccount->setProviderId($providerId);
            $user->addBankAccount($bankAccount);
            if (in_array($id, array_keys($closed))) {
                $bankAccount->setStatus(BankAccountStatus::Closed);
            }
            if (in_array($id, array_keys($rejected))) {
                $bankAccount->setStatus(BankAccountStatus::Rejected);
            }
        }
        $actual = $this->service->getUserSyncedRecipientIds($user);
        $this->assertEquals($expected, $actual);
    }

    public function testFilterUnsyncedRecipients(): void
    {
        $alreadySynced = [
            'bank_acc_test_' . bin2hex(random_bytes(8)),
            'bank_acc_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
        ];
        $toSync = [
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
            'rec_test_' . bin2hex(random_bytes(8)),
        ];
        $recipientsList = [];
        foreach (array_replace($alreadySynced, $toSync) as $providerId) {
            $recipientsList[] = new Recipient($providerId);
        }
        $batchSize = 4;
        $actual = $this->service->filterUnsyncedRecipients(
            $alreadySynced,
            $recipientsList,
            $batchSize,
        );
        $expected = array_slice($toSync, 0, $batchSize);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mapRecipientProvider')]
    public function testMapActiveMangopayRecipient(
        BankAccount $expected,
        Recipient $recipient,
    ): void {
        $user = EntityIdTestUtil::setEntityId(new User(), 9621);
        $recipient->Status = 'ACTIVE';
        $recipient->Id = 'rec_test' . bin2hex(random_bytes(8));
        $recipient->Tag = 'Recipient mapping test' . bin2hex(random_bytes(8));
        $actual = $this->service->mapActiveMangopayRecipient($user, $recipient);

        $this->assertEquals($user->getId(), $actual->getUser()->getId());
        $this->assertEquals($recipient->Id, $actual->getProviderId());
        $this->assertEquals($recipient->Tag, $actual->getDescription());

        $this->assertEquals($expected->getAccountType(), $actual->getAccountType());
        $this->assertEquals($expected->getDisplayName(), $actual->getDisplayName());
        $this->assertEquals($expected->getFingerprint(), $actual->getFingerprint());
        $this->assertEquals($expected->getCurrency(), $actual->getCurrency());

        $this->assertNull($actual->getAccountNumber());
        $this->assertNull($actual->getBankIdentifierCode());
    }

    public static function mapRecipientProvider(): \Generator
    {
        $recLocalGb = new Recipient();
        $recLocalGb->Currency = 'GBP';
        $recLocalGb->Country = 'GB';
        $recLocalGb->PayoutMethodType = 'LocalBankTransfer';
        $recLocalGb->RecipientType = 'Individual';
        $recLocalGb->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => '55779911',
                'SortCode' => '200000',
            ],
        ];

        $yLocalGb = new BankAccount();
        $yLocalGb->setAccountType(BankAccountType::GB);
        $yLocalGb->setDisplayName('GBP GB _ 9911');
        $yLocalGb->setFingerprint('b67d5d3c508d2e7f90f95ecca686d35d');
        yield 'Local GB' => [$yLocalGb, $recLocalGb];

        $recLocalFr = new Recipient();
        $recLocalFr->Currency = 'EUR';
        $recLocalFr->Country = 'FR';
        $recLocalFr->PayoutMethodType = 'LocalBankTransfer';
        $recLocalFr->RecipientType = 'Individual';
        $recLocalFr->LocalBankTransfer = [
            'EUR' => [
                'IBAN' => 'FR7630004000031234567890143',
                'BIC' => 'BNPAFRPP',
            ],
        ];
        $yLocalFr = new BankAccount();
        $yLocalFr->setCurrency('EUR');
        $yLocalFr->setAccountType(BankAccountType::International);
        $yLocalFr->setDisplayName('EUR FR _ 0143');
        $yLocalFr->setFingerprint('899267b1f0618e0e5fa304b64d9ae498');
        yield 'Local FR' => [$yLocalFr, $recLocalFr];

        $recLocalCa = new Recipient();
        $recLocalCa->Currency = 'CAD';
        $recLocalCa->Country = 'CA';
        $recLocalCa->PayoutMethodType = 'LocalBankTransfer';
        $recLocalCa->RecipientType = 'Individual';
        $recLocalCa->LocalBankTransfer = [
            'CAD' => [
                'AccountNumber' => '11696419',
                'InstitutionNumber' => '614',
                'BranchCode' => '00152',
            ],
        ];
        $yLocalCa = new BankAccount();
        $yLocalCa->setCurrency('CAD');
        $yLocalCa->setAccountType(BankAccountType::International);
        $yLocalCa->setDisplayName('CAD CA _ 6419');
        $yLocalCa->setFingerprint('f5382b48aa79918b5614c5a883e2fc68');
        yield 'Local CA' => [$yLocalCa, $recLocalCa];

        $recLocalUs = new Recipient();
        $recLocalUs->Currency = 'USD';
        $recLocalUs->Country = 'US';
        $recLocalUs->PayoutMethodType = 'LocalBankTransfer';
        $recLocalUs->RecipientType = 'Individual';
        $recLocalUs->LocalBankTransfer = [
            'USD' => [
                'AccountNumber' => '11696419',
                'ABA' => '071000288',
            ],
        ];
        $yLocalUs = new BankAccount();
        $yLocalUs->setCurrency('USD');
        $yLocalUs->setAccountType(BankAccountType::International);
        $yLocalUs->setDisplayName('USD US _ 6419');
        $yLocalUs->setFingerprint('878e7e6140e07a3728e092758979dd46');
        yield 'Local US' => [$yLocalUs, $recLocalUs];

        $recIntlAu = new Recipient();
        $recIntlAu->Currency = 'AUD';
        $recIntlAu->Country = 'AU';
        $recIntlAu->PayoutMethodType = 'InternationalBankTransfer';
        $recIntlAu->RecipientType = 'Individual';
        $recIntlAu->InternationalBankTransfer = [
            'AccountNumber' => '111155559',
            'BIC' => 'CTBAAU2SXXX',
        ];
        $yIntlAu = new BankAccount();
        $yIntlAu->setCurrency('AUD');
        $yIntlAu->setAccountType(BankAccountType::International);
        $yIntlAu->setDisplayName('AUD AU _ 5559');
        $yIntlAu->setFingerprint('424574436265e84c9609a18352c10142');
        yield 'Intl Australia' => [$yIntlAu, $recIntlAu];

        $recIntlDe = new Recipient();
        $recIntlDe->Currency = 'GBP';
        $recIntlDe->Country = 'DE';
        $recIntlDe->PayoutMethodType = 'InternationalBankTransfer';
        $recIntlDe->RecipientType = 'Individual';
        $recIntlDe->InternationalBankTransfer = [
            'AccountNumber' => 'DE89370400440532013000',
        ];
        $yIntlDe = new BankAccount();
        $yIntlDe->setCurrency('GBP');
        $yIntlDe->setAccountType(BankAccountType::International);
        $yIntlDe->setDisplayName('GBP DE _ 3000');
        $yIntlDe->setFingerprint('0d82f30f82246913a5c8eb218d9dcc4f');
        yield 'Intl Germany' => [$yIntlDe, $recIntlDe];
    }

    public function testMapActiveMangopayRecipientInactive(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $disabledBankAccount = new Recipient();
        $disabledBankAccount->Status = 'DEACTIVATED';
        $this->service->mapActiveMangopayRecipient(new User(), $disabledBankAccount);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('loadAccountDetailsProvider')]
    public function testLoadAccountDetails(
        ?string $expectedAccountNumber,
        ?string $expectedBic,
        BankAccountStatus $registrationStatus,
        ?string $providerId,
    ): void {
        $user = EntityIdTestUtil::setEntityId(new User(), 9621);
        $registration = new BankAccount();
        $registration->setUser($user);
        $registration->setAccountType(BankAccountType::GB);
        $registration->setProviderId($providerId);
        $registration->setCountry('GB');
        $registration->setStatus($registrationStatus);

        $recipient = new Recipient();
        $recipient->Currency = 'GBP';
        $recipient->Country = 'GB';
        $recipient->PayoutMethodType = 'LocalBankTransfer';
        $recipient->RecipientType = 'Individual';
        $recipient->Status = 'ACTIVE';
        $recipient->Id = $registration->getProviderId() ?? 'not_being_retrieved';
        $recipient->Tag =
            'Recipient account details load test' . bin2hex(random_bytes(8));
        $recipient->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => $expectedAccountNumber ?? '55779911',
                'SortCode' => $expectedBic ?? '200000',
            ],
        ];

        $this->mangopayWalletServiceMock
            ->method('retrieveRecipient')
            ->with($registration->getProviderId())
            ->willReturn($recipient);

        $actual = $this->service->loadAccountDetails($registration);

        $this->assertNotNull($recipient->LocalBankTransfer['GBP']['AccountNumber']);
        $this->assertNotNull($recipient->LocalBankTransfer['GBP']['SortCode']);
        $this->assertEquals($expectedAccountNumber, $actual->getAccountNumber());
        $this->assertEquals($expectedBic, $actual->getBankIdentifierCode());
    }

    public static function loadAccountDetailsProvider(): \Generator
    {
        $providerId = 'rec_m_test_' . bin2hex(random_bytes(8));

        yield 'Active with provider id - yes load' => [
            '55779911',
            '200000',
            BankAccountStatus::Active,
            $providerId,
        ];
        yield 'Active without provider id - no load' => [
            null,
            null,
            BankAccountStatus::Active,
            null,
        ];
        yield 'Closed - no load' => [
            null,
            null,
            BankAccountStatus::Closed,
            null,
        ];
    }

    private function prepUserForSync(): User
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 9621);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));

        $reg1 = EntityIdTestUtil::setEntityId(new BankAccount(), 441);
        $reg1->setProviderId('rec_m_test_' . bin2hex(random_bytes(8)));
        $reg1->setStatus(BankAccountStatus::Active);

        $user->addBankAccount($reg1);
        return $user;
    }
}
