<?php

namespace App\Test;

use App\Service\ContegoService;
use App\Service\MangoPay;
use App\Service\SalesforceService;
use App\Test\FixtureWebTestCase;
use MangoPay\Address;
use MangoPay\BankAccount;
use MangoPay\BankAccountDetailsGB;
use MangoPay\BankAccountDetailsIBAN;
use MangoPay\Card;
use MangoPay\CardRegistration;
use MangoPay\KycDocument;
use MangoPay\LegalRepresentative;
use MangoPay\Libraries\Error;
use MangoPay\Libraries\ResponseException;
use MangoPay\Money;
use MangoPay\PayIn;
use MangoPay\PayInExecutionDetailsDirect;
use MangoPay\PayInExecutionDetailsWeb;
use MangoPay\PayInPaymentDetailsBankWire;
use MangoPay\PayInPaymentDetailsCard;
use MangoPay\PayOut;
use MangoPay\PayOutPaymentDetailsBankWire;
use MangoPay\Transaction;
use MangoPay\TransactionStatus;
use MangoPay\Transfer;
use MangoPay\UserLegalSca;
use MangoPay\UserNaturalSca;
use MangoPay\Wallet;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Provide helpers to mock external services including
 * - Mangopay
 * - Contego/Northrow
 * - Salesforce
 *
 * Note that many of the behind the scenes helpers are not available to tests
 * Only the helpers for creating service mocks
 */
abstract class ExternalServiceWebTestCase extends FixtureWebTestCase
{
    protected const KYC_TEST_SCORE = '798255'; // specific number that isn't in fixtures to avoid clash

    // Supported mocks - note that the value doesn't matter
    // They're mainly for readability so you know what each mock type does
    // Think of them as like unbacked enums
    protected const MANGOPAY_CARD_REGISTRATION = 'createMangopayCardRegistrationMock';
    protected const MANGOPAY_CARD_VALIDATION = 'createMangopayCardValidationMock';
    protected const MANGOPAY_CARD_PAYIN = 'createMangopayCardpayIn';
    protected const MANGOPAY_CARD_WEB_PAYIN = 'createMangopayCardWebpayIn';
    protected const MANGOPAY_BANKWIRE_PAYIN = 'createMangopayBankwirePayIn';
    protected const MANGOPAY_KYC_CHECK = 'createMangopayKycCheckMock';
    protected const MANGOPAY_LIST_WALLETS = 'createMangopayListWalletsMock';
    protected const MANGOPAY_VIEW_WALLET = 'createMangopaySingleWalletMock';
    protected const MANGOPAY_VIEW_WALLET_SCA = 'createMangopaySingleWalletScaMock';
    protected const MANGOPAY_LIST_BANK_ACCOUNTS = 'createMangopayListBankAccountsMock';
    protected const MANGOPAY_LIST_TRANSACTIONS = 'createMangopayListTransactionsMock';
    protected const MANGOPAY_LIST_TRANSACTIONS_SCA = 'createMangopayListTransactionsScaMock';
    protected const MANGOPAY_VIEW_PAYIN = 'createMangopaySinglePayinTransactionMock';
    protected const MANGOPAY_CREATE_WALLET = 'createMangopayCreateWalletMock';
    protected const MANGOPAY_CREATE_USER = 'createMangopayCreateWalletUserMock';
    protected const MANGOPAY_CREATE_USER_SCA = 'createMangopayCreateWalletUserScaMock';
    protected const MANGOPAY_CREATE_LEGAL_USER = 'createMangopayCreateWalletLegalUserMock';
    protected const MANGOPAY_CREATE_TRANSFER = 'createMangopayCreatetransfer';
    protected const MANGOPAY_CREATE_BANK_ACCOUNT_GB = 'createMangopayCreateBankAccountGBMock';
    protected const MANGOPAY_CREATE_BANK_ACCOUNT_IBAN = 'createMangopayCreateBankAccountIBANMock';
    protected const MANGOPAY_BANKWIRE_PAYOUT = 'createMangopayBankwirepayOut';
    protected const MANGOPAY_CARD_DEACTIVATE = 'updateMangopayCardDeactivate';

    protected const SALESFORCE_OBJECT_NAME = 'Contact'; // Name of the Salesforce object we sync with

    // Example new style Mangopay object IDs
    protected const TEST_MP_ID_USER = 'user_m_01HPPATRNW7EB1S3JFMJQKYXZ4';
    protected const TEST_MP_ID_WALLET = 'wlt_m_01HPPATRXPXEJ9A9F323XDHS3G';
    protected const TEST_MP_ID_PAYIN = 'payin_m_01HPSDZKQMVSJN6JF4H04WY9XR';
    protected const TEST_MP_ID_PAYOUT = 'po_m_01HPRXV8P8W12WP5M34XKJX28A';
    protected const TEST_MP_ID_CARD = 'card_m_01HPSEB84W7S80A9BNGKFNSTNG';

    protected function useContegoServiceMock(array $mockResponses = []): void
    {
        // If no mocked reponses given, use a default one configured to GREEN RAG with test specific score
        if (empty($mockResponses)) {
            $mockResponses = [
                [
                    'contegoScore' => [
                        'score' => self::KYC_TEST_SCORE,
                        'rag' => 'GREEN',
                        'alert' => [
                            ['message' => 'alert1'],
                            ['message' => 'alert2'],
                        ],
                    ],
                    'header' => [
                        'requestRef' => 'xyz123',
                        'pdfreport' => 'http://abc',
                    ],
                ],
            ];
        }

        // Create the mock service
        $contegoServiceMock = $this
            ->getMockBuilder(\App\Service\ContegoService::class)
            ->setConstructorArgs([
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Service\DocumentService::class),
                null,
                null,
                null,
                null,
                null,
                null,
            ])
            // Note that you can't disable the constructor as the logger is still used in the service
            // ->disableOriginalConstructor()
            ->onlyMethods(['getContegoResponse'])
            ->getMock();

        // Create the Guzzle responses
        $mockHttpResponses = [];
        foreach ($mockResponses as $mockKycResponse) {
            $mockHttpResponses[] = new \GuzzleHttp\Psr7\Response(
                \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                ['Content-Type' => 'application/json'],
                json_encode($mockKycResponse),
            );
        }
        // Configure the mocked method behaviour
        $contegoServiceMock
            ->expects($this->exactly(count($mockHttpResponses)))
            ->method('getContegoResponse')
            ->willReturn(...$mockHttpResponses);

        // Symfony will reboot the kernel between requests by default
        // Disable the kernel reboot between requests so we keep our mock for the duration of the (single) test
        $this->client->disableReboot();

        // Override the ContegoService with our mock in the service container
        static::getContainer()->set(ContegoService::class, $contegoServiceMock);
    }

    protected function useSalesforceServiceMock(): void
    {
        $authResponse = new \GuzzleHttp\Psr7\Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"access_token":"sf-acc-tok","instance_url":"some-url"}',
        );
        $retrieveResponse = new \GuzzleHttp\Psr7\Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"attributes": {"type": "'
            . self::SALESFORCE_OBJECT_NAME
            . '","url": "object-endpoint-url"}}',
        );
        $createResponse = new \GuzzleHttp\Psr7\Response(
            201,
            ['Content-Type' => 'application/json'],
            '{"id":"MyABCDEId","success": true,"errors": []}',
        );
        $updateResponse = new \GuzzleHttp\Psr7\Response(204, []);

        $saleforceServiceMock = $this
            ->getMockBuilder(SalesforceService::class)
            ->setConstructorArgs(['id', 'secret', 'token'])
            ->onlyMethods([
                'requestSalesforceAuthentication',
                'requestSalesforceUserRetrieve',
                'requestSalesforceUserCreate',
                'requestSalesforceUserUpdate',
                'requestSalesforceUserDelete',
            ])
            ->getMock();
        $saleforceServiceMock
            ->expects($this->any())
            ->method('requestSalesforceAuthentication')
            ->willReturn($authResponse);
        $saleforceServiceMock
            ->expects($this->any())
            ->method('requestSalesforceUserRetrieve')
            ->willReturn($retrieveResponse);
        $saleforceServiceMock
            ->expects($this->any())
            ->method('requestSalesforceUserCreate')
            ->willReturn($createResponse);
        $saleforceServiceMock
            ->expects($this->any())
            ->method('requestSalesforceUserUpdate')
            ->willReturn($updateResponse);
        $saleforceServiceMock
            ->expects($this->any())
            ->method('requestSalesforceUserDelete')
            ->willReturn($updateResponse);

        // Symfony will reboot the kernel between requests by default
        // Disable the kernel reboot between requests so we keep our mock for the duration of the (single) test
        $this->client->disableReboot();

        // Override the SalesforceService with our mock in the service container
        static::getContainer()->set(SalesforceService::class, $saleforceServiceMock);
    }

    protected function useMangopayServiceMock(string $mockType): void
    {
        // Note that the match structure requires PHP 8.0 to work
        $mangopayServiceMock = match ($mockType) {
            self::MANGOPAY_CARD_REGISTRATION
                => $this->createMangopayCardRegistrationMock(),
            self::MANGOPAY_CARD_VALIDATION => $this->createMangopayCardValidationMock(),
            self::MANGOPAY_CARD_PAYIN => $this->createMangopayCardpayIn('CARD'),
            self::MANGOPAY_CARD_WEB_PAYIN => $this->createMangopayCardpayIn('CARD_WEB'),
            self::MANGOPAY_BANKWIRE_PAYIN => $this->createMangopayBankwirePayIn(),
            self::MANGOPAY_KYC_CHECK => $this->createMangopayKycCheckMock(),
            self::MANGOPAY_LIST_WALLETS => $this->createMangopayListWalletsMock(),
            self::MANGOPAY_VIEW_WALLET => $this->createMangopaySingleWalletMock(),
            self::MANGOPAY_VIEW_WALLET_SCA
                => $this->createMangopaySingleWalletScaMock(),
            self::MANGOPAY_LIST_BANK_ACCOUNTS
                => $this->createMangopayListBankAccountsMock(),
            self::MANGOPAY_VIEW_PAYIN
                => $this->createMangopaySinglePayinTransactionMock(),
            self::MANGOPAY_LIST_TRANSACTIONS
                => $this->createMangopayListTransactionsMock(),
            self::MANGOPAY_LIST_TRANSACTIONS_SCA
                => $this->createMangopayListTransactionsScaMock(),
            self::MANGOPAY_CREATE_WALLET => $this->createMangopayCreateWalletMock(),
            self::MANGOPAY_CREATE_USER_SCA => $this->createMangopayCreateWalletUserMock(
                type: 'naturalSca',
            ),
            self::MANGOPAY_CREATE_LEGAL_USER
                => $this->createMangopayCreateWalletUserMock('legal'),
            self::MANGOPAY_CREATE_TRANSFER => $this->createMangopayCreatetransfer(),
            self::MANGOPAY_CREATE_BANK_ACCOUNT_GB
                => $this->createMangopayCreateBankAccountMock('GB'),
            self::MANGOPAY_CREATE_BANK_ACCOUNT_IBAN
                => $this->createMangopayCreateBankAccountMock('IBAN'),
            self::MANGOPAY_BANKWIRE_PAYOUT => $this->createMangopayBankwirePayOut(),
            self::MANGOPAY_CARD_DEACTIVATE => $this->updateMangopayCard(false),
            default => $this->fail(
                "Mangopay service mock of {$mockType} not supported.",
            ),
        };

        // Disable the kernel reboot between requests so we keep our mock for the duration of the (single) test
        $this->client->disableReboot();

        // Override the MangoPayService with our mock in the service container
        static::getContainer()->set(MangoPay::class, $mangopayServiceMock);
    }

    /**
     * Available to tests for any custom Mangopay service mocks
     *
     * @param array $methods - Define any methods you want to mock and customise behaviour of
     */
    protected function createMangopayServiceMock(array $methods): MockObject
    {
        $mangopayServiceMock = $this
            ->getMockBuilder(MangoPay::class)
            ->setConstructorArgs([
                static::getContainer()->get(\Psr\Log\LoggerInterface::class),
                static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
                static::getContainer()->get(\App\Service\Manager\AssetManager::class),
                static::getContainer()->get(\App\Service\DocumentService::class),
                static::getContainer()->get(\App\Service\MailerService::class),
                null,
                null,
                null,
                '',
                '',
                '',
                'sandbox',
            ])
            ->onlyMethods($methods)
            ->getMock();
        return $mangopayServiceMock;
    }

    private function createMangopayCardRegistrationMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayCardRegistrationCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayCardRegistrationCreate')
            ->willReturn($this->createMangopayCardRegistration());

        return $mangopayServiceMock;
    }

    private function createMangopayCardValidationMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayCardRegistrationCreate',
            'executeMangopayCardRegistrationUpdate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayCardRegistrationCreate')
            ->willReturn($this->createMangopayCardRegistration('CREATED'));
        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayCardRegistrationUpdate')
            ->willReturn($this->createMangopayCardRegistration('VALIDATED'));
        return $mangopayServiceMock;
    }

    private function createMangopayCardpayIn(string $type): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayPayInsCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayPayInsCreate')
            ->willReturn($this->createMangopayPayin($type));

        return $mangopayServiceMock;
    }

    private function createMangopayBankwirePayIn(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayPayInsCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayPayInsCreate')
            ->willReturn($this->createMangopayPayin('BANK_WIRE'));

        return $mangopayServiceMock;
    }

    private function createMangopayKycCheckMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayUsersCreateKycDocument',
            'executeCreateKycPage',
            'executeUpdateKycDocument',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayUsersCreateKycDocument')
            ->willReturn($this->createMangopayKycDocument('CREATED'));

        //This request to mangopay returns null
        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeCreateKycPage')
            ->willReturn(null);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeUpdateKycDocument')
            ->willReturn($this->createMangopayKycDocument('VALIDATION_ASKED'));

        return $mangopayServiceMock;
    }

    private function createMangopaySingleWalletMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangoPayViewWallet',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangoPayViewWallet')
            ->willReturn($this->createMangopayWallet());

        return $mangopayServiceMock;
    }

    private function createMangopaySingleWalletScaMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangoPayViewWallet',
        ]);

        $errorInfo = new Error();
        $errorInfo->Message = 'SCA required';
        $errorInfo->Errors = ['Sca' => 'SCA required to perform this action.'];
        $errorInfo->Data = [
            'RedirectUrl' => 'https://sca.sandbox.mangopay.com/?token=sca_example_token',
        ];
        $responseException = new ResponseException(
            'test_placeholder_not_used',
            401,
            $errorInfo,
        );

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangoPayViewWallet')
            ->willThrowException($responseException);

        return $mangopayServiceMock;
    }

    private function createMangopayListWalletsMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangoPayUserGetWallets',
            'executeMangoPayViewWallet',
        ]);

        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangoPayUserGetWallets')
            ->willReturn([$this->createMangopayWallet()]);
        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangoPayViewWallet')
            ->willReturn($this->createMangopayWallet());

        return $mangopayServiceMock;
    }

    private function createMangopayListBankAccountsMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayUsersGetBankAccounts',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayUsersGetBankAccounts')
            ->willReturn([$this->createMangopayBankAccount()]);

        return $mangopayServiceMock;
    }

    private function createMangopaySinglePayinTransactionMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayPayInGet',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayPayInGet')
            ->willReturn($this->createMangopayPayinTransaction());

        return $mangopayServiceMock;
    }

    private function createMangopayListTransactionsMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayUsersGetTransactionsPage',
            'executeMangopayUsersGetTransactionsMulti',
        ]);

        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangopayUsersGetTransactionsPage')
            ->willReturn([
                $this->createMangopayTransaction(),
                $this->createMangopayTransaction(),
            ]);

        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangopayUsersGetTransactionsMulti')
            ->willReturn([$this->createMangopayTransaction('SUCCEEDED', 100)]);

        return $mangopayServiceMock;
    }

    private function createMangopayListTransactionsScaMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayUsersGetTransactionsPage',
            'executeMangopayUsersGetTransactionsMulti',
        ]);

        $errorInfo = new Error();
        $errorInfo->Message = 'SCA required';
        $errorInfo->Errors = ['Sca' => 'SCA required to perform this action.'];
        $errorInfo->Data = [
            'RedirectUrl' => 'https://sca.sandbox.mangopay.com/?token=sca_example_token',
        ];
        $responseException = new ResponseException(
            'test_placeholder_not_used',
            401,
            $errorInfo,
        );

        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangopayUsersGetTransactionsPage')
            ->willThrowException($responseException);

        $mangopayServiceMock
            ->expects($this->atMost(1))
            ->method('executeMangopayUsersGetTransactionsMulti')
            ->willThrowException($responseException);

        return $mangopayServiceMock;
    }

    private function createMangopayCreateWalletMock(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangoPayWalletCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangoPayWalletCreate')
            ->willReturn($this->createMangopayWallet());

        return $mangopayServiceMock;
    }

    private function createMangopayCreateWalletUserMock(string $type): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangoPayUserCreate',
        ]);

        if ('legal' === $type) {
            $mangopayServiceMock
                ->expects($this->once())
                ->method('executeMangoPayUserCreate')
                ->willReturn($this->createMangopayLegalUser());
        } else {
            $mangopayServiceMock
                ->expects($this->once())
                ->method('executeMangoPayUserCreate')
                ->willReturn($this->createMangopayUserSca());
        }

        return $mangopayServiceMock;
    }

    private function createMangopayCreatetransfer(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayTransfersCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayTransfersCreate')
            ->willReturn($this->createMangopayTransfer());

        return $mangopayServiceMock;
    }

    private function createMangopayCreateBankAccountMock(string $type): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayBankAccountCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayBankAccountCreate')
            ->willReturn($this->createMangopayBankAccount($type));

        return $mangopayServiceMock;
    }

    private function createMangopayBankwirePayout(): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayPayoutCreate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayPayoutCreate')
            ->willReturn($this->createMangopayPayout());

        return $mangopayServiceMock;
    }

    private function updateMangopayCard(bool $isActive = true): MockObject
    {
        $mangopayServiceMock = $this->createMangopayServiceMock([
            'executeMangopayCardUpdate',
        ]);

        $mangopayServiceMock
            ->expects($this->once())
            ->method('executeMangopayCardUpdate')
            ->willReturn($this->createMangopayCard($isActive));

        return $mangopayServiceMock;
    }

    private function createMangopayCardRegistration(string $status = 'CREATED'): CardRegistration
    {
        $cardRegistration = new CardRegistration();
        $cardRegistration->UserId = '18465502';
        $cardRegistration->CardType = 'CB_VISA_MASTERCARD';
        $cardRegistration->AccessKey = '1X0m87dmM2LiwFgxPLBJ';
        $cardRegistration->PreregistrationData = 'fztL6okJyT8dJpVcSz7IN65aCWlh8lkipuTsC2uJOhYH8fVpGVVNAD2sFheZFFVq7qGR3aNxrLUiPbx-Z--VxQ';
        $cardRegistration->CardRegistrationURL = 'https://homologation-webpayment.payline.com/webpayment/getToken';
        $cardRegistration->CardId = null;
        $cardRegistration->RegistrationData = null;
        $cardRegistration->ResultCode = null;
        $cardRegistration->ResultMessage = null;
        $cardRegistration->Currency = 'GBP';
        $cardRegistration->Status = $status;
        $cardRegistration->Id = '19148323';
        $cardRegistration->Tag = null;
        $cardRegistration->CreationDate = 1482976545;
        return $cardRegistration;
    }

    private function createMangopayCard(bool $isActive = true): Card
    {
        $cardMock = new Card();
        $cardMock->ExpirationDate = '0119';
        $cardMock->Alias = '470675XXXXXX0009';
        $cardMock->CardProvider = 'VISA';
        $cardMock->UserId = '18465155';
        $cardMock->CardType = 'CB_VISA_MASTERCARD';
        $cardMock->Product = 'G';
        $cardMock->BankCode = 'unknown';
        $cardMock->Country = 'RUS';
        $cardMock->Currency = 'GBP';
        $cardMock->Validity = 'UNKNOWN';
        $cardMock->Id = '55588959';
        $cardMock->Tag = null;
        $cardMock->CreationDate = 1538499108;
        $cardMock->Active = $isActive;
        return $cardMock;
    }

    private function createMangopayPayin(string $type = 'CARD'): PayIn
    {
        $payIn = new PayIn();
        $payIn->CreditedWalletId = '19497822';
        $payIn->ExecutionType = 'DIRECT';
        $payIn->AuthorId = '19497819';
        $payIn->CreditedUserId = '19497819';
        $payIn->ResultCode = '000000';
        $payIn->ResultMessage = 'Success';
        $payIn->ExecutionDate = null;
        $payIn->Type = 'PAYIN';
        $payIn->Nature = 'REGULAR';
        $payIn->DebitedWalletId = null;
        $payIn->Id = '19497833';
        $payIn->Tag = null;
        $payIn->CreationDate = '1483156828';

        if ('CARD' == $type) {
            $payIn->PaymentType = 'CARD';
            $payIn->PaymentDetails = new PayInPaymentDetailsCard();
            $payIn->PaymentDetails->CardType = null;
            $payIn->PaymentDetails->CardId = '19497831';
            $payIn->PaymentDetails->StatementDescriptor = null;
            $payIn->Status = 'SUCCEEDED';
            $payIn->DebitedFunds = new Money();
            $payIn->DebitedFunds->Amount = 3000;
            $payIn->DebitedFunds->Currency = 'GBP';
            $payIn->CreditedFunds = new Money();
            $payIn->CreditedFunds->Amount = 6500;
            $payIn->CreditedFunds->Currency = 'GBP';
            $payIn->Fees = new Money();
            $payIn->Fees->Amount = 500;
            $payIn->Fees->Currency = 'GBP';
        } else {
            $payIn->PaymentType = 'BANK_WIRE';
            $payIn->Status = 'CREATED';

            $payIn->DebitedFunds = null;
            $payIn->CreditedFunds = null;
            $payIn->Fees = null;
            $payIn->PaymentDetails = new PayInPaymentDetailsBankWire();
            $payIn->PaymentDetails->DeclaredDebitedFunds = new Money();
            $payIn->PaymentDetails->DeclaredDebitedFunds->Amount = 10000;
            $payIn->PaymentDetails->DeclaredDebitedFunds->Currency = 'GBP';
            $payIn->PaymentDetails->DeclaredFees = new Money();
            $payIn->PaymentDetails->DeclaredFees->Amount = 100;
            $payIn->PaymentDetails->DeclaredFees->Currency = 'GBP';
            $payIn->PaymentDetails = new PayInPaymentDetailsBankWire();
            $payIn->PaymentDetails->BankAccount = '';
            $payIn->PaymentDetails->WireReference = 'b8758954d9';
            $payIn->PaymentDetails->BankAccount = new BankAccount();
            $payIn->PaymentDetails->BankAccount->UserId = '';
            $payIn->PaymentDetails->BankAccount->Type = 'IBAN';
            $payIn->PaymentDetails->BankAccount->OwnerName = 'Leetchi Corp SA';
            $payIn->PaymentDetails->BankAccount->OwnerAddress = '';
            $payIn->PaymentDetails->BankAccount->Active = null;
            $payIn->PaymentDetails->BankAccount->Id = null;
            $payIn->PaymentDetails->BankAccount->Tag = null;
            $payIn->PaymentDetails->BankAccount->CreationDate = null;
            $payIn->PaymentDetails->BankAccount->OwnerAddress = new Address();
            $payIn->PaymentDetails->BankAccount->OwnerAddress->AddressLine1 = 'leetchi loop';
            $payIn->PaymentDetails->BankAccount->OwnerAddress->AddressLine2 = null;
            $payIn->PaymentDetails->BankAccount->OwnerAddress->City = 'paris';
            $payIn->PaymentDetails->BankAccount->OwnerAddress->Region = 'paris region';
            $payIn->PaymentDetails->BankAccount->OwnerAddress->PostalCode = '75009';
            $payIn->PaymentDetails->BankAccount->OwnerAddress->Country = 'FR';
            $payIn->PaymentDetails->BankAccount->Details = new BankAccountDetailsIBAN();
            $payIn->PaymentDetails->BankAccount->Details->IBAN = 'LU320141444892503030';
            $payIn->PaymentDetails->BankAccount->Details->BIC = 'CELLLULL';
        }
        if ('CARD_WEB' == $type) {
            $payIn->ExecutionDetails = new PayInExecutionDetailsWeb();
            $payIn->ExecutionDetails->RedirectURL = 'http://localhost';
            $payIn->ExecutionDetails->ReturnURL = 'https://exameple.com';
            $payIn->ExecutionDetails->SecureMode = 'DEFAULT';
            $payIn->ExecutionDetails->Culture = 'EN';
        } else {
            $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
            $payIn->ExecutionDetails->SecureMode = 'DEFAULT';
            $payIn->ExecutionDetails->SecureModeRedirectURL = null;
            $payIn->ExecutionDetails->SecureModeNeeded = false;
            $payIn->ExecutionDetails->SecureModeReturnURL = null;
        }
        return $payIn;
    }

    private function createMangopayKycDocument(string $status = 'CREATED'): KycDocument
    {
        $kycDocument = new KycDocument();
        $kycDocument->Type = 'IDENTITY_PROOF';
        $kycDocument->Status = $status;
        $kycDocument->UserId = '19638491';
        $kycDocument->RefusedReasonType = null;
        $kycDocument->RefusedReasonMessage = null;
        $kycDocument->Id = '19638492';
        $kycDocument->CreationDate = 1483668801;
        $kycDocument->Tag = null;
        return $kycDocument;
    }

    private function createMangopayWallet(): Wallet
    {
        $money = new Money();
        $money->Amount = 1521;
        $money->Currency = 'GBP';
        $wallet = new Wallet();
        $wallet->Id = '82820505';
        $wallet->Tag = 'Mock example Tag';
        $wallet->CreationDate = '';
        $wallet->Description = 'Mock example wallet';
        $wallet->Balance = $money;
        return $wallet;
    }

    private function createMangopayPayinTransaction(): Payin
    {
        $money = new Money();
        $money->Amount = 1521;
        $money->Currency = 'GBP';
        $payin = new PayIn();
        // Example payin belonging to Ben user's wallet
        $payin->Id = 'wt_0f44a630-454d-45ae-8de2-2389ea38f7bb';
        // Wallet Id of Ben test user
        $payin->CreditedWalletId = 'wlt_m_01HW3FBRBZF8ZMEF8WHPRA21NZ';
        $payin->Tag = 'Mock example Tag';
        $payin->CreationDate = '';
        $payin->Status = TransactionStatus::Succeeded;
        $payin->CreditedFunds = $money;
        return $payin;
    }

    private function createMangopayBankAccount(string $accountType = 'GB'): BankAccount
    {
        $bankAccount = new BankAccount();
        $bankAccount->UserId = '18985011';
        $bankAccount->OwnerName = 'Jon Doe';
        $bankAccount->Active = true;
        $bankAccount->Id = '19574050';
        $bankAccount->Tag = null;
        $bankAccount->CreationDate = 1483522916;
        $bankAccount->OwnerAddress = new Address();
        $bankAccount->OwnerAddress->AddressLine1 = '1 London Road';
        $bankAccount->OwnerAddress->AddressLine2 = '';
        $bankAccount->OwnerAddress->City = 'London';
        $bankAccount->OwnerAddress->Region = '';
        $bankAccount->OwnerAddress->PostalCode = 'E1 1RD';
        $bankAccount->OwnerAddress->Country = 'GB';
        // account numbers example from https://wise.com/gb/iban/uk
        if ('IBAN' == $accountType) {
            $bankAccount->Type = 'IBAN';
            $bankAccount->Details = new BankAccountDetailsIBAN();
            $bankAccount->Details->IBAN = 'GB29NWBK60161331926819';
            $bankAccount->Details->BIC = 'NWBK';
        } else {
            // Use GB as the default
            $bankAccount->Type = 'GB';
            $bankAccount->Details = new BankAccountDetailsGB();
            $bankAccount->Details->AccountNumber = '31926819';
            $bankAccount->Details->SortCode = '601613';
        }
        return $bankAccount;
    }

    private function createMangopayTransaction(
        string $status = 'SUCCEEDED',
        int $creationDateOffset = 0,
    ): Transaction {
        $transaction = new Transaction();
        $transaction->DebitedWalletId = '18985011';
        $transaction->CreditedUserId = '19551823';
        $transaction->AuthorId = '18985011';
        $transaction->CreditedUserId = '19551816';
        $transaction->Status = $status;
        $transaction->ResultCode = '000000';
        $transaction->ResultMessage = 'Success';
        $transaction->ExecutionDate = 1483462380;
        $transaction->Type = 'PAYIN';
        $transaction->Nature = 'REGULAR';
        $transaction->Id = '19551835';
        $transaction->Tag = 'Some TAG';
        $transaction->CreationDate = 1483462380 + $creationDateOffset;
        $transaction->DebitedFunds = new Money();
        $transaction->DebitedFunds->Amount = 2000;
        $transaction->DebitedFunds->Currency = 'GBP';
        $transaction->CreditedFunds = new Money();
        $transaction->CreditedFunds->Amount = 1900;
        $transaction->CreditedFunds->Currency = 'GBP';
        $transaction->Fees = new Money();
        $transaction->Fees->Amount = 100;
        $transaction->Fees->Currency = 'GBP';
        return $transaction;
    }

    private function createMangopayUserSca(): UserNaturalSca
    {
        $userAddress = new Address();
        $userAddress->AddressLine1 = '';
        $userAddress->AddressLine2 = '';
        $userAddress->City = '';
        $userAddress->Region = '';
        $userAddress->PostalCode = '';
        $userAddress->Country = '';

        $walletNaturalUser = new UserNaturalSca();
        $walletNaturalUser->Id = '18985011';
        $walletNaturalUser->FirstName = 'mango';
        $walletNaturalUser->LastName = 'pay';
        $walletNaturalUser->Birthday = 899560697;
        $walletNaturalUser->Nationality = 'GB';
        $walletNaturalUser->CountryOfResidence = 'GB';
        $walletNaturalUser->Occupation = '';
        $walletNaturalUser->IncomeRange = '';
        $walletNaturalUser->ProofOfIdentity = '';
        $walletNaturalUser->ProofOfAddress = '';
        $walletNaturalUser->PersonType = 'NATURAL';
        $walletNaturalUser->Email = 'mangopay@test.co';
        $walletNaturalUser->KYCLevel = 'LIGHT';
        $walletNaturalUser->Tag = '';
        $walletNaturalUser->CreationDate = 1482541182;
        $walletNaturalUser->Address = $userAddress;
        $walletNaturalUser->PhoneNumber = '+447111222333';

        $walletNaturalUser->UserCategory = 'Owner';
        $walletNaturalUser->TermsAndConditionsAccepted = true;

        return $walletNaturalUser;
    }

    private function createMangopayLegalUser(): UserLegalSca
    {
        $hqAddress = new Address();
        $hqAddress->AddressLine1 = '';
        $hqAddress->AddressLine2 = '';
        $hqAddress->City = '';
        $hqAddress->Region = '';
        $hqAddress->PostalCode = '';
        $hqAddress->Country = '';

        $walletLegalUser = new UserLegalSca();
        $legalRepresentative = new LegalRepresentative();
        $walletLegalUser->Id = 18722014;
        $walletLegalUser->LegalPersonType = 'ORGANIZATION';
        $walletLegalUser->Email = 'test@mail.co';
        $legalRepresentative->CountryOfResidence = 'GB';
        $legalRepresentative->FirstName = 'Investor';
        $legalRepresentative->LastName = 'Natural1_MangoPay';
        $legalRepresentative->Nationality = 'GB';

        $walletLegalUser->LegalRepresentative = $legalRepresentative;

        $walletLegalUser->HeadquartersAddress = $hqAddress;

        $walletLegalUser->UserCategory = 'Owner';
        $walletLegalUser->TermsAndConditionsAccepted = true;
        return $walletLegalUser;
    }

    private function createMangopayTransfer(): Transfer
    {
        $transfer = new Transfer();
        $transfer->DebitedWalletId = '19551819';
        $transfer->CreditedWalletId = '19551823';
        $transfer->AuthorId = '19551816';
        $transfer->CreditedUserId = '19551816';
        $transfer->Status = 'SUCCEEDED';
        $transfer->ResultCode = '000000';
        $transfer->ResultMessage = 'Success';
        $transfer->ExecutionDate = 1483462380;
        $transfer->Type = 'TRANSFER';
        $transfer->Nature = 'REGULAR';
        $transfer->Id = '19551835';
        $transfer->Tag = 'Some TAG';
        $transfer->CreationDate = '1483462380';
        $transfer->DebitedFunds = new Money();
        $transfer->DebitedFunds->Amount = 1000;
        $transfer->DebitedFunds->Currency = 'GBP';
        $transfer->CreditedFunds = new Money();
        $transfer->CreditedFunds->Amount = 900;
        $transfer->CreditedFunds->Currency = 'GBP';
        $transfer->Fees = new Money();
        $transfer->Fees->Amount = 100;
        $transfer->Fees->Currency = 'GBP';
        return $transfer;
    }

    private function createMangopayPayout(): PayOut
    {
        $payOut = new PayOut();
        $payOut->DebitedWalletId = '19588279';
        $payOut->PaymentType = 'BANK_WIRE';
        $payOut->AuthorId = '19588297';
        $payOut->CreditedUserId = null;
        $payOut->DebitedFunds = '';
        $payOut->Status = 'CREATED';
        $payOut->ResultCode = null;
        $payOut->ResultMessage = null;
        $payOut->ExecutionDate = null;
        $payOut->Type = 'PAYOUT';
        $payOut->Nature = 'REGULAR';
        $payOut->CreditedWalletId = null;
        $payOut->Id = '19588303';
        $payOut->Tag = null;
        $payOut->CreationDate = 1483544184;
        $payOut->MeanOfPaymentDetails = new PayOutPaymentDetailsBankWire();
        $payOut->MeanOfPaymentDetails->BankAccountId = '19588298';
        $payOut->MeanOfPaymentDetails->BankWireRef = 'Wire Payout';
        $payOut->DebitedFunds = new Money();
        $payOut->DebitedFunds->Amount = '5000';
        $payOut->DebitedFunds->Currency = 'GBP';
        $payOut->CreditedFunds = new Money();
        $payOut->CreditedFunds->Amount = '4900';
        $payOut->CreditedFunds->Currency = 'GBP';
        $payOut->Fees = new Money();
        $payOut->Fees->Amount = '100';
        $payOut->Fees->Currency = 'GBP';
        return $payOut;
    }
}
