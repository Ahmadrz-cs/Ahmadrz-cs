<?php

namespace App\Tests\Service;

use App\Entity\Address;
use App\Entity\Communication;
use App\Entity\Company;
use App\Entity\ContegoLog;
use App\Entity\Document;
use App\Entity\Enum\AccountCleanupAction;
use App\Entity\Enum\AccountClosureRestriction;
use App\Entity\Enum\AccountRetentionLevel;
use App\Entity\Enum\UserCategory;
use App\Entity\Enum\UserStatus;
use App\Entity\Investor;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Entity\UserCategorisation;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use App\Entity\UserLog;
use App\Entity\UserStatusLog;
use App\Repository\HoldingRepository;
use App\Repository\InvestmentRepository;
use App\Service\AccountClosureService;
use App\Service\MangopayWalletService;
use App\Service\SalesforceService;
use App\Test\Util\EntityIdTestUtil;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MangoPay\Money;
use MangoPay\Pagination;
use MangoPay\UserNaturalSca;
use MangoPay\Wallet;
use Pagerfanta\Adapter\NullAdapter;
use Pagerfanta\Pagerfanta;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class AccountClosureServiceTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private AccountClosureService $service;
    private MangopayWalletService|MockObject $mangopayWalletServiceMock;
    private SalesforceService|MockObject $salesforceServiceMock;
    private InvestmentRepository|MockObject $investmentRepositoryMock;
    private HoldingRepository|MockObject $holdingRepositoryMock;

    protected function setUp(): void
    {
        self::bootKernel();

        // Configure any services that we want to mock (due to interaction with external services)
        $this->mangopayWalletServiceMock = $this->createMock(MangopayWalletService::class);
        static::getContainer()->set(
            MangopayWalletService::class,
            $this->mangopayWalletServiceMock,
        );
        $this->salesforceServiceMock = $this->createMock(SalesforceService::class);
        static::getContainer()->set(
            SalesforceService::class,
            $this->salesforceServiceMock,
        );

        $this->investmentRepositoryMock = $this->createMock(InvestmentRepository::class);
        static::getContainer()->set(
            InvestmentRepository::class,
            $this->investmentRepositoryMock,
        );
        $this->holdingRepositoryMock = $this->createMock(HoldingRepository::class);
        static::getContainer()->set(
            HoldingRepository::class,
            $this->holdingRepositoryMock,
        );

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->service = static::getContainer()->get(AccountClosureService::class);
    }

    protected function tearDown(): void
    {
        // https://symfony.com/doc/current/testing/database.html
        // doing this is recommended to avoid memory leaks
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testGetAccountClosureRestrictionsMangopayBalance(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        $pagination = new Pagination(1, 1);
        $filter = new \MangoPay\FilterTransactions();
        $filter->ScaContext = 'USER_NOT_PRESENT';
        $filter->Status = \MangoPay\TransactionStatus::Succeeded;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('listWalletTransactions')
            ->with($user->getMangoPayWalletId(), $pagination, null, $filter)
            ->willReturn([1]);

        $wallet = new Wallet();
        $money = new Money();
        $money->Amount = 1;
        $wallet->Balance = $money;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getWallet')
            ->with($user->getMangoPayWalletId(), 'USER_NOT_PRESENT')
            ->willReturn($wallet);

        $this->investmentRepositoryMock
            ->expects($this->once())
            ->method('findByWithAssociations')
            ->willReturn(new Pagerfanta(new NullAdapter(0)));

        $this->holdingRepositoryMock
            ->expects($this->once())
            ->method('getShareHoldings')
            ->willReturn([]);

        $expected = [
            AccountClosureRestriction::MangopayUser,
            AccountClosureRestriction::Transactions,
            AccountClosureRestriction::WalletBalance,
        ];
        $actual = $this->service->getAccountClosureRestrictions($user);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAccountClosureRestrictionsActiveShareholder(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        $this->mangopayWalletServiceMock
            ->expects($this->never())
            ->method('listWalletTransactions');

        $wallet = new Wallet();
        $money = new Money();
        $money->Amount = 1;
        $wallet->Balance = $money;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getWallet')
            ->with($user->getMangoPayWalletId(), 'USER_NOT_PRESENT')
            ->willReturn($wallet);
        $this->investmentRepositoryMock
            ->expects($this->once())
            ->method('findByWithAssociations')
            ->with([
                'userId' => $user->getId(),
                'lifecycleStatus' => [
                    InvestmentLifecycle::STATE_APPROVED,
                    InvestmentLifecycle::STATE_SETTLED,
                ],
            ])
            // Number of results must be > 1
            ->willReturn(new Pagerfanta(new NullAdapter(1)));
        $this->holdingRepositoryMock
            ->expects($this->once())
            ->method('getShareHoldings')
            ->with([
                'currentHolding' => 1,
                'capitalRepayments' => false,
                'userId' => $user->getId(),
            ])
            // just needs to be non-empty
            ->willReturn([1]);
        $expected = [
            AccountClosureRestriction::MangopayUser,
            AccountClosureRestriction::Investments,
            AccountClosureRestriction::Transactions,
            AccountClosureRestriction::Shareholder,
            AccountClosureRestriction::WalletBalance,
        ];
        $actual = $this->service->getAccountClosureRestrictions($user);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAccountClosureRestrictionsStaff(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setRoles(['ROLE_ANALYST']);

        $this->mangopayWalletServiceMock->expects($this->never())->method('getScaUser');

        $this->mangopayWalletServiceMock
            ->expects($this->never())
            ->method('listWalletTransactions');

        $this->mangopayWalletServiceMock->expects($this->never())->method('getWallet');

        $this->investmentRepositoryMock
            ->expects($this->once())
            ->method('findByWithAssociations')
            ->willReturn(new Pagerfanta(new NullAdapter(0)));

        $this->holdingRepositoryMock
            ->expects($this->once())
            ->method('getShareHoldings')
            ->willReturn([]);

        $expected = [AccountClosureRestriction::Staff];
        $actual = $this->service->getAccountClosureRestrictions($user);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAccountClosureRestrictionsMangopayBare(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        $pagination = new Pagination(1, 1);
        $filter = new \MangoPay\FilterTransactions();
        $filter->ScaContext = 'USER_NOT_PRESENT';
        $filter->Status = \MangoPay\TransactionStatus::Succeeded;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('listWalletTransactions')
            ->with($user->getMangoPayWalletId(), $pagination, null, $filter)
            ->willReturn([]);

        $wallet = new Wallet();
        $money = new Money();
        $money->Amount = 0;
        $wallet->Balance = $money;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getWallet')
            ->with($user->getMangoPayWalletId(), 'USER_NOT_PRESENT')
            ->willReturn($wallet);

        $this->investmentRepositoryMock
            ->expects($this->once())
            ->method('findByWithAssociations')
            ->willReturn(new Pagerfanta(new NullAdapter(0)));

        $this->holdingRepositoryMock
            ->expects($this->once())
            ->method('getShareHoldings')
            ->willReturn([]);

        $expected = [AccountClosureRestriction::MangopayUser];
        $actual = $this->service->getAccountClosureRestrictions($user);
        $this->assertEquals($expected, $actual);
    }

    public function testGetAccountClosureRestrictionsMangopayClosed(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'CLOSED';
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        $this->mangopayWalletServiceMock
            ->expects($this->never())
            ->method('listWalletTransactions');

        $this->mangopayWalletServiceMock->expects($this->never())->method('getWallet');

        $this->investmentRepositoryMock
            ->expects($this->once())
            ->method('findByWithAssociations')
            ->willReturn(new Pagerfanta(new NullAdapter(0)));

        $this->holdingRepositoryMock
            ->expects($this->once())
            ->method('getShareHoldings')
            ->willReturn([]);

        $expected = [AccountClosureRestriction::MangopayUser];
        $actual = $this->service->getAccountClosureRestrictions($user);
        $this->assertEquals($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('restrictionsCanCloseProvider')]
    public function testCanCloseAccount(bool $expected, array $restrictions): void
    {
        $actual = $this->service->canCloseAccount($restrictions);
        $this->assertSame($expected, $actual);
    }

    public static function restrictionsCanCloseProvider(): \Generator
    {
        yield 'No restrictions' => [true, []];
        yield 'One soft restrictions' => [
            true,
            [
                AccountClosureRestriction::MangopayUser,
            ],
        ];
        yield 'Several soft restrictions' => [
            true,
            [
                AccountClosureRestriction::MangopayUser,
                AccountClosureRestriction::Investments,
                AccountClosureRestriction::Transactions,
            ],
        ];
        yield 'Some hard restrictions' => [
            false,
            [
                AccountClosureRestriction::Shareholder,
            ],
        ];
        yield 'Staff restrictions' => [
            false,
            [
                AccountClosureRestriction::Staff,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('restrictionsRetentionLevelProvider')]
    public function testGetRetentionLevel(
        AccountRetentionLevel $expected,
        array $restrictions,
    ): void {
        $actual = $this->service->getRetentionLevel($restrictions);
        $this->assertSame($expected, $actual);
    }

    public static function restrictionsRetentionLevelProvider(): \Generator
    {
        yield 'Has no restrictions' => [
            AccountRetentionLevel::None,
            [],
        ];
        yield 'Has only Mangopay user' => [
            AccountRetentionLevel::Wallet,
            [
                AccountClosureRestriction::MangopayUser,
            ],
        ];
        yield 'Has Mangopay transactions user' => [
            AccountRetentionLevel::AML,
            [
                AccountClosureRestriction::MangopayUser,
                AccountClosureRestriction::Transactions,
            ],
        ];
        yield 'Has only transactions user' => [
            AccountRetentionLevel::AML,
            [
                AccountClosureRestriction::Transactions,
            ],
        ];
        yield 'Has only investments user' => [
            AccountRetentionLevel::AML,
            [
                AccountClosureRestriction::Investments,
            ],
        ];
        yield 'Has hard restrictions' => [
            AccountRetentionLevel::Full,
            [
                AccountClosureRestriction::Shareholder,
            ],
        ];
    }

    public function testGenerateAnonymisedUsername(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $actual = $this->service->generateAnonymisedUsername($user);
        $currentDate = new \DateTime()->format('Ymd');
        $regexPattern =
            '/^' . "{$user->getId()}_{$currentDate}" . "_\d{6}\@closed\.example\.com$/";
        $this->assertMatchesRegularExpression($regexPattern, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('mangopayUserStatusProvider')]
    public function testGetMangopayUserStatus(string $status): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = $status;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        $actual = $this->service->getMangopayUserStatus($user);
        $this->assertSame($status, $actual);

        // Calling it again, should use cached value
        $actual = $this->service->getMangopayUserStatus($user);
        $this->assertSame($status, $actual);
    }

    public static function mangopayUserStatusProvider(): \Generator
    {
        yield 'Active' => ['ACTIVE'];
        yield 'Pending' => ['PENDING_USER_ACTION'];
        yield 'Closed' => ['CLOSED'];
    }

    public function testGetMangopayUserStatusNoId(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $this->mangopayWalletServiceMock->expects($this->never())->method('getScaUser');

        $actual = $this->service->getMangopayUserStatus($user);
        $this->assertNull($actual);
    }

    public function testGetMangopayUserStatusException(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $this->mangopayWalletServiceMock
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willThrowException(new \Exception());

        $actual = $this->service->getMangopayUserStatus($user);
        $this->assertNull($actual);
    }

    public function testHasSalesforceContact(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $salesforceId = bin2hex(random_bytes(8));
        $customField = new UserCustomFields();
        $customField->setFieldKey('salesforce_id');
        $customField->setFieldValue($salesforceId);
        $user->addCustomField($customField);

        $this->salesforceServiceMock
            ->expects($this->once())
            ->method('retrieve')
            ->with('Contact', $salesforceId)
            ->willReturn(new Response(200));

        $actual = $this->service->hasSalesforceContact($user);
        $this->assertTrue($actual);
    }

    public function testHasSalesforceNoId(): void
    {
        $this->salesforceServiceMock->expects($this->never())->method('retrieve');

        $actual = $this->service->hasSalesforceContact(new user());
        $this->assertFalse($actual);
    }

    public function testHasSalesforceContactNotFound(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $salesforceId = bin2hex(random_bytes(8));
        $customField = new UserCustomFields();
        $customField->setFieldKey('salesforce_id');
        $customField->setFieldValue($salesforceId);
        $user->addCustomField($customField);

        $this->salesforceServiceMock
            ->expects($this->once())
            ->method('retrieve')
            ->with('Contact', $salesforceId)
            ->willThrowException(
                new \GuzzleHttp\Exception\RequestException(
                    'Resource Not Found',
                    new Request('GET', '/'),
                    new Response(404),
                ),
            );

        $actual = $this->service->hasSalesforceContact($user);
        $this->assertFalse($actual);
    }

    public function testCleanupDataUserNotBlocked(): void
    {
        // Should only be allowed to perform cleanup
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $actual = $this->service->cleanupData($user, [AccountCleanupAction::Identity]);
        $this->assertEmpty($actual);
    }

    public function testCleanupDataUserInternalCleanupOnly(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setEnabled(false);
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));

        $fieldsAffected = [
            'firstName',
            'middleName',
            'additionalName',
            'lastName',
            'gender',
            'type',
            'mobile',
            'phone1',
            'phone2',
            'birthDate',
            'birthCountry',
            'birthPlace',
            'drivingLicenseNo',
            'passportCountry',
            'passportExpiry',
            'passportNumber',
            'incomeRange',
            'occupation',
            'affiliateCode',
            'biography',
            'referralCode',
            'sector',
            'tagline',
            'timezone',
            'website',
        ];
        $user->setUsername('somethingtodelete@test.yielderverse.co.uk');
        $user->setEmail('somethingtodelete@test.yielderverse.co.uk');
        $user->setFirstname('There');
        $user->setMiddlename('Was');
        $user->setAdditionalName('Something');
        $user->setLastName('Here');
        $user->setGender('OTHER');
        $user->setType('Mystery');
        $user->setMobile('123');
        $user->setPhone1('456');
        $user->setPhone2('789');
        $user->setBirthDate(new \DateTime());
        $user->setBirthCountry('Magicland');
        $user->setBirthPlace('Hopeville');
        $user->setDrivingLicenseNo('1583');
        $user->setPassportCountry('GB');
        $user->setPassportExpiry(new \DateTime());
        $user->setPassportNumber('111188889');
        $user->setIncomeRange('More or less');
        $user->setOccupation('Magician');
        $user->setAffiliateCode('YielderverseTester');
        $user->setBiography('Did things to test');
        $user->setReferralCode('Yielderverse123');
        $user->setSector('Entertainment');
        $user->setTagline('Test this and that');
        $user->setTimezone(new \DateTime());
        $user->setWebsite('example.com');

        $investor = new Investor();
        $investor->setWordsOfOwn('Humble apply for toppish tester');
        $user->setInvestor($investor);

        $company = new Company();
        $company->setRegAddress1('Biggish Corp Road');
        $user->setCompany($company);

        $address = new Address();
        $address->setAddress1('Magical Roundabout');
        $address->setCity('Hopeless Town');
        $address->setCountry('GB');
        $user->addAddress($address);

        $userdoc = new UserDocument();
        $doc = new Document();
        $userdoc->setDocument($doc);
        $user->addDocument($userdoc);

        $usercomm = new Communication();
        $usercomm->setSubject('Invitation');
        $usercomm->setContent('Blah blah blah');
        $user->addCommunication($usercomm);

        $log = new UserLog();
        $log->setType('Invitation');
        $log->setEvent('Dispatch');
        $log->setMessage('Dipatch Invitation');
        $user->addLog($log);

        $onboardingProfile = $user->getOnboardingProfile();
        $categorisation = new UserCategorisation();
        $categorisation->setCategory(UserCategory::Restricted);
        $categorisation->setDetails(['expected' => '4%']);
        $onboardingProfile->addCategorisation($categorisation);

        $cf1 = new UserCustomFields();
        $cf1->setFieldKey('Tricks');
        $cf1->setFieldValue('5');
        $cf2 = new UserCustomFields();
        $cf2->setFieldKey('salesforce_id');
        $cf2->setFieldValue('WillYouSendMeToCRM');
        $user->addCustomField($cf1);
        $user->addCustomField($cf2);

        $contegoLog = new ContegoLog();
        $contegoLog->setUser($user);
        $contegoLog->setProfileName('AccountClosureTest');
        $contegoLog->setRAG('GREEN');
        $contegoLog->setKycScore('Great');
        $contegoLog->setKycType('Testing');
        $contegoLog->setExtReferenceId('Mystery');
        $contegoLog->setPdfReportUrl('Somewhere');
        $this->entityManager->persist($contegoLog);
        $this->entityManager->flush();

        $expected = [
            AccountCleanupAction::Username,
            AccountCleanupAction::Identity,
            AccountCleanupAction::Contact,
            AccountCleanupAction::Address,
            AccountCleanupAction::Documents,
            AccountCleanupAction::Logs,
            AccountCleanupAction::Onboarding,
            AccountCleanupAction::Company,
            AccountCleanupAction::AdditionalFields,
            AccountCleanupAction::Kyc,
        ];
        $actual = $this->service->cleanupData($user, $expected);
        $this->assertEqualsCanonicalizing($expected, $actual);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($fieldsAffected as $field) {
            $this->assertNull($propertyAccessor->getValue($user, $field));
        }
        $currentDate = new \DateTime()->format('Ymd');
        $regexPattern =
            '/^' . "{$user->getId()}_{$currentDate}" . "_\d{6}\@closed\.example\.com$/";
        $this->assertMatchesRegularExpression($regexPattern, $user->getUsername());
        $this->assertMatchesRegularExpression($regexPattern, $user->getEmail());
        $this->assertNull($user->getInvestor()->getWordsOfOwn());
        $this->assertNull($user->getCompany()->getRegAddress1());
        $this->assertCount(0, $user->getDocuments());
        $this->assertCount(0, $user->getAddresses());
        $this->assertCount(0, $user->getCommunication());
        $this->assertCount(0, $user->getLogs());
        $this->assertCount(0, $user->getOnboardingProfile()->getCategorisations());
        $this->assertCount(1, $user->getCustomFields());
        $sfId = $user->findCustomField('salesforce_id');
        $this->assertNotNull($sfId);
        $this->assertEquals('WillYouSendMeToCRM', $sfId->getFieldValue());
    }

    public function testCleanupDataUserExternalCleanupOnly(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $user->setEnabled(false);
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));
        $salesforceId = bin2hex(random_bytes(8));
        $customField = new UserCustomFields();
        $customField->setFieldKey('salesforce_id');
        $customField->setFieldValue($salesforceId);
        $user->addCustomField($customField);

        // Mangopay user must be active
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock
            ->expects($this->atLeastOnce())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        // Wallet must be empty
        $wallet = new Wallet();
        $money = new Money();
        $money->Amount = 0;
        $wallet->Balance = $money;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getWallet')
            ->with($user->getMangoPayWalletId(), 'USER_NOT_PRESENT')
            ->willReturn($wallet);

        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('closeUser')
            ->with($mangopayUser);

        $this->salesforceServiceMock
            ->expects($this->once())
            ->method('delete')
            ->with('Contact', $salesforceId)
            ->willReturn(new Response(204));

        $expected = [
            AccountCleanupAction::Mangopay,
            AccountCleanupAction::Salesforce,
        ];
        $actual = $this->service->cleanupData($user, $expected);
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testCleanupDataUserMangopayNonEmptyWallet(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setMangoPayUserId('user_m_test_' . bin2hex(random_bytes(8)));
        $user->setMangoPayWalletId('wallet_m_test_' . bin2hex(random_bytes(8)));
        $user->setEnabled(false);
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));

        // Mangopay user must be active
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock
            ->expects($this->atLeastOnce())
            ->method('getScaUser')
            ->with($user->getMangoPayUserId())
            ->willReturn($mangopayUser);

        // Wallet not empty
        $wallet = new Wallet();
        $money = new Money();
        $money->Amount = 1;
        $wallet->Balance = $money;
        $this->mangopayWalletServiceMock
            ->expects($this->once())
            ->method('getWallet')
            ->with($user->getMangoPayWalletId(), 'USER_NOT_PRESENT')
            ->willReturn($wallet);

        $this->mangopayWalletServiceMock->expects($this->never())->method('closeUser');

        $this->salesforceServiceMock->expects($this->never())->method('delete');

        $actual = $this->service->cleanupData($user, [AccountCleanupAction::Mangopay]);
        $this->assertEmpty($actual);
    }

    public function testCleanupDataUserExternalMissingIds(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setEnabled(false);
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));

        // Mangopay user must be active
        $mangopayUser = new UserNaturalSca();
        $mangopayUser->UserStatus = 'ACTIVE';
        $this->mangopayWalletServiceMock->expects($this->never())->method('getScaUser');

        $this->mangopayWalletServiceMock->expects($this->never())->method('getWallet');

        $this->mangopayWalletServiceMock->expects($this->never())->method('closeUser');

        $this->salesforceServiceMock->expects($this->never())->method('delete');

        $actual = $this->service->cleanupData($user, [
            AccountCleanupAction::Mangopay,
            AccountCleanupAction::Salesforce,
        ]);
        $this->assertEmpty($actual);
    }

    public function testToggleAccountBlock(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5784);
        $user->setEnabled(true);
        $user->setLifecycleStatus(UserLifecycle::STATE_EMAIL_NOT_VERIFIED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Pending));

        // Block account
        $this->service->toggleAccountBlock($user);
        $this->assertFalse($user->isEnabled());
        $this->assertEquals(UserLifecycle::STATE_BLOCKED, $user->getLifecycleStatus());
        $this->assertEquals(UserStatus::Closed, $user->getCurrentStatus());

        // Unblock the account - should revert to previous active status
        $this->service->toggleAccountBlock($user);
        $this->assertTrue($user->isEnabled());
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->assertEquals(UserStatus::Pending, $user->getCurrentStatus());

        // Move status along to email verified user
        $user->setLifecycleStatus(UserLifecycle::STATE_EMAIL_VERIFIED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Active));
        $this->service->toggleAccountBlock($user);
        $this->assertFalse($user->isEnabled());
        $this->assertEquals(UserLifecycle::STATE_BLOCKED, $user->getLifecycleStatus());
        $this->assertEquals(UserStatus::Closed, $user->getCurrentStatus());
        $this->service->toggleAccountBlock($user);
        $this->assertTrue($user->isEnabled());
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->assertEquals(UserStatus::Active, $user->getCurrentStatus());

        // Move status along to approved user
        $user->setLifecycleStatus(UserLifecycle::STATE_APPROVED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Active));
        $this->service->toggleAccountBlock($user);
        $this->assertFalse($user->isEnabled());
        $this->assertEquals(UserLifecycle::STATE_BLOCKED, $user->getLifecycleStatus());
        $this->assertEquals(UserStatus::Closed, $user->getCurrentStatus());
        $this->service->toggleAccountBlock($user);
        $this->assertTrue($user->isEnabled());
        $this->assertEquals(UserLifecycle::STATE_APPROVED, $user->getLifecycleStatus());
        $this->assertEquals(UserStatus::Active, $user->getCurrentStatus());
    }
}
