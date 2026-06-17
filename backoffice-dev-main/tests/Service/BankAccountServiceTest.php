<?php

namespace App\Tests\Service;

use App\Dto\Sca\ScaActionResponseDto;
use App\Entity\Address;
use App\Entity\BankAccount;
use App\Entity\Enum\ActionRequest;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountTransition;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Service\BankAccountService;
use App\Service\MangopayWalletService;
use App\Service\NotificationService;
use App\Test\Util\EntityIdTestUtil;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use MangoPay\Address as MangoPayAddress;
use MangoPay\BusinessRecipient;
use MangoPay\IndividualRecipient;
use MangoPay\Libraries\Error;
use MangoPay\Libraries\ResponseException;
use MangoPay\PayoutMethods;
use MangoPay\PendingUserAction;
use MangoPay\Recipient;
use MangoPay\RecipientPropertySchema;
use MangoPay\RecipientSchema;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

final class BankAccountServiceTest extends KernelTestCase
{
    private BankAccountService $service;
    private MangopayWalletService|MockObject $mangopayWalletServiceMock;
    private NotificationService|MockObject $notificationServiceMock;
    protected AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        $this->databaseTool = static::getContainer()
            ->get(DatabaseToolCollection::class)
            ->get();

        self::bootKernel();

        // Configure any services that we want to mock (due to interaction with external services)
        $this->mangopayWalletServiceMock = $this->createMock(MangopayWalletService::class);
        static::getContainer()->set(
            MangopayWalletService::class,
            $this->mangopayWalletServiceMock,
        );

        $this->notificationServiceMock = $this->createMock(NotificationService::class);
        static::getContainer()->set(
            NotificationService::class,
            $this->notificationServiceMock,
        );

        $this->service = static::getContainer()->get(BankAccountService::class);
    }

    protected function teardown(): void
    {
        unset($this->databaseTool);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('countrySchemaProvider')]
    public function testGetSchemaForRecipient(
        string $country,
        string $currency,
        string $chosenMethod,
        string $recipientType,
        string $expectedMethod,
    ): void {
        $this->mangopayWalletServiceMock
            ->method('retrieveRecipientSchema')
            ->with($expectedMethod, $recipientType, $currency, $country)
            ->willReturn(new RecipientSchema());

        $actual = $this->service->getSchemaForRecipient(
            country: $country,
            currency: $currency,
            payoutMethod: $chosenMethod,
            recipientType: $recipientType,
        );
        // Check expected fields changed
        $this->assertEquals(RecipientSchema::class, $actual::class);
    }

    public static function countrySchemaProvider(): \Generator
    {
        yield 'GB auto' => ['GB', 'GBP', 'auto', 'Individual', 'LocalBankTransfer'];
        yield 'US auto' => [
            'US',
            'GBP',
            'auto',
            'Business',
            'InternationalBankTransfer',
        ];
        yield 'FR auto' => [
            'FR',
            'EUR',
            'auto',
            'Individual',
            'InternationalBankTransfer',
        ];
        yield 'DE local' => [
            'DE',
            'EUR',
            'LocalBankTransfer',
            'Individual',
            'LocalBankTransfer',
        ];
        yield 'GB intl' => [
            'GB',
            'GBP',
            'InternationalBankTransfer',
            'Individual',
            'InternationalBankTransfer',
        ];
        yield 'GB other' => [
            'GB',
            'EUR',
            'something_different',
            'Individual',
            'InternationalBankTransfer',
        ];
    }

    public function testGetPayoutMethods(): void
    {
        $this->mangopayWalletServiceMock
            ->method('retrievePayoutMethods')
            ->with('GBP', 'DE')
            ->willReturn(new PayoutMethods());

        $actual = $this->service->getPayoutMethods('DE');
        // Check expected fields changed
        $this->assertEquals(PayoutMethods::class, $actual::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('schemaNormalizerProvider')]
    public function testNormalizeSchema(
        string $countryAlpha2,
        RecipientSchema $schema,
        array $expected,
    ): void {
        $actual = $this->service->normalizeSchema($schema, $countryAlpha2);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public static function schemaNormalizerProvider(): \Generator
    {
        $gbAcSchema = new RecipientPropertySchema();
        $gbAcSchema->Required = true;
        $gbAcSchema->MaxLength = 8;
        $gbAcSchema->MinLength = 8;
        $gbAcSchema->Pattern = "^\\d{8}$";
        $gbAcSchema->Label = 'Account Number';
        $gbAcSchema->EndUserDisplay = 'Show';

        $gbBicSchema = new RecipientPropertySchema();
        $gbBicSchema->Required = true;
        $gbBicSchema->MaxLength = 6;
        $gbBicSchema->MinLength = 6;
        $gbBicSchema->Pattern = "^\\d{6}$";
        $gbBicSchema->Label = 'Sort Code';
        $gbBicSchema->EndUserDisplay = 'Show';

        $gbSchema = new RecipientSchema();
        $gbSchema->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => $gbAcSchema,
                'SortCode' => $gbBicSchema,
            ],
        ];
        $gbExpected = [
            'accountNumber' => [
                'required' => true,
                'maxLength' => 8,
                'minLength' => 8,
                'pattern' => "^\\d{8}$",
                'allowedValues' => null,
                'label' => 'Account Number',
                'endUserDisplay' => 'Show',
            ],
            'bic' => [
                'required' => true,
                'maxLength' => 6,
                'minLength' => 6,
                'pattern' => "^\\d{6}$",
                'allowedValues' => null,
                'label' => 'Sort Code',
                'endUserDisplay' => 'Show',
            ],
        ];

        $ibanSchema = new RecipientPropertySchema();
        $ibanSchema->Required = true;
        $ibanSchema->MaxLength = 34;
        $ibanSchema->Pattern = "^[a-zA-Z]{2}\\d{2}\\s*(\\w{4}\\s*){2,7}\\w{1,4}\\s*$";
        $ibanSchema->Label = 'Account Number';
        $ibanSchema->EndUserDisplay = 'Show';

        $deSchema = new RecipientSchema();
        $deSchema->InternationalBankTransfer = [
            'AccountNumber' => $ibanSchema,
        ];
        $deExpected = [
            'accountNumber' => [
                'required' => true,
                'maxLength' => 34,
                'minLength' => null,
                'pattern' => "^[a-zA-Z]{2}\\d{2}\\s*(\\w{4}\\s*){2,7}\\w{1,4}\\s*$",
                'allowedValues' => null,
                'label' => 'Account Number',
                'endUserDisplay' => 'Show',
            ],
        ];

        $usAcSchema = new RecipientPropertySchema();
        $usAcSchema->Required = true;
        $usAcSchema->MaxLength = 12;
        $usAcSchema->MinLength = 8;
        $usAcSchema->Pattern = '^[0-9a-zA-Z]{8,12}$';
        $usAcSchema->Label = 'Account Number';
        $usAcSchema->EndUserDisplay = 'Show';

        $usBicSchema = new RecipientPropertySchema();
        $usBicSchema->Required = true;
        $usBicSchema->Pattern = '^[0-9a-zA-Z]{8}([0-9a-zA-Z]{3})?$';
        $usBicSchema->Label = 'BIC';
        $usBicSchema->EndUserDisplay = 'Show';

        $usSchema = new RecipientSchema();
        $usSchema->InternationalBankTransfer = [
            'AccountNumber' => $usAcSchema,
            'BIC' => $usBicSchema,
        ];
        $usExpected = [
            'accountNumber' => [
                'required' => true,
                'maxLength' => 12,
                'minLength' => 8,
                'pattern' => '^[0-9a-zA-Z]{8,12}$',
                'allowedValues' => null,
                'label' => 'Account Number',
                'endUserDisplay' => 'Show',
            ],
            'bic' => [
                'required' => true,
                'maxLength' => null,
                'minLength' => null,
                'pattern' => '^[0-9a-zA-Z]{8}([0-9a-zA-Z]{3})?$',
                'allowedValues' => null,
                'label' => 'BIC',
                'endUserDisplay' => 'Show',
            ],
        ];
        yield 'GB Local' => ['GB', $gbSchema, $gbExpected];
        yield 'Germany IBAN' => ['DE', $deSchema, $deExpected];
        yield 'US Intl' => ['US', $usSchema, $usExpected];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bankAccountFingerprintProvider')]
    public function testGetFingerprint(?string $expected, BankAccount $input): void
    {
        $actual = $this->service->getFingerprint($input);
        $this->assertEquals($expected, $actual);
    }

    public static function bankAccountFingerprintProvider(): \Generator
    {
        $gbAccount = new BankAccount();
        $gbAccount->setCountry('GB');
        $gbAccount->setAccountNumber('55779911');
        $gbAccount->setBankIdentifierCode('200000');

        $ibanAccount = new BankAccount();
        $ibanAccount->setCountry('FR');
        // The BIC for this account is BNPAFRPP, but isn't technically necessary for mangopay
        $ibanAccount->setAccountNumber('FR7630004000031234567890143');

        $ibanBicAccount = new BankAccount();
        $ibanBicAccount->setCurrency('EUR');
        $ibanBicAccount->setCountry('FR');
        $ibanBicAccount->setAccountNumber('FR541558929750ZZZZZZAC01915');
        $ibanBicAccount->setBankIdentifierCode('CMBRFR2BXXX');

        $usAccount = new BankAccount();
        $usAccount->setCurrency('USD');
        $usAccount->setCountry('US');
        $usAccount->setAccountNumber('1002003004');
        $usAccount->setBankIdentifierCode('CHASUS33XXX');

        yield 'GB local' => ['b67d5d3c508d2e7f90f95ecca686d35d', $gbAccount];
        yield 'FR GBP IBAN only' => ['c1d75988d79881491d2ae94bb9cf8feb', $ibanAccount];
        yield 'FR IBAN with BIC' => [
            '79b7ef77436ecf94449f4e061e13913d',
            $ibanBicAccount,
        ];
        yield 'US Intl' => ['81249dc95a4e045d2c37a030579cb7c9', $usAccount];
    }

    public function testIsNotDuplicated(): void
    {
        // Just load the minimal user fixtures
        $this->databaseTool->loadFixtures([
            'App\DataFixtures\TestUserFixtures',
        ]);

        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $bankAccount = new BankAccount();
        $bankAccount->setUser($em->getRepository(User::class)->find(1));
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        // New account should not be detected as a duplicate
        $this->assertTrue($this->service->isNotDuplicated($bankAccount));

        // Once it's saved, resubmitting it will be detected as a duplicate
        $em->persist($bankAccount);
        $em->flush();
        $this->assertFalse($this->service->isNotDuplicated($bankAccount));

        // Change status to an inactive state will allow it to be readded
        $bankAccount->setStatus(BankAccountStatus::Closed);
        $em->flush();
        $this->assertTrue($this->service->isNotDuplicated($bankAccount));

        // Changing the user, but keeping the same fingerprint will also allow be considered non-dupe
        // Reactivate the account first
        $bankAccount->setStatus(BankAccountStatus::Active);
        $em->flush();
        // Change the user AFTER flushing the reverting changes, to emulate a new/different bank account...
        $bankAccount->setUser($em->getRepository(User::class)->find(2));
        $this->assertTrue($this->service->isNotDuplicated($bankAccount));
    }

    public function testValidateWithMangopay(): void
    {
        $this->mangopayWalletServiceMock->method('validateRecipient')->willReturn(null);

        $user = EntityIdTestUtil::setEntityId(new User(), 414);
        $user->setMangoPayUserId('wallet_m_test_recipient_validation');
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $this->assertSame([], $this->service->validateWithMangopay($bankAccount));
        $this->assertSame(BankAccountStatus::Validated, $bankAccount->getStatus());
    }

    public function testValidateWithMangopayHasErrors(): void
    {
        $mangopayError = new Error();
        $mangopayError->Errors = [
            'UserId' => 'USER_NOT_FOUND',
        ];
        $responseException = new ResponseException('/test', 400, $mangopayError);
        $this->mangopayWalletServiceMock
            ->method('validateRecipient')
            ->willThrowException($responseException);

        $user = EntityIdTestUtil::setEntityId(new User(), 414);
        $user->setMangoPayUserId('wallet_m_test_recipient_validation');
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setStatus(BankAccountStatus::Validated);
        $this->assertSame(
            $mangopayError->Errors,
            $this->service->validateWithMangopay($bankAccount),
        );
        // Will downgrade a validated status to pending
        $this->assertSame(BankAccountStatus::Pending, $bankAccount->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statusDowngradeProvider')]
    public function testValidateWithMangopayHasErrorsNoDowngrade(BankAccountStatus $status): void
    {
        $mangopayError = new Error();
        $mangopayError->Errors = [
            'UserId' => 'USER_NOT_FOUND',
        ];
        $responseException = new ResponseException('/test', 400, $mangopayError);
        $this->mangopayWalletServiceMock
            ->method('validateRecipient')
            ->willThrowException($responseException);

        $user = EntityIdTestUtil::setEntityId(new User(), 414);
        $user->setMangoPayUserId('wallet_m_test_recipient_validation');
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setStatus($status);
        $this->assertSame(
            $mangopayError->Errors,
            $this->service->validateWithMangopay($bankAccount),
        );
        // No downgrade if failed validation in other states
        $this->assertSame($status, $bankAccount->getStatus());
    }

    public static function statusDowngradeProvider(): \Generator
    {
        yield 'pending' => [BankAccountStatus::Pending];
        yield 'approved' => [BankAccountStatus::Approved];
        yield 'rejected' => [BankAccountStatus::Rejected];
        yield 'active' => [BankAccountStatus::Active];
        yield 'closed' => [BankAccountStatus::Closed];
    }

    public function testValidateWithMangopayCrashNoDowngrade(): void
    {
        $exception = new \RuntimeException('mystery_error');
        $this->mangopayWalletServiceMock
            ->method('validateRecipient')
            ->willThrowException($exception);

        $user = EntityIdTestUtil::setEntityId(new User(), 414);
        $user->setMangoPayUserId('wallet_m_test_recipient_validation');
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setStatus(BankAccountStatus::Validated);
        $this->assertEquals(
            ['Unable to validate with Mangopay'],
            $this->service->validateWithMangopay($bankAccount),
        );
        // No downgrade if unable to perform a validation
        $this->assertSame(BankAccountStatus::Validated, $bankAccount->getStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('statusSyncProvider')]
    public function testSyncStatusWithMangopay(
        string $mangopayStatus,
        BankAccountStatus $start,
        BankAccountStatus $expected,
    ): void {
        $recipient = new Recipient();
        $recipient->Id = 'rec_test' . bin2hex(random_bytes(8));
        $recipient->Status = $mangopayStatus;
        $this->mangopayWalletServiceMock
            ->method('retrieveRecipient')
            ->with($recipient->Id)
            ->willReturn($recipient);

        $bankAccount = new BankAccount();
        $bankAccount->setStatus($start);
        $bankAccount->setProviderId($recipient->Id);
        $this->service->syncStatusWithMangopay($bankAccount);
        $this->assertSame($expected, $bankAccount->getStatus());
    }

    public static function statusSyncProvider(): \Generator
    {
        yield 'PENDING stays pending' => [
            'PENDING',
            BankAccountStatus::Pending,
            BankAccountStatus::Pending,
        ];
        yield 'PENDING stays closed' => [
            'PENDING',
            BankAccountStatus::Closed,
            BankAccountStatus::Closed,
        ];
        yield 'ACTIVE pending to active' => [
            'ACTIVE',
            BankAccountStatus::Pending,
            BankAccountStatus::Active,
        ];
        yield 'ACTIVE approved to active' => [
            'ACTIVE',
            BankAccountStatus::Approved,
            BankAccountStatus::Active,
        ];
        yield 'ACTIVE closed to active' => [
            'ACTIVE',
            BankAccountStatus::Closed,
            BankAccountStatus::Active,
        ];
        yield 'CANCELED active to closed' => [
            'CANCELED',
            BankAccountStatus::Active,
            BankAccountStatus::Closed,
        ];
        yield 'DEACTIVATED approved to closed' => [
            'DEACTIVATED',
            BankAccountStatus::Approved,
            BankAccountStatus::Closed,
        ];
        yield 'DEACTIVATED active to closed' => [
            'DEACTIVATED',
            BankAccountStatus::Active,
            BankAccountStatus::Closed,
        ];
        yield 'DEACTIVATED rejected to closed' => [
            'DEACTIVATED',
            BankAccountStatus::Rejected,
            BankAccountStatus::Closed,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('transitionsProvider')]
    public function testTransitionBankAccount(
        BankAccountTransition $transition,
        BankAccountStatus $start,
        BankAccountStatus $expected,
    ): void {
        $bankAccount = new BankAccount();
        $bankAccount->setStatus($start);
        $this->service->transitionBankAccount($bankAccount, $transition->value);
        $this->assertEquals($expected, $bankAccount->getStatus());
    }

    public static function transitionsProvider(): \Generator
    {
        yield 'approve' => [
            BankAccountTransition::Approve,
            BankAccountStatus::Pending,
            BankAccountStatus::Approved,
        ];
        yield 'unapprove' => [
            BankAccountTransition::Unapprove,
            BankAccountStatus::Approved,
            BankAccountStatus::Pending,
        ];
        yield 'enable' => [
            BankAccountTransition::Enable,
            BankAccountStatus::Approved,
            BankAccountStatus::Active,
        ];
        yield 'disable' => [
            BankAccountTransition::Disable,
            BankAccountStatus::Active,
            BankAccountStatus::Closed,
        ];
        yield 'reject' => [
            BankAccountTransition::Reject,
            BankAccountStatus::Pending,
            BankAccountStatus::Rejected,
        ];
        yield 'reopen closed' => [
            BankAccountTransition::Reopen,
            BankAccountStatus::Closed,
            BankAccountStatus::Pending,
        ];
        yield 'reopen rejected' => [
            BankAccountTransition::Reopen,
            BankAccountStatus::Rejected,
            BankAccountStatus::Pending,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidTransitionsProvider')]
    public function testTransitionBankAccountInvalid(
        BankAccountTransition $transition,
        BankAccountStatus $start,
    ): void {
        // Just check a handful as a sanity check of potentially problematic ones
        // $this->expectException(NotEnabledTransitionException::class);
        $bankAccount = new BankAccount();
        $bankAccount->setStatus($start);
        $this->service->transitionBankAccount($bankAccount, $transition->value);
        // Invalid transitions are ignored
        $this->assertEquals($start, $bankAccount->getStatus());
    }

    public static function invalidTransitionsProvider(): \Generator
    {
        yield 'unapprove pending' => [
            BankAccountTransition::Unapprove,
            BankAccountStatus::Pending,
        ];
        yield 'enable pending' => [
            BankAccountTransition::Enable,
            BankAccountStatus::Pending,
        ];
        yield 'approve approved' => [
            BankAccountTransition::Approve,
            BankAccountStatus::Approved,
        ];
        yield 'approve active' => [
            BankAccountTransition::Approve,
            BankAccountStatus::Active,
        ];
        yield 'reopen active' => [
            BankAccountTransition::Reopen,
            BankAccountStatus::Active,
        ];
        // yield 'disable pending' => [BankAccountTransition::Disable, BankAccountStatus::Pending];
        // yield 'disable approved' => [BankAccountTransition::Disable, BankAccountStatus::Approved];
        yield 'disable closed' => [
            BankAccountTransition::Disable,
            BankAccountStatus::Closed,
        ];
        yield 'reject active' => [
            BankAccountTransition::Reject,
            BankAccountStatus::Active,
        ];
        yield 'reject closed' => [
            BankAccountTransition::Reject,
            BankAccountStatus::Closed,
        ];
    }

    public function testCreateMangopayRecipientObjectGB(): void
    {
        $user = new User();
        $user->setFirstname('TestBankAccount');
        $user->setLastname('UserOwnerName');
        $user->setMangoPayUserId(bin2hex(random_bytes(8)));
        $userAddress = new Address();
        $userAddress->setAddress1('44 Mango Street');
        $userAddress->setAddress2('Tropical Borough');
        $userAddress->setCity('London');
        $userAddress->setPostCode('SW12 8TH');
        $userAddress->setCountry('GB');
        $user->addAddress($userAddress);

        $description = 'Description to go in tag ' . bin2hex(random_bytes(6));
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setDescription($description);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountHolderLastName('ChangeLastName');

        // Will check the Mangopay owner address is correctly configured
        $ownerAddress = new MangoPayAddress();
        $ownerAddress->AddressLine1 = $userAddress->getAddress1();
        $ownerAddress->AddressLine2 = $userAddress->getAddress2();
        $ownerAddress->City = $userAddress->getCity();
        $ownerAddress->Region = $userAddress->getRegion();
        $ownerAddress->PostalCode = $userAddress->getPostCode();
        $ownerAddress->Country = $userAddress->getCountry();

        $individualRecipient = new IndividualRecipient();
        $individualRecipient->FirstName = $user->getFirstname();
        $individualRecipient->LastName = $bankAccount->getAccountHolderLastName();
        $individualRecipient->Address = $ownerAddress;

        $recipient = new Recipient();
        $recipient->Currency = 'GBP';
        $recipient->Country = 'GB';
        $recipient->DisplayName = 'GBP GB _ 9911';
        $recipient->PayoutMethodType = 'LocalBankTransfer';
        $recipient->RecipientType = 'Individual';
        $recipient->LocalBankTransfer = [
            'GBP' => [
                'AccountNumber' => $bankAccount->getAccountNumber(),
                'SortCode' => $bankAccount->getBankIdentifierCode(),
            ],
        ];
        $recipient->IndividualRecipient = $individualRecipient;
        $recipient->Tag = $bankAccount->getDescription();

        $actual = $this->service->createMangopayRecipientObject($bankAccount);
        $this->assertEquals($recipient, $actual);
    }

    public function testCreateMangopayRecipientObjectFRBiz(): void
    {
        $user = new User();
        $user->setFirstname('TestBankAccount');
        $user->setLastname('UserOwnerName');
        $user->setMangoPayUserId(bin2hex(random_bytes(8)));
        $userAddress = new Address();
        $userAddress->setAddress1('44 Mango Street');
        $userAddress->setAddress2('Tropical Borough');
        $userAddress->setCity('London');
        $userAddress->setPostCode('SW12 8TH');
        $userAddress->setCountry('GB');
        $user->addAddress($userAddress);

        $description = 'Biz description to go in tag ' . bin2hex(random_bytes(6));
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::International);
        $bankAccount->setAccountHolderType(BankAccountHolderType::Business);
        $bankAccount->setAccountNumber('FR7630004000031234567890143');
        $bankAccount->setDescription($description);
        $bankAccount->setCountry('FR');
        $bankAccount->setAccountHolderName('Some Special Biz');

        // Will check the Mangopay owner address is correctly configured
        $ownerAddress = new MangoPayAddress();
        $ownerAddress->AddressLine1 = $userAddress->getAddress1();
        $ownerAddress->AddressLine2 = $userAddress->getAddress2();
        $ownerAddress->City = $userAddress->getCity();
        $ownerAddress->Region = $userAddress->getRegion();
        $ownerAddress->PostalCode = $userAddress->getPostCode();
        $ownerAddress->Country = $userAddress->getCountry();

        $bizRecipient = new BusinessRecipient();
        $bizRecipient->BusinessName = $bankAccount->getAccountHolderName();
        $bizRecipient->Address = $ownerAddress;

        $recipient = new Recipient();
        $recipient->Currency = 'GBP';
        $recipient->Country = 'FR';
        $recipient->DisplayName = 'GBP FR _ 0143';
        $recipient->PayoutMethodType = 'InternationalBankTransfer';
        $recipient->RecipientType = 'Business';
        $recipient->InternationalBankTransfer = [
            'AccountNumber' => $bankAccount->getAccountNumber(),
        ];
        $recipient->BusinessRecipient = $bizRecipient;
        $recipient->Tag = $bankAccount->getDescription();

        $actual = $this->service->createMangopayRecipientObject($bankAccount);
        $this->assertEquals($recipient, $actual);
    }

    public function testCreateMangopayRecipient(): void
    {
        $user = new User();
        $user->setFirstname('TestBankAccount');
        $user->setLastname('UserOwnerName');
        $user->setMangoPayUserId(bin2hex(random_bytes(8)));
        $userAddress = new Address();
        $userAddress->setAddress1('44 Mango Street');
        $userAddress->setAddress2('Tropical Borough');
        $userAddress->setCity('London');
        $userAddress->setPostCode('SW12 8TH');
        $userAddress->setCountry('GB');
        $user->addAddress($userAddress);

        $description = 'Description to go in tag ' . bin2hex(random_bytes(6));
        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setDescription($description);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountHolderLastName('ChangeLastName');
        $bankAccount->setStatus(BankAccountStatus::Approved);

        // Will check what we are sending to Mangopay is the constructed Mangopay bank account object
        // The construction of this object is tested in a separate test
        $mangopayRequest = $this->service->createMangopayRecipientObject($bankAccount);
        // Mangopay should just set the Id and make the bank account active
        // We only need to check that we store that Mangopay Id in the registration entity
        $mangopayReponse = clone $mangopayRequest;
        $mangopayReponse->Id = bin2hex(random_bytes(8));

        $this->mangopayWalletServiceMock
            ->method('createRecipient')
            ->with($user->getMangoPayUserId(), $mangopayRequest)
            ->willReturn($mangopayReponse);

        $actual = $this->service->createMangopayRecipient($bankAccount);
        // Status does not change from Approved until SCA is completed
        $this->assertEquals(BankAccountStatus::Approved, $bankAccount->getStatus());
        // Check expected fields changed
        $this->assertEquals($mangopayReponse->Id, $bankAccount->getProviderId());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bankAccountIsActiveProvider')]
    public function testDisableBankAccount(bool $active, bool $hasProviderId): void
    {
        $user = new User();
        $user->setMangoPayUserId(bin2hex(random_bytes(8)));

        $bankAccount = new BankAccount();
        $bankAccount->setUser($user);
        $bankAccount->setStatus(BankAccountStatus::Active);

        $mangopayBankAccount = new Recipient();
        $mangopayBankAccount->Id = bin2hex(random_bytes(8));
        $mangopayBankAccount->Status = $active ? 'ACTIVE' : 'DEACTIVATED';

        if ($hasProviderId) {
            $bankAccount->setProviderId($mangopayBankAccount->Id);
            $this->mangopayWalletServiceMock
                ->expects($this->once())
                ->method('retrieveRecipient')
                ->with($mangopayBankAccount->Id)
                ->willReturn($mangopayBankAccount);
            if ($active) {
                $this->mangopayWalletServiceMock
                    ->expects($this->once())
                    ->method('deactivateRecipient')
                    ->with($mangopayBankAccount->Id);
            }
        }

        $actual = $this->service->disableBankAccount($bankAccount);
        // Check expected fields changed
        $this->assertEquals(BankAccountStatus::Closed, $actual->getStatus());
        $this->assertEmpty($actual->getAccountNumber());
        $this->assertEmpty($actual->getBankIdentifierCode());
        if ($hasProviderId) {
            // Provider ID stays even after disabling
            $this->assertEquals($mangopayBankAccount->Id, $actual->getProviderId());
        } else {
            $this->assertEmpty($actual->getProviderId());
        }
    }

    public static function bankAccountIsActiveProvider(): \Generator
    {
        yield 'Active and synced' => [true, true];
        yield 'Active with missing provider id' => [true, false];
        yield 'Inactive with leftover provider id' => [false, true];
        yield 'Nothing to do' => [false, false];
    }

    public function testActivateBankAccount(): void
    {
        $user = new User();
        $user->setFirstname('TestBankAccount');
        $user->setLastname('UserOwnerName');
        $user->setMangoPayUserId(bin2hex(random_bytes(8)));
        $userAddress = new Address();
        $userAddress->setAddress1('44 Mango Street');
        $userAddress->setAddress2('Tropical Borough');
        $userAddress->setCity('London');
        $userAddress->setPostCode('SW12 8TH');
        $userAddress->setCountry('GB');
        $user->addAddress($userAddress);

        $description = 'Description to go in tag ' . bin2hex(random_bytes(6));
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 558);
        $bankAccount->setUser($user);
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setDescription($description);
        $bankAccount->setCountry('GB');
        $bankAccount->setAccountHolderLastName('ChangeLastName');
        $bankAccount->setStatus(BankAccountStatus::Approved);

        // Will check what we are sending to Mangopay is the constructed Mangopay bank account object
        // The construction of this object is tested in a separate test
        $mangopayRequest = $this->service->createMangopayRecipientObject($bankAccount);
        // Mangopay should just set the Id and make the bank account active
        // We only need to check that we store that Mangopay Id in the registration entity
        $mangopayReponse = clone $mangopayRequest;
        $mangopayReponse->Id = bin2hex(random_bytes(8));
        $pendingAction = new PendingUserAction();
        $pendingAction->RedirectUrl = 'https://example.com/';
        $mangopayReponse->PendingUserAction = $pendingAction;
        $mangopayReponse->Status = 'PENDING';

        $this->mangopayWalletServiceMock
            ->method('createRecipient')
            ->with($user->getMangoPayUserId(), $mangopayRequest)
            ->willReturn($mangopayReponse);

        $expected = new ScaActionResponseDto(
            id: $bankAccount->getId(),
            object: 'bankAccount',
            status: BankAccountStatus::Approved->value,
            providerId: $mangopayReponse->Id,
            providerStatus: $mangopayReponse->Status,
            pendingUserAction: [
                'redirectUrl' => $pendingAction->RedirectUrl,
            ],
        );

        $actual = $this->service->activateBankAccount($bankAccount);
        // Status does not change from Approved until SCA is completed
        $this->assertEquals(BankAccountStatus::Approved, $bankAccount->getStatus());
        // Check expected fields changed
        $this->assertEquals($mangopayReponse->Id, $bankAccount->getProviderId());
        // Check the DTO created
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('activationOutcomeProvider')]
    public function testProcessActivationOutcome(
        ?bool $success,
        BankAccountStatus $startStatus,
        BankAccountStatus $endStatus,
    ): void {
        $bankAccount = new BankAccount();
        $bankAccount->setAccountNumber('55779911');
        $bankAccount->setBankIdentifierCode('200000');
        $bankAccount->setStatus($startStatus);
        $this->service->processActivationOutcome($bankAccount, $success);
        $this->assertEquals($endStatus, $bankAccount->getStatus());
        // If bank account is being updated (was in approved status)
        // Also check the account details are wiped
        if ($startStatus == BankAccountStatus::Approved && !is_null($success)) {
            $this->assertNull($bankAccount->getAccountNumber());
            $this->assertNull($bankAccount->getBankIdentifierCode());
        } else {
            $this->assertEquals('55779911', $bankAccount->getAccountNumber());
            $this->assertEquals('200000', $bankAccount->getBankIdentifierCode());
        }
    }

    public static function activationOutcomeProvider(): \Generator
    {
        yield 'success approved to active' => [
            true,
            BankAccountStatus::Approved,
            BankAccountStatus::Active,
        ];
        yield 'fail approved to closed' => [
            false,
            BankAccountStatus::Approved,
            BankAccountStatus::Closed,
        ];
        yield 'success but already active' => [
            true,
            BankAccountStatus::Active,
            BankAccountStatus::Active,
        ];
        yield 'success but not approved' => [
            true,
            BankAccountStatus::Validated,
            BankAccountStatus::Validated,
        ];
        yield 'fail but already closed' => [
            false,
            BankAccountStatus::Closed,
            BankAccountStatus::Closed,
        ];
        yield 'success but already rejected' => [
            true,
            BankAccountStatus::Rejected,
            BankAccountStatus::Rejected,
        ];
        yield 'success null-unknown, approved unchanged' => [
            null,
            BankAccountStatus::Approved,
            BankAccountStatus::Approved,
        ];
    }

    public function testDisableBankAccountInvalidState(): void
    {
        $bankAccountRegistration = new BankAccount();
        $invalidStartStates = [
            BankAccountStatus::Closed,
            BankAccountStatus::Rejected,
        ];
        foreach ($invalidStartStates as $status) {
            $bankAccountRegistration->setStatus($status);
            $actual = $this->service->disableBankAccount($bankAccountRegistration);
            $this->assertEquals($status, $actual->getStatus());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('displayNameProvider')]
    public function testCreateDisplayName(string $expected, BankAccount $input): void
    {
        $actual = $this->service->createDisplayName($input);
        $this->assertEquals($expected, $actual);
    }

    public static function displayNameProvider(): \Generator
    {
        $gb = new BankAccount();
        $gb->setAccountNumber('55779911');
        $gb->setCountry('GB');
        $fr = new BankAccount();
        $fr->setCurrency('EUR');
        $fr->setAccountNumber('FR7630004000031234567890143');
        $fr->setCountry('FR');
        $us = new BankAccount();
        $us->setCurrency('GBP');
        $us->setAccountNumber('1002003004');
        $us->setCountry('US');

        yield 'GB local' => ['GBP GB _ 9911', $gb];
        yield 'FR iban' => ['EUR FR _ 0143', $fr];
        yield 'US intl' => ['GBP US _ 3004', $us];
    }

    public function testSendCreationNotification(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been received',
                'We have received your bank account registration request. You will be notified once the request has been approved or if amendments are required.',
                ['title' => 'Bank Account Registration Received'],
            );

        $this->service->sendCreationNotification($bankAccount);
    }

    public function testSendReviewNotification(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $user->setUsername(bin2hex(random_bytes(8)) . 'test@example.com');
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $_ENV['MAILER_TEAM_ADDRESS'],
                'Bank account registration ready for review',
                "User ID#{$user->getId()} with username {$user->getUserIdentifier()} has submitted a new bank account registration ID#{$bankAccount->getId()}.
                \nA review by BizOps staff is required for approval or rejection.",
                ['title' => 'Bank Account Registration Pending Review'],
                true,
            );

        $this->service->sendReviewNotification($bankAccount);
    }

    public function testSendApprovalNotification(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been approved',
                'Your bank account registration has been approved. You will need to activate your linked bank account in your profile before it can be used for withdrawals.',
                ['title' => 'Bank Account Registration Approved'],
            );

        $this->service->sendApprovalNotification($bankAccount);
    }

    public function testSendActionRequestNotification(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);
        $bankAccount->setMetadata(['actionRequests' => [ActionRequest::ProofAddress]]);

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been updated',
                "Your bank account registration has been reviewed and additional information was requested: proof of address.
                    \nYou can respond to this request from the 'Linked Bank Accounts' section of your profile.",
                ['title' => 'Bank Account Registration More Info Requested'],
            );

        $this->service->sendActionRequestNotification($bankAccount);
    }

    public function testSendActionRequestNotificationNoActions(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        $this->notificationServiceMock
            ->expects($this->never())
            ->method('notifyUserByEmail');

        $this->service->sendActionRequestNotification($bankAccount);
    }

    public function testSendClosureNotificationNoReason(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);

        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been rejected',
                "Your bank account registration has been rejected.
                \nA new bank account registration can be submitted from your profile.",
                ['title' => 'Bank Account Registration Rejected'],
            );

        $this->service->sendClosureNotification(
            $bankAccount,
            BankAccountTransition::Reject,
        );
    }

    public function testSendClosureNotificationWithReason(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);
        $reason = 'testing purposes ' . bin2hex(random_bytes(8));
        $this->notificationServiceMock
            ->expects($this->once())
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been rejected',
                "Your bank account registration has been rejected for the following reason(s): {$reason}.
                \nA new bank account registration can be submitted from your profile.",
                ['title' => 'Bank Account Registration Rejected'],
            );

        $this->service->sendClosureNotification(
            $bankAccount,
            BankAccountTransition::Reject,
            $reason,
        );
    }

    public function testSendClosureNotificationNoOrOtherTransition(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 4141);
        $bankAccount = EntityIdTestUtil::setEntityId(new BankAccount(), 554);
        $bankAccount->setUser($user);
        $bankAccount->setCountry('GB');
        $bankAccount->setFingerprint('Test');
        $bankAccount->setAccountType(BankAccountType::GB);
        $reason = 'Extra testing purposes ' . bin2hex(random_bytes(8));

        $this->notificationServiceMock
            ->expects($this->exactly(2))
            ->method('notifyUserByEmail')
            ->with(
                $user,
                'Your bank account registration has been closed',
                "Your bank account registration has been closed for the following reason(s): {$reason}.
                \nA new bank account registration can be submitted from your profile.",
                ['title' => 'Bank Account Registration Closed'],
            );

        $this->service->sendClosureNotification($bankAccount, null, $reason);

        $this->service->sendClosureNotification(
            $bankAccount,
            BankAccountTransition::Disable,
            $reason,
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('actionRequestsProvider')]
    public function testActionRequestsAsEnum(array $expected, array $input): void
    {
        $actual = $this->service->actionRequestsAsEnum($input);
        $this->assertEqualsCanonicalizing(
            array_values($expected),
            array_values($actual),
        );
    }

    public static function actionRequestsProvider(): \Generator
    {
        yield 'Empty' => [[], []];
        yield 'Pure strings' => [
            [ActionRequest::ProofId, ActionRequest::ProofAddress],
            ['proof_of_id',          'proof_of_address'],
        ];
        yield 'Invalid strings ignored' => [
            [ActionRequest::ProofId],
            ['abc', 'proof_of_magic', 'proof_of_id'],
        ];
    }
}
