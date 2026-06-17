<?php

namespace App\Service;

use App\Dto\CardPayinDTO;
use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Investment;
use App\Entity\Offering;
use App\Entity\TradeOrder;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Exception\ApiException;
use App\Service\DocumentService;
use App\Service\MailerService;
use App\Service\Manager\AssetManager;
use App\Service\Mangopay\MangopayClientFactory;
use App\Service\Util\Helper;
use Doctrine\ORM\EntityManagerInterface;
use MangoPay\Address;
use MangoPay\BankAccount;
use MangoPay\BankAccountDetailsGB;
use MangoPay\BankAccountDetailsIBAN;
use MangoPay\BrowserInfo;
use MangoPay\Card;
use MangoPay\CardRegistration;
use MangoPay\CurrencyIso;
use MangoPay\FilterBase;
use MangoPay\FilterTransactions;
use MangoPay\KycDocument;
use MangoPay\KycPage;
use MangoPay\LegalRepresentative;
use MangoPay\MangoPayApi;
use MangoPay\Money;
use MangoPay\Pagination;
use MangoPay\PayIn;
use MangoPay\PayInExecutionDetailsDirect;
use MangoPay\PayInExecutionDetailsWeb;
use MangoPay\PayInExecutionType;
use MangoPay\PayInPaymentDetailsBankWire;
use MangoPay\PayInPaymentDetailsCard;
use MangoPay\PayInPaymentDetailsDirectDebit;
use MangoPay\PayInPaymentType;
use MangoPay\PayOut;
use MangoPay\PayOutPaymentDetailsBankWire;
use MangoPay\SortDirection;
use MangoPay\Sorting;
use MangoPay\Transfer;
use MangoPay\UserLegalSca;
use MangoPay\UserNaturalSca;
use MangoPay\Wallet;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SameerShelavale\PhpCountriesArray\CountriesArray;
use UnexpectedValueException;

class MangoPay
{
    public const PERSONTYPE_NATURAL = 'NATURAL';
    public const PERSONTYPE_LEGAL = 'ORGANIZATION';
    public const DEFAULT_CURRENCY = 'GBP';
    public const DEFAULT_FEE = '0';
    public const DEFAULT_CARD_TYPE = 'CB_VISA_MASTERCARD';

    public const SCA_CONTEXT_OPTIONS = ['USER_PRESENT', 'USER_NOT_PRESENT'];

    protected MangoPayApi $api;

    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private AssetManager $assetManager,
        private DocumentService $documentService,
        private MailerService $mailerService,
        protected ?string $clientId,
        protected ?string $clientPassword,
        protected ?string $tempDir,
        private string $mangopayProdUrl,
        private string $mtlsCertB64,
        private string $mtlsKeyB64,
        private string $mangopayEnvironment = 'sandbox',
    ) {
        //Set the mangoPayApi
        $this->getMangoPayApi();
    }

    public function getMangoPayApi(): MangoPayApi
    {
        $this->api = new MangoPayApi();
        $this->api->Config->ClientId = $this->clientId;
        $this->api->Config->ClientPassword = $this->clientPassword;
        $this->api->Config->TemporaryFolder = $this->tempDir;

        if ($this->mangopayEnvironment == 'prod') {
            $this->api->Config->BaseUrl = $this->mangopayProdUrl;
        } else {
            $this->api->Config->BaseUrl =
                MangopayClientFactory::DEFAULT_MTLS_ENDPOINT_URL;
        }

        // Mutual TLS config
        $this->api->Config->ClientCertificateString = $this->mtlsCertB64;
        $this->api->Config->ClientCertificateKeyString = $this->mtlsKeyB64;

        return $this->api;
    }

    /**
     * @return \MangoPay\Client
     */
    public function getClient()
    {
        return $this->api->Clients->Get();
    }

    /**
     * @return \MangoPay\RateLimit[]
     */
    public function getRateLimits()
    {
        return $this->api->RateLimits ?? [];
    }

    /**
     * @param $countryData Can be either the country name (United Kingdom) or ISO country code (GB)
     * @return mixed Country code in ISO 3166-1 alpha-2 format
     * @throws \Exception
     */
    private function getCountryCode($countryData)
    {
        // This allows us to convert country name to ISO 3166-1 alpha-2 e.g. United Kingdom to GB
        // Maybe this should not be here ???
        $countriesByName = CountriesArray::get('name', 'alpha2'); // United Kingdom -> GB
        $countriesByCode = CountriesArray::get('alpha2', 'name'); // GB -> United Kingdom

        $this->logger->debug("Looking up country code for country [$countryData] ...");

        if (in_array($countryData, $countriesByName)) {
            //If we find the variable in countriesByCode (e.g. GB) it means we have the correct ISO value already
            //Under normal circumstances this should not be the case as from the form we get the country as 'United Kingdom'
            $countryCode = $countryData;
            $this->logger->debug(" ... Found country code [$countryCode] ...");
        } elseif (in_array($countryData, $countriesByCode)) {
            //We found a country code corresponding to the the country name given
            $countryCode = $countriesByName[$countryData];
            $this->logger->debug(" ... Found country code [$countryCode] ...");
        } else {
            // We couldn't find the country by name or code
            throw new \Exception(
                'Unable to translate ['
                . $countryData
                . '] into a valid ISO 3166-1 alpha-2 country code',
            );
        }

        return $countryCode;
    }

    /**
     * Creates a mangoPay Natural user
     *
     * @param $user
     * @throws \Exception
     */
    public function createNaturalUser(User $user)
    {
        $this->logger->debug('Attempting to create a Mangopay natural user ...');

        $this->isUserValidForMangoPay($user); // this throws an exception if not

        if ($user->getMangoPayUserId()) {
            // No need to create if the user already has a mangopay account
            $this->logger->debug('User already has a Mangopay account');
            // Get the existing Mangopay user and return it
            return $this->api->Users->GetSca($user->getMangoPayUserId());
        }

        // Will eventually be mandatory to use Sca version
        $mangoPayUser = new UserNaturalSca();
        $mangoPayUser->PhoneNumber = Helper::preparePhoneNumber($user->getPhone1());

        $mangoPayUser->PersonType = self::PERSONTYPE_NATURAL;
        $mangoPayUser->FirstName = $user->getFirstname();
        $mangoPayUser->LastName = $user->getLastname();
        $mangoPayUser->Birthday = $user->getBirthDate()->getTimestamp();
        $mangoPayUser->Nationality = $this->getCountryCode($user->getNationality());
        $mangoPayUser->CountryOfResidence = $this->getCountryCode(
            $user->getMainAddress()->getCountry(),
        );
        $mangoPayUser->Email = $user->getEmail();
        $mangoPayUser->Tag = $user->getId();
        $mangoPayUser->Occupation = $user->getJobTitle();
        $mangoPayUser->IncomeRange = $user->getIncomeRange();
        $mangoPayUser->UserCategory = 'Owner';
        $mangoPayUser->TermsAndConditionsAccepted = true;

        $mangoPayUser->Address = new Address();
        $mangoPayUser->Address->AddressLine1 = $user->getMainAddress()->getAddress1();
        $mangoPayUser->Address->AddressLine2 = $user->getMainAddress()->getAddress2();
        $mangoPayUser->Address->City = $user->getMainAddress()->getCity();
        $mangoPayUser->Address->Region = $user->getMainAddress()->getRegion();
        $mangoPayUser->Address->Country = $user->getMainAddress()->getCountry();
        $mangoPayUser->Address->PostalCode = $user->getMainAddress()->getPostCode();
        $mangoPayUser->Address->PostalCode = $user->getMainAddress()->getPostCode();

        $this->logger->debug(
            'Sending request data to Mangopay[' . json_encode($mangoPayUser) . ']',
        );

        // Try within the try-catch block first with the phone number set in case Mangopay reject invalid numbers
        try {
            $response = $this->executeMangoPayUserCreate($mangoPayUser);
        } catch (\Throwable $th) {
            $this->logger->warning(
                'Failed to create Mangopay SCA user with number, retrying without: '
                    . $th->getMessage(),
            );
            $mangoPayUser->PhoneNumber = null;
            // If this throws an error, it should be caught and handled further up in another service
            // User will have to enter their phone number from scratch during enrollment
            $response = $this->executeMangoPayUserCreate($mangoPayUser);
        }

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        // $user->setMangoPayUserId( $response->Id );
        // $this->em->merge( $user );
        // $this->em->flush();

        return $response;
    }

    /**
     * Creates a mangoPay Wallet for a user
     *
     * @param $user
     * @param $description - Optional tag value for the wallet
     */
    public function createUserWallet(
        User $user,
        $description = 'Yielders investor main wallet',
    ) {
        $this->logger->info('In createUserWallet');

        $this->isUserValidForMangoPay($user); // this will throw an exception if not
        $this->hasUserMangoPayId($user); // this will throw an exception if not

        if ($user->getMangoPayWalletId()) {
            // No need to create a wallet if the user already has one
            $this->logger->debug('User already has a Mangopay wallet');
            // Create a Mangopay wallet object with the id to be used by any followup responses
            $wallet = new Wallet();
            $wallet->Id = $user->getMangoPayWalletId();
            return $wallet;
        }

        $wallet = new Wallet();
        $wallet->Owners = [$user->getMangoPayUserId()];

        // @TODO Should these description/curreny and tag be set as constants and are they correct
        $wallet->Description = $description;
        $wallet->Currency = 'GBP';

        //Decided that tag should be CT userid
        //https://gitlab.helpmewithit.com:7055/yielders2/Phase2/issues/182

        $wallet->Tag = $user->getId();

        $this->logger->debug(
            'Mangopay request for createUserWallet = [' . json_encode($wallet) . ']',
        );

        $response = $this->executeMangoPayWalletCreate($wallet);

        $this->logger->debug(
            'Mangopay response for createUserWallet = [' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        //   $user->setMangoPayWalletId( $response->Id );
        //   $this->em->merge( $user );
        //   $this->em->flush();

        return $response;
    }

    /**
     * Make a transfer from a users mangopay wallet to an organisations mangopay wallet
     * @param User $user
     * @param $data
     */
    public function createTransfer($user, $data)
    {
        $this->logger->info('In createTransfer ');

        //check for missing data sent in via the API request
        $data = $this->checkData($data);

        //echo "amount= $data->amount\n";
        //echo "currency= $data->currency\n";
        //echo "fee = $data->fee_amount\n";
        //echo "user wallet= $data->user_wallet_id\n";
        //echo "asset wallet = $data->asset_wallet_id\n";
        //echo "user mango  = " . $user->getMangoPayUserId(). "\n";

        //Create money objects for debitiedFunds and fees and populate with the received data
        $debitedFunds = new Money();
        $debitedFunds->Amount = $data->amount;
        $debitedFunds->Currency = $data->currency;

        $fees = new Money();
        $fees->Amount = $data->fee_amount;
        $fees->Currency = $data->currency;
        //Create a transfer object and populate with the received data
        $transfer = new Transfer();
        $transfer->AuthorId = $user->getMangoPayUserId();
        $transfer->Tag =
            'Transfer: ' . $data->user_wallet_id . ' to ' . $data->org_wallet_id;
        $transfer->DebitedWalletId = $data->user_wallet_id;
        $transfer->CreditedWalletId = $data->org_wallet_id;
        $transfer->DebitedFunds = $debitedFunds;
        $transfer->Fees = $fees;
        //$transfer->CreditedUserId = $user->getMangoPayUserId();

        if (isset($data->asset_id)) {
            $asset = $this->assetManager->findAssetById($data->asset_id);
            $transfer->Tag =
                $transfer->Tag
                . ';AstName:'
                . $asset->getName()
                . ';AstCode:'
                . $asset->getCompanyNumber();
        }

        if (isset($data->transfer_type)) {
            $transfer->Tag = $transfer->Tag . ';Type:' . $data->transfer_type;
        }

        if (
            isset($data->sca_context)
            && in_array($data->sca_context, self::SCA_CONTEXT_OPTIONS)
        ) {
            $transfer->ScaContext = $data->sca_context;
        }

        $this->logger->debug(
            'Mangopay request for createTransfer = [' . json_encode($transfer) . ']',
        );

        $response = $this->executeMangopayTransfersCreate($transfer);

        $this->logger->debug(
            'Mangopay response for createTransfer = [' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Replaces createTransfer if using /offerings/{offeringId}/payments
     * as part of new SCA investment flow
     *
     * Offering must have a sell_investment linked to it
     */
    public function createRelistingFeeTransfer(
        Offering $offering,
        int|float|string $amount,
        ?bool $sca,
    ): Transfer {
        $asset = $offering->getAsset();
        $seller = $offering->getSellInvestment()->getUser();
        $metadata = 'Type:Relisting Fee';
        $transfer = $this->buildTransferObj(
            $seller->getMangoPayUserId(),
            $seller->getMangoPayWalletId(),
            $asset->getHoldWalletId(),
            $amount,
            0,
            $metadata,
        );
        if ($sca !== null) {
            $scaContext = $sca ? 'USER_PRESENT' : 'USER_NOT_PRESENT';
            $transfer->ScaContext = $scaContext;
            $this->logger->debug("Preparing transfer with SCA context {$scaContext}");
        }
        $this->logger->debug(
            'Mangopay request for createRelistingFeeTransfer = ['
            . json_encode($transfer)
            . ']',
        );
        $transferResponse = $this->executeMangopayTransfersCreate($transfer);
        $this->logger->debug(
            'Mangopay response for createRelistingFeeTransfer = ['
            . json_encode($transferResponse)
            . ']',
        );

        $this->handleMangopayResponse($transferResponse);

        return $transferResponse;
    }

    /**
     * Replaces createTransfer if using /investments/{investmentId}/payments
     * as part of new SCA investment flow
     */
    public function createInvestmentTransfer(
        Investment $investment,
        int|float|string $amount,
        ?bool $sca,
    ): Transfer {
        $asset = $investment->getOffering()->getAsset();
        $metadata = "AstName:{$asset->getName()};AstCode:{$asset->getCompanyNumber()};Type:Investment";
        $transfer = $this->buildTransferObj(
            $investment->getUser()->getMangoPayUserId(),
            $investment->getUser()->getMangoPayWalletId(),
            $asset->getHoldWalletId(),
            $amount,
            0,
            $metadata,
        );
        if ($sca !== null) {
            $scaContext = $sca ? 'USER_PRESENT' : 'USER_NOT_PRESENT';
            $transfer->ScaContext = $scaContext;
            $this->logger->debug("Preparing transfer with SCA context {$scaContext}");
        }
        $this->logger->debug(
            'Mangopay request for createInvestmentTransfer = ['
            . json_encode($transfer)
            . ']',
        );
        $transferResponse = $this->executeMangopayTransfersCreate($transfer);
        $this->logger->debug(
            'Mangopay response for createInvestmentTransfer = ['
            . json_encode($transferResponse)
            . ']',
        );

        $this->handleMangopayResponse($transferResponse);

        return $transferResponse;
    }

    public function createTradeOrderTransfer(
        TradeOrder $tradeOrder,
        int|float|string $amount,
        ?bool $sca,
    ): Transfer {
        if (!in_array($tradeOrder->getType(), TradeOrderType::tradingBuyTypes())) {
            throw new \InvalidArgumentException(
                "Cannot create transfer for order of type: {$tradeOrder->getType()->value}",
            );
        }
        $transferType = match ($tradeOrder->getDirection()) {
            TradeDirection::Buy => 'Investment',
            TradeDirection::Sell => 'Relisting',
        };
        $asset = $tradeOrder->getAsset();
        $metadata = "AstName:{$asset->getName()};AstCode:{$asset->getCompanyNumber()};Type:{$transferType}";
        $transfer = $this->buildTransferObj(
            $tradeOrder->getUser()->getMangoPayUserId(),
            $tradeOrder->getUser()->getMangoPayWalletId(),
            $asset->getHoldWalletId(),
            $amount,
            0,
            $metadata,
            true,
        );
        if ($sca !== null) {
            $scaContext = $sca ? 'USER_PRESENT' : 'USER_NOT_PRESENT';
            $transfer->ScaContext = $scaContext;
            $this->logger->debug("Preparing transfer with SCA context {$scaContext}");
        }
        $this->logger->debug(
            'Mangopay request for createTradeOrderTransfer = ['
            . json_encode($transfer)
            . ']',
        );
        $transferResponse = $this->executeMangopayTransfersCreate($transfer);
        $this->logger->debug(
            'Mangopay response for createTradeOrderTransfer = ['
            . json_encode($transferResponse)
            . ']',
        );

        $this->handleMangopayResponse($transferResponse);

        return $transferResponse;
    }

    public function createGenericTransfer(
        string $authUserId,
        string $debitedWalletId,
        string $creditedWalletId,
        float $amount,
        float $fee,
        string $metadata = '',
    ): \MangoPay\Transfer {
        $transfer = $this->buildTransferObj(
            $authUserId,
            $debitedWalletId,
            $creditedWalletId,
            $amount,
            $fee,
            $metadata,
        );
        $this->logger->debug(
            'Mangopay request for createGenericTransfer = ['
            . json_encode($transfer)
            . ']',
        );
        $transferResponse = $this->executeMangopayTransfersCreate($transfer);
        $this->logger->debug(
            'Mangopay response for createGenericTransfer = ['
            . json_encode($transferResponse)
            . ']',
        );

        return $transferResponse;
    }

    /**
     * Payin to a wallet via Direct Debit
     * @param User $user
     * @param $mandateId
     * @param $amount
     */
    public function directDebitPayin($user, $mandateId, $amount)
    {
        $this->logger->info('In directDebitPayin ');

        $payIn = new PayIn();
        $payIn->Tag = 'Type:Direct Debit Payin';
        $payIn->CreditedWalletId = $user->getMangoPayWalletId();
        $payIn->AuthorId = $user->getMangoPayUserId();

        $payIn->DebitedFunds = new Money();
        $payIn->DebitedFunds->Amount = $amount + 60;
        $payIn->DebitedFunds->Currency = CurrencyIso::GBP;

        $payIn->Fees = new Money();
        $payIn->Fees->Amount = 60;
        $payIn->Fees->Currency = CurrencyIso::GBP;

        $payIn->PaymentDetails = new PayInPaymentDetailsDirectDebit();
        $payIn->PaymentDetails->MandateId = $mandateId;
        $payIn->PaymentType = PayInPaymentType::DirectDebit;

        $payIn->ExecutionType = PayInExecutionType::Direct;
        $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();

        $this->logger->debug(
            'Mangopay request for directDebitPayin = [' . json_encode($payIn) . ']',
        );

        $response = $this->executeMangopayPayInsCreate($payIn);

        $this->logger->debug(
            'Mangopay response for directDebitPayin = [' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Generate a transaction for a bank wire to mango pay
     * @param User $user
     * @param $data
     */
    public function createMangopayWalletPayinBankWire($user, $data)
    {
        try {
            $this->logger->info(
                'Data passed to createMangopayWalletPayinBankWire ['
                . json_encode($data)
                . ']',
            );

            //check for missing data sent in via the API request
            $data = $this->checkData($data);

            // create pay-in BANKWIRE DIRECT
            $payIn = new PayIn();
            $payIn->CreditedWalletId = $user->getMangoPayWalletId();
            $payIn->AuthorId = $user->getMangoPayUserId();
            // payment type as CARD
            $payIn->PaymentDetails = new PayInPaymentDetailsBankWire();

            //0.5% fee
            //$fee = round((0.5 * $data->amount) * 0.01, 0);

            $payIn->PaymentDetails->DeclaredDebitedFunds = new Money();
            $payIn->PaymentDetails->DeclaredDebitedFunds->Amount = $data->amount;
            $payIn->PaymentDetails->DeclaredDebitedFunds->Currency = $data->currency;
            $payIn->PaymentDetails->DeclaredFees = new Money();
            $payIn->PaymentDetails->DeclaredFees->Amount = $data->fee_amount;
            $payIn->PaymentDetails->DeclaredFees->Currency = $data->currency;

            $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
            $payIn->Tag = 'Type:Bankwire Transfer';

            $this->logger->debug(
                'Mangopay request for createMangopayWalletPayinBankWire = ['
                . json_encode($payIn)
                . ']',
            );

            $response = $this->executeMangopayPayInsCreate($payIn);

            $this->logger->debug(
                'Mangopay response for createMangopayWalletPayinBankWire = ['
                . json_encode($response)
                . ']',
            );
            $this->handleMangopayResponse($response);

            $wireReference = $response->PaymentDetails->WireReference;
            $type = $response->PaymentDetails->BankAccount->Type;
            $ownerName = $response->PaymentDetails->BankAccount->OwnerName;
            $BIC = $response->PaymentDetails->BankAccount->Details->BIC;

            $wireAmount = $response->PaymentDetails->DeclaredDebitedFunds->Amount;

            $this->sendMangopayPayinBankTransferMailToUser(
                $user,
                $wireReference,
                $type,
                $ownerName,
                $BIC,
                $wireAmount,
            );

            return $response;
        } catch (\Exception $ex) {
            return $response;
        }
    }

    public function createBankwirePayin(
        $user,
        \App\Dto\BankwirePayinDTO $payinDTO,
    ): ?\Mangopay\Payin {
        $payIn = new PayIn();
        $payIn->CreditedWalletId = $user->getMangoPayWalletId();
        $payIn->AuthorId = $user->getMangoPayUserId();
        $payIn->PaymentDetails = new PayInPaymentDetailsBankWire();
        $payIn->PaymentDetails->DeclaredDebitedFunds = new Money();
        $payIn->PaymentDetails->DeclaredDebitedFunds->Amount = $payinDTO->getAmount();
        $payIn->PaymentDetails->DeclaredDebitedFunds->Currency = 'GBP';
        $payIn->PaymentDetails->DeclaredFees = new Money();
        $payIn->PaymentDetails->DeclaredFees->Amount = 0;
        $payIn->PaymentDetails->DeclaredFees->Currency = 'GBP';

        $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
        $payIn->Tag = 'Type:Bankwire Transfer';

        try {
            $response = $this->executeMangopayPayInsCreate($payIn);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            $this->logger->debug(
                'Mangopay Respone Exception. Reponse Code: '
                    . $e->GetCode()
                    . ' Error message: '
                    . $e->GetMessage()
                    . 'Error details: '
                    . $e->GetErrorDetails(),
            );
            return null;
        } catch (\MangoPay\Libraries\Exception $e) {
            $this->logger->debug(
                'Mangopay Exception. Error message: ' . $e->GetMessage(),
            );
            return null;
        }

        $this->logger->debug(
            'Mangopay response for createBankwirePayin = ['
            . json_encode($response)
            . ']',
        );

        //toggle for payin confirmation email
        if ($payinDTO->getNotification()) {
            if ($response->Status == 'CREATED' or $response->Status == 'SUCCEEDED') {
                $wireReference = $response->PaymentDetails->WireReference;
                $type = $response->PaymentDetails->BankAccount->Type;
                $ownerName = $response->PaymentDetails->BankAccount->OwnerName;
                $BIC = $response->PaymentDetails->BankAccount->Details->BIC;
                $wireAmount = $response->PaymentDetails->DeclaredDebitedFunds->Amount;
                $this->sendMangopayPayinBankTransferMailToUser(
                    $user,
                    $wireReference,
                    $type,
                    $ownerName,
                    $BIC,
                    $wireAmount,
                );
            }
        }

        return $response;
    }

    /**
     * Create a mangopay payout from a wallet to a users bank account
     * @param User $user
     * @param $walletId
     * @param $data
     */
    public function createMangopayWalletPayoutBankWire($user, $data)
    {
        $this->logger->info('In createMangopayWalletPayoutBankWire');

        //check for missing data sent in via the API request
        $data = $this->checkData($data);

        $payout = new PayOut();
        $payout->AuthorId = $user->getMangoPayUserId();
        $payout->DebitedWalletId = $user->getMangoPayWalletId();
        $payout->MeanOfPaymentDetails = new PayOutPaymentDetailsBankWire();
        $payout->MeanOfPaymentDetails->BankAccountId = $data->bank_account_id;
        $payout->MeanOfPaymentDetails->BankWireRef = 'Wire Payout';
        $payout->DebitedFunds = new Money();
        $payout->DebitedFunds->Amount = $data->amount;
        $payout->DebitedFunds->Currency = $data->currency;
        $payout->Fees = new Money();
        $payout->Fees->Amount = $data->fee_amount;
        $payout->Fees->Currency = $data->currency;
        $payout->Tag = 'Type:Withdrawal';

        $this->logger->debug(
            'Mangopay request for createMangopayWalletPayoutBankWire = ['
            . json_encode($payout)
            . ']',
        );

        $response = $this->executeMangopayPayoutCreate($payout);

        $this->logger->debug(
            'Mangopay response for createMangopayWalletPayoutBankWire = ['
            . json_encode($response)
            . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    public function removeSpaceFromStartOfString() {}

    /**
     * Create a bank account to be used for a direct debit
     * @param User $user
     * @param $fields
     * @throws ApiException
     */
    public function createMangopayUserDirectDebitBankAccount($user, $data)
    {
        $this->logger->debug(
            'MangoPayUserId passed to createMangopayUserDirectDebitBankAccount = ['
            . json_encode($user->getMangoPayUserId())
            . ']',
        );

        $this->logger->debug(
            'Fields passed to createMangopayUserDirectDebitBankAccount ======== ['
            . json_encode($data)
            . ']',
        );

        if ($data->addressCheck === true) {
            $mangoUserId = $user->getMangoPayUserId();
            $firstName = $user->getFirstName();
            $lastName = $user->getLastName();
            $mainAddress = $user->getMainAddress();

            $type = 'GB';

            if ($data->bankAccountType === 'uk bank account') {
                $type = 'GB';
            } elseif ($data->bankAccountType === 'eu bank account') {
                $type = 'IBAN';
            }

            $address = new Address();
            $address->AddressLine1 = $mainAddress->getAddress1();
            $address->AddressLine2 = $mainAddress->getAddress2();
            $address->City = $mainAddress->getCity();
            $address->Country = $this->getCountryCode($mainAddress->getCountry());
            $address->PostalCode = $mainAddress->getPostCode();

            $bankAccount = new BankAccount();
            $bankAccount->UserId = $mangoUserId;
            $bankAccount->OwnerName = $firstName . ' ' . $lastName;
            $bankAccount->OwnerAddress = $address;
            $bankAccount->Type = $type;

            //The type can either be IBAN or GB
            if ($type === 'IBAN') {
                $bankAccount->Details = new BankAccountDetailsIBAN();
                $bankAccount->Details->IBAN = $data->accountIban;
                $bankAccount->Details->BIC = $data->sortBic;
            } elseif ($type === 'GB') {
                $bankAccount->Details = new BankAccountDetailsGB();
                $bankAccount->Details->AccountNumber = $data->accountIban;
                $bankAccount->Details->SortCode = $data->sortBic;
            }
            $this->logger->debug(
                'Sending request data to Mangopay[' . json_encode($bankAccount) . ']',
            );

            $response = $this->executeMangopayBankAccountCreate(
                $mangoUserId,
                $bankAccount,
            );

            $this->logger->debug(
                'Response from Mangopay[' . json_encode($response) . ']',
            );
            $this->handleMangopayResponse($response);

            return $response;
        }
    }

    /**
     * Generate a mandate to be used for direct debit payins
     * @param User $user
     * @param $bankAccountID
     * @throws ApiException
     */
    public function createMangopayMandate($data, $user, $bankAccountID)
    {
        $this->logger->debug(
            'MangoPayUserId passed to createMangopayMandate  = ['
            . json_encode($user->getMangoPayUserId())
            . ']',
        );
        $this->logger->debug(
            'MangoBankAccountID passed to createMangopayMandate  = ['
            . $bankAccountID
            . ']',
        );

        $mandate = new \MangoPay\Mandate();

        $mandate->UserId = $user->getMangoPayUserId();
        $mandate->BankAccountId = $bankAccountID;
        $mandate->Culture = 'EN';
        $mandate->ReturnURL = $data->confirmUrl;

        $this->logger->debug(
            'Sending request data to Mangopay[' . json_encode($mandate) . ']',
        );

        $response = $this->executeMangopayMandateCreate($mandate);

        $this->logger->debug('Response from Mangopay[' . json_encode($response) . ']');

        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Generate a transaction for a bank wire to mango pay
     * @param User $user
     * @param $data
     * @throws ApiException
     */
    public function createMangopayUserBankAccount($user, $data)
    {
        $this->logger->debug(
            'MangoPayUserId passed to createMangopayUserBankAccount = ['
            . json_encode($user->getMangoPayUserId())
            . ']',
        );
        $this->logger->debug(
            'Data passed to createMangopayUserBankAccount = ['
            . json_encode($data)
            . ']',
        );

        //The address comes over as a single string!!
        $address_array = explode(',', $data->owner_address);

        $bankAccount = new BankAccount();
        $bankAccount->UserId = $user->getMangoPayUserId();
        $bankAccount->OwnerName = $data->owner_name;
        $bankAccount->OwnerAddress = new Address();
        $bankAccount->OwnerAddress->AddressLine1 = trim($address_array[0]);
        $bankAccount->OwnerAddress->AddressLine2 = trim($address_array[1]);
        $bankAccount->OwnerAddress->City = trim($address_array[3]);
        $bankAccount->OwnerAddress->Region = trim($address_array[4]);
        $bankAccount->OwnerAddress->Country = $this->getCountryCode(trim(
            $address_array[5],
        ));
        $bankAccount->OwnerAddress->PostalCode = trim($address_array[6]);
        $bankAccount->Type = $data->type;

        //The type can either be IBAN or GB
        if ($data->type === 'IBAN') {
            $bankAccount->Details = new BankAccountDetailsIBAN();
            $bankAccount->Details->BIC = $data->BIC;
            $bankAccount->Details->IBAN = $data->IBAN;
        } elseif ($data->type === 'GB') {
            $bankAccount->Details = new BankAccountDetailsGB();
            $bankAccount->Details->AccountNumber = $data->account_number;
            $bankAccount->Details->SortCode = $data->sort_code;
        } else {
            throw new ApiException('Unknown Bank account type [' . $data->type . ']');
        }

        $this->logger->debug(
            'Sending request data to Mangopay[' . json_encode($bankAccount) . ']',
        );

        $response = $this->executeMangopayBankAccountCreate(
            $user->getMangoPayUserId(),
            $bankAccount,
        );

        $this->logger->debug('Response from Mangopay[' . json_encode($response) . ']');
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Gets a transfer for a given transferId
     * @param $transferId
     * @return \MangoPay\Transfer
     */

    public function getTransfer($transferId)
    {
        // $this->logger->debug('API call to Mangopay for transfer');
        return $this->api->Transfers->Get($transferId);
    }

    /**
     * Gets card registration
     * @param User $user
     * @param $data
     */
    public function cardRegistration($user, $data)
    {
        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $cardRegistration = new CardRegistration();
        $cardRegistration->UserId = $user->getMangoPayUserId();
        $cardRegistration->Currency = 'GBP';

        //Defaults to CB_VISA_MASTERCARD if no card_type
        //$cardRegistration->CardType = $data->card_type;

        $this->logger->debug(
            'Mangopay request for cardRegistration = ['
            . json_encode($cardRegistration)
            . ']',
        );

        $response = $this->executeMangopayCardRegistrationCreate($cardRegistration);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Gets card registration
     * @param User $user
     * @param $data
     */
    public function cardCreate($user, $data)
    {
        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $cardRegistration = new CardRegistration();
        $cardRegistration->Id = $data->card_registration_id;

        //Adding "data=" to the registration data if it does not exist.  The mangopay call will fail without this!
        if (substr($data->data, 0, 5) === 'data=') {
            $cardRegistration->RegistrationData = $data->data;
        } else {
            $cardRegistration->RegistrationData = 'data=' . $data->data;
        }

        $this->logger->debug(
            'Mangopay request for cardCreate = ['
            . json_encode($cardRegistration)
            . ']',
        );

        $response = $this->executeMangopayCardRegistrationUpdate($cardRegistration);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        //We dont't save this data anywhere, it is just used by the mangopayCards api url to register a credit card

        return $response;
    }

    /**
     * LEGACY
     *
     * Create a KYC Document
     * @param User $user
     */
    public function createKYCDocument($user)
    {
        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $kycDocument = new KycDocument();
        $kycDocument->Type = 'IDENTITY_PROOF';
        $kycDocument->UserId = $user->getMangoPayUserId();

        $this->logger->debug(
            'Mangopay request for createKYCDocument = ['
            . json_encode($kycDocument)
            . ']',
        );

        $response = $this->executeMangopayUsersCreateKycDocument(
            $user->getMangoPayUserId(),
            $kycDocument,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        // We now have the KYCDocumentId
        // We need to get the users proof_of_identity tagged document

        $this->logger->debug('NAME=' . $user->getUserIdentifier());

        $documents = $user->getDocuments();
        // Loop through all documents belonging to a user and check for those tagged with proof_of_identity
        // Get the latest one of these
        // There's probably a more efficient way of doing this, but this is legacy code so we'll avoid touching it
        foreach ($documents as $document) {
            /** @var UserDocument $document */
            $this->logger->debug('NAME='
                . $document->getDocument()->getName(), [$document->getId()]);

            if ($document->getDocument()->getTag() === 'proof_of_identity') {
                $this->logger->debug('KYC doc found');

                // Use the most recent document
                // Note that getDocuments will return in reverse chronological order
                $taggedDocument = $document;
                break;
            }
        }

        $this->logger->debug('Got response');

        if (!isset($taggedDocument)) {
            throw new \UnexpectedValueException(
                'User must have a document with tag proof_of_identity!',
            );
        }

        $this->logger->debug('Got response');

        // Now we create a mangopay kyc page and upload the document

        $page = new KycPage();
        $page->File = base64_encode($this->documentService->read(
            $taggedDocument->getDocument()->getDocumentUrl(),
            'private',
        ));
        // $page->File = $taggedDocument->getDocument()->getBase64Encoded_DocumentContent();

        $this->logger->debug(
            'Mangopay request for createKYCDocumentPage = [' . json_encode($page) . ']',
        );
        $this->logger->debug(
            'Mangopay request for createKYCDocumentPage = ['
            . $user->getMangoPayUserId()
            . ']',
        );

        $response2 = $this->executeCreateKycPage(
            $user->getMangoPayUserId(),
            $response->Id,
            $page,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response2) . ']',
        );
        //$this->handleMangopayResponse($response2);

        //var_dump($response2);

        //Now we need to submit the document with the is and status updated
        $kycDocument->Status = 'VALIDATION_ASKED';
        $kycDocument->Id = $response->Id;

        $this->logger->debug(
            'Mangopay request for createKYCDocument = ['
            . json_encode($kycDocument)
            . ']',
        );

        $response3 = $this->executeUpdateKycDocument(
            $user->getMangoPayUserId(),
            $kycDocument,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response3) . ']',
        );
        $this->handleMangopayResponse($response3);

        return $response;
    }

    /**
     * Add a KYC document to Mangopay from passed in document
     *
     * @param UserDocument $userDocument
     *
     * @return \Mangopay\KycDocument
     */
    public function addKYCDocument(UserDocument $userDocument)
    {
        $user = $userDocument->getUser();

        $kycDocument = new KycDocument();
        $kycDocument->Type = 'IDENTITY_PROOF';
        $kycDocument->UserId = $user->getMangoPayUserId();

        $this->logger->debug(
            'Mangopay request for createKYCDocument = ['
            . json_encode($kycDocument)
            . ']',
        );

        $mpKycDocument = $this->executeMangopayUsersCreateKycDocument(
            $user->getMangoPayUserId(),
            $kycDocument,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($mpKycDocument) . ']',
        );
        $this->handleMangopayResponse($mpKycDocument);

        $page = new KycPage();

        //check if passed in document has document content; if not retrieve from file store
        if ($userDocument->getDocument()->getDocumentContent()) {
            $page->File = $userDocument->getDocument()->getDocumentContent();
        } elseif ($userDocument->getDocument()->getDocumentUrl()) {
            $page->File = base64_encode($this->documentService->read(
                $userDocument->getDocument()->getDocumentUrl(),
                'private',
            ));
        }

        $this->logger->debug(
            'Mangopay request for createKYCDocumentPage = [' . json_encode($page) . ']',
        );
        $this->logger->debug(
            'Mangopay request for createKYCDocumentPage = ['
            . $user->getMangoPayUserId()
            . ']',
        );

        $mpPage = $this->executeCreateKycPage(
            $user->getMangoPayUserId(),
            $mpKycDocument->Id,
            $page,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($mpPage) . ']',
        );

        $kycDocument->Status = 'VALIDATION_ASKED';
        $kycDocument->Id = $mpKycDocument->Id;

        $this->logger->debug(
            'Mangopay request for createKYCDocument = ['
            . json_encode($kycDocument)
            . ']',
        );

        $updatedMpDocument = $this->executeUpdateKycDocument(
            $user->getMangoPayUserId(),
            $kycDocument,
        );

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($updatedMpDocument) . ']',
        );
        $this->handleMangopayResponse($updatedMpDocument);

        return $updatedMpDocument;
    }

    /**
     * Get the KYC documents related to a user object
     */
    public function getKycDocuments(User $user): array
    {
        $mangopayUserId = $user->getMangoPayUserId();

        return $this->executeGetKycDocuments($mangopayUserId);
    }

    public function cardPayIn(User $user, string $cardId, CardPayinDTO $cardPayinDTO)
    {
        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $browserInfo = $cardPayinDTO->getBrowserInfo();
        $payIn = $this->buildCardPayinObj(
            $user->getMangoPayUserId(),
            $user->getMangoPayWalletId(),
            $cardId,
            $cardPayinDTO->getAmount(),
            $cardPayinDTO->getCurrency(),
            $cardPayinDTO->getIpAddress(),
            $browserInfo->getAcceptHeader(),
            $browserInfo->getUserAgent(),
            $browserInfo->getLanguage(),
            $browserInfo->getScreenWidth(),
            $browserInfo->getScreenHeight(),
            $browserInfo->getColorDepth(),
            $browserInfo->getTimeZoneOffset(),
            $browserInfo->getJavaEnabled(),
            $browserInfo->getJavascriptEnabled(),
            $cardPayinDTO->getSecureModeReturnUrl(),
        );

        $response = $this->executeMangopayPayInsCreate($payIn);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Validates and sets defaults for data that is sent over an API request
     * @param $data
     * @return mixed
     */
    private function checkData($data)
    {
        if (!isset($data->currency)) {
            $this->logger->warning(
                'Value for currency was not passed in via API, setting to default value ['
                . self::DEFAULT_CURRENCY
                . ']',
            );
            $data->currency = self::DEFAULT_CURRENCY;
        }

        if (!isset($data->fee_amount)) {
            $this->logger->warning(
                'Value for fee_amount was not passed in via API, setting to default value ['
                . self::DEFAULT_FEE
                . ']',
            );
            $data->fee_amount = self::DEFAULT_FEE;
        }

        if (!isset($data->card_type)) {
            $this->logger->warning(
                'Value for card_type was not passed in via API, setting to default value ['
                . self::DEFAULT_CARD_TYPE
                . ']',
            );
            $data->card_type = self::DEFAULT_CARD_TYPE;
        }

        return $data;
    }

    /**
     * Gets card registration
     * @param User $user
     * @param $data
     * @throws ApiException
     */
    public function cardWebPayIn($user, $data)
    {
        $this->logger->debug(
            'Data passed to cardWebPayIn = [' . json_encode($data) . ']',
        );
        $this->logger->debug(
            'Data passed to cardWebPayIn = ['
            . json_encode($user->getMangoPayUserId())
            . ']',
        );

        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        //check for missing data sent in via the API request
        $data = $this->checkData($data);

        // create pay-in CARD DIRECT
        $payIn = new PayIn();
        $payIn->CreditedWalletId = $user->getMangoPayWalletId();
        $payIn->AuthorId = $user->getMangoPayUserId();
        $payIn->CreditedUserId = $user->getMangoPayUserId();
        $payIn->DebitedFunds = new Money();
        $payIn->DebitedFunds->Amount = $data->amount;
        $payIn->DebitedFunds->Currency = $data->currency;
        $payIn->Tag = 'Type:Card Payment';

        $payIn->Fees = new Money();
        $payIn->Fees->Amount = $data->fee_amount;
        $payIn->Fees->Currency = $data->currency;

        // payment type as CARD
        $payIn->PaymentDetails = new PayInPaymentDetailsCard();
        $payIn->PaymentDetails->CardType = $data->card_type;

        // execution type as DIRECT
        $payIn->ExecutionDetails = new PayInExecutionDetailsWeb();
        $payIn->ExecutionDetails->ReturnURL = $data->callback_url;
        $payIn->ExecutionDetails->SecureMode = 'DEFAULT';
        $payIn->ExecutionDetails->Culture = 'EN';

        $this->logger->debug(
            'Mangopay request for cardWebPayIn = [' . json_encode($payIn) . ']',
        );

        $response = $this->executeMangopayPayInsCreate($payIn);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Gets card registration
     * @param User $user
     * @throws ApiException
     */
    public function getUserbankaccounts($user)
    {
        $this->logger->debug('Doing mangopay getUserbankaccounts');

        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $this->logger->debug(
            'Sending request data to Mangopay [empty request is correct here]',
        );

        //Response is an array of bank accounts
        $response = $this->executeMangopayUsersGetBankAccounts(
            $user->getMangoPayUserId(),
        );

        $this->logger->debug('Response from Mangopay [' . json_encode($response) . ']');

        //check only the first instance of bank account
        $this->handleMangopayResponse($response[0]);

        return $response;
    }

    /**
     * LEGACY
     * Get a mangoPay Wallet
     *
     * @param $user
     */
    public function getWallet($wallet_id)
    {
        //Response here will be an array
        $response = $this->executeMangoPayViewWallet($wallet_id);

        return $response;
    }

    public function getSingleWallet(
        string $walletId,
        ?string $scaContext = null,
    ): \MangoPay\Wallet {
        return $this->executeMangoPayViewWallet($walletId, $scaContext);
    }

    public function getPayIn(string $payinId): PayIn
    {
        return $this->executeMangopayPayInGet($payinId);
    }

    /**
     * Get a users mangoPay Wallets
     *
     * @param $user
     */
    public function getUserWallets(User $user)
    {
        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $this->logger->debug(
            'Mangopay request for getUserWallets = [ expected emtpty ]',
        );

        //Response here will be an array
        $response = $this->executeMangoPayUserGetWallets($user->getMangoPayUserId());

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );

        //Onlu test first element of array
        $this->handleMangopayResponse($response[0]);

        return $response;
    }

    /**
     * Get a users mangoPay Wallets
     *
     * @param $cardId
     */
    public function deactivateCard($cardId)
    {
        $this->logger->debug(
            'Mangopay request for deactivateCard cardId = [ ' . $cardId . ' ]',
        );

        $card = new Card();
        $card->Id = $cardId;
        $card->Active = false;

        //Response here will be an array
        $response = $this->executeMangopayCardUpdate($card);

        $this->logger->debug(
            'Got response from Mangopay for deactivateCard['
            . json_encode($response)
            . ']',
        );

        //Only test first element of array
        //$this->handleMangopayResponse($response[0]);

        return $response;
    }

    /**
     * Deactivate a Bank Account
     *
     * @param $user
     * @param $bankAccountId
     */
    public function deactivateBankAccount($user, $bankAccountId)
    {
        $this->logger->debug(
            'Mangopay request for deactivateBankAccount bankAccountId = [ '
            . $bankAccountId
            . ' ]',
        );

        $userId = $user->getMangoPayUserId();
        $bankAccount = $this->executeGetBankAccount($userId, $bankAccountId);
        $bankAccount->Active = false;

        $response = $this->executeBankAccountUpdate($userId, $bankAccount);

        $this->logger->debug(
            'Got response from Mangopay for deactivateBankAccount['
            . json_encode($response)
            . ']',
        );

        return $response;
    }

    /**
     * Get a Mandate
     *
     * @param $mandateId
     * @return \MangoPay\Mandate
     */
    public function getMandate($mandateId)
    {
        $this->logger->debug(
            'Mangopay request for getMandate mandateId = [ ' . $mandateId . ' ]',
        );

        //Response here will be an array
        $response = $this->executeGetMandate($mandateId);

        $this->logger->debug(
            'Got response from Mangopay for getMandate[' . json_encode($response) . ']',
        );

        return $response;
    }

    /**
     * Cancel a Mandate
     *
     * @param $mandateId
     * @return \MangoPay\Mandate
     */
    public function cancelMandate($mandateId)
    {
        $this->logger->debug(
            'Mangopay request for cacnelMandate mandateId = [ ' . $mandateId . ' ]',
        );

        $response = $this->executeCancelMandate($mandateId);

        $this->logger->debug(
            'Got response from Mangopay for cancelMandate['
            . json_encode($response)
            . ']',
        );

        return $response;
    }

    /**
     * Get a single bank account
     *
     * @param $userId,
     * @param $bankAccountId
     *
     */
    public function getBankAccount($user, $bankAccountId)
    {
        $this->logger->debug(
            'Mangopay request for getBankAccount bankAccountId = [ '
            . $bankAccountId
            . ' ]',
        );

        $userId = $user->getMangoPayUserId();

        //Response here will be an array
        $response = $this->executeGetBankAccount($userId, $bankAccountId);

        $this->logger->debug(
            'Got response from Mangopay executeGetBankAccount for ['
            . json_encode($response)
            . ']',
        );

        return $response;
    }

    protected function executeGetBankAccount($userId, $bankAccountId)
    {
        return $this->api->Users->GetBankAccount($userId, $bankAccountId);
    }

    protected function executeGetMandate($mandateId)
    {
        return $this->api->Mandates->Get($mandateId);
    }

    protected function executeCancelMandate($mandateId)
    {
        return $this->api->Mandates->Cancel($mandateId);
    }

    protected function executeBankAccountUpdate($userId, $bankAccount)
    {
        return $this->api->Users->UpdateBankAccount($userId, $bankAccount);
    }

    protected function executeMangopayCardUpdate($card)
    {
        return $this->api->Cards->Update($card);
    }

    protected function executeMangoPayUserCreate($user)
    {
        return $this->api->Users->Create($user);
    }

    protected function executeMangoPayWalletCreate($wallet)
    {
        return $this->api->Wallets->Create($wallet);
    }

    protected function executeMangoPayUserGetWallets($user)
    {
        return $this->api->Users->GetWallets($user);
    }

    protected function executeMangoPayViewWallet(
        string $walletId,
        ?string $scaContext = null,
    ) {
        return $this->api->Wallets->Get($walletId, $scaContext);
    }

    protected function executeMangopayCardRegistrationCreate($cardRegistration)
    {
        return $this->api->CardRegistrations->Create($cardRegistration);
    }

    protected function executeMangopayCardRegistrationUpdate($cardRegistration)
    {
        return $this->api->CardRegistrations->Update($cardRegistration);
    }

    protected function executeMangopayPayInsCreate($payIn)
    {
        return $this->api->PayIns->Create($payIn);
    }

    protected function executeMangopayPayInGet(string $payInId)
    {
        return $this->api->PayIns->Get($payInId);
    }

    protected function executeMangopayTransfersCreate($transfer)
    {
        return $this->api->Transfers->Create($transfer);
    }

    protected function executeMangopayBankAccountCreate($mangopayUserId, $bankAccount)
    {
        return $this->api->Users->CreateBankAccount($mangopayUserId, $bankAccount);
    }

    protected function executeMangopayMandateCreate($mandate)
    {
        return $this->api->Mandates->Create($mandate);
    }

    protected function executeMangopayMandateCancel($mandateId)
    {
        return $this->api->Mandates->Cancel($mandateId);
    }

    protected function executeMangopayPayoutCreate($payout)
    {
        return $this->api->PayOuts->Create($payout);
    }

    protected function executeMangopayUsersCreateKycDocument($userId, $kycDocument)
    {
        return $this->api->Users->CreateKycDocument($userId, $kycDocument);
    }

    protected function executeMangopayUsersGetBankAccounts($userId)
    {
        return $this->api->Users->GetBankAccounts($userId);
    }

    protected function executeMangopayUsersGetPayIns($walletId)
    {
        //MangoPay\Pagination
        /** @var Pagination $mgp */
        $mgp = new Pagination();
        $mgp->ItemsPerPage = 100;

        $sort = new Sorting();
        $sort->AddField('CreationDate', SortDirection::DESC);

        /** @var FilterTransactions $filter */
        $filter = new FilterTransactions();
        $filter->Type = 'PAYIN';
        $filter->Status = 'SUCCEEDED';

        return $this->api->Wallets->GetTransactions($walletId, $mgp, $filter, $sort);
    }

    protected function executeMangopayUsersGetTransactionsPage(
        $userId,
        $page,
        $pageSize,
        $startDate,
        $endDate,
        ?string $scaContext = null,
    ) {
        $pagination = new Pagination();
        $pagination->Page = $page;
        $pagination->ItemsPerPage = $pageSize;
        if (!$pageSize) { // can't have page size of 0
            $pagination->ItemsPerPage = 25;
        }

        $filter = new FilterTransactions();
        if ($startDate) {
            $filter->AfterDate = $startDate;
        }
        if ($endDate) {
            $filter->BeforeDate = $endDate;
        }
        if ($scaContext) {
            $filter->ScaContext = $scaContext;
        }

        $sort = new Sorting();
        $sort->AddField('CreationDate', SortDirection::DESC);

        $pagination->Page = $page;
        return $this->api->Wallets->GetTransactions(
            $userId,
            $pagination,
            $filter,
            $sort,
        );
    }

    protected function executeMangopayUsersGetTransactionsMulti(
        $userId,
        $startDate,
        $endDate,
        ?string $scaContext = null,
    ) {
        $pagination = new Pagination(1, 100);

        $filter = new FilterTransactions();
        if ($startDate) {
            $filter->AfterDate = $startDate;
        }
        if ($endDate) {
            $filter->BeforeDate = $endDate;
        }
        if ($scaContext) {
            $filter->ScaContext = $scaContext;
        }

        $sort = new Sorting();
        $sort->AddField('CreationDate', SortDirection::DESC);

        $transactions = [];
        do {
            $results = $this->api->Wallets->GetTransactions(
                $userId,
                $pagination,
                $filter,
                $sort,
            );
            $transactions = array_merge($transactions, $results);
            $pagination->Page = $pagination->Page + 1;
        } while (count($results) == 100 && $pagination->Page <= 10); // current hard cap at 10 pages (1000 transactions)

        return $transactions;
    }

    protected function executeMangopayUsersGetLastTransaction($userId)
    {
        //MangoPay\Pagination
        /** @var Pagination $mgp */
        $mgp = new Pagination();
        $mgp->ItemsPerPage = 1;

        $sort = new Sorting();
        $sort->AddField('CreationDate', SortDirection::DESC);

        return $this->api->Wallets->GetTransactions($userId, $mgp, null, $sort);
    }

    protected function executeCreateKycPage($userId, $docuemnetId, $page)
    {
        return $this->api->Users->CreateKycPage($userId, $docuemnetId, $page);
    }

    protected function executeUpdateKycDocument($userId, $kycDocument)
    {
        return $this->api->Users->UpdateKycDocument($userId, $kycDocument);
    }

    protected function executeGetKycDocuments($mangopayUserId)
    {
        return $this->api->Users->GetKycDocuments($mangopayUserId);
    }

    protected function executeMangopayCreateMandate($mandate)
    {
        return $this->api->Mandates->Create($mandate);
    }

    public function createLegalUser(Asset $asset, User $user)
    {
        $this->logger->info('Doing createLegalUser');

        $isUserValid = $this->isUserValidForMangoPay($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        }

        $isAssetValid = $this->isAssetValidForMangoPay($asset);
        if ($isAssetValid !== true) {
            return $isAssetValid;
        }

        $mangoPayUser = new UserLegalSca();
        $legalRepresentative = new LegalRepresentative();

        $mangoPayUser->LegalPersonType = self::PERSONTYPE_LEGAL;
        $mangoPayUser->PersonType = 'LEGAL';
        $mangoPayUser->Name = $asset->getName();
        $mangoPayUser->UserCategory = 'Owner';
        $mangoPayUser->TermsAndConditionsAccepted = true;
        $legalRepresentative->FirstName = $user->getFirstname();
        $legalRepresentative->LastName = $user->getLastname();
        $legalRepresentative->Birthday = $user->getBirthDate()->getTimestamp();
        $legalRepresentative->Nationality = $this->getCountryCode(
            $user->getNationality(),
        );
        $legalRepresentative->CountryOfResidence = $this->getCountryCode(
            $user->getMainAddress()->getCountry(),
        );
        $mangoPayUser->HeadquartersAddress = new \MangoPay\Address();

        /** @var AssetAddress $address */
        $address = $asset->getAddresses()->first();
        $mangoPayUser->HeadquartersAddress->AddressLine1 = $address->getAddress1();
        $mangoPayUser->HeadquartersAddress->AddressLine2 = $address->getAddress2();
        $mangoPayUser->HeadquartersAddress->City = $address->getCity();
        $mangoPayUser->HeadquartersAddress->Region = $address->getRegion();
        $mangoPayUser->HeadquartersAddress->PostalCode = $address->getPostCode();
        $mangoPayUser->HeadquartersAddress->Country = $this->getCountryCode(
            $address->getCountry(),
        );
        $legalRepresentative->Email = $user->getEmail();
        $mangoPayUser->Email = $asset->getOrgEmail();
        $mangoPayUser->LegalRepresentative = $legalRepresentative;

        $this->logger->debug(
            'Sending request data to Mangopay[' . json_encode($mangoPayUser) . ']',
        );

        $response = $this->executeMangoPayUserCreate($mangoPayUser);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        //$this->em->merge( $user );
        //$this->em->flush();

        return $response;
    }

    /**
     * Gets the details of a MangoPay User
     *
     * @param $mangopay_id
     */
    public function getUser($mangopay_id)
    {
        try {
            if ($mangopay_id) {
                $response = $this->api->Users->GetSca($mangopay_id);
                return $response;
            } else {
                return 'MangoPay user Id is missing';
            }
        } catch (\MangoPay\Libraries\ResponseException $e) {
            //@todo How do we handle this logging
            // handle/log the response exception with code $e->GetCode(), message $e->GetMessage() and error(s) $e->GetErrorDetails()
            $this->logger->error($e->GetMessage());
            return $e->GetMessage();
        } catch (\MangoPay\Libraries\Exception $e) {
            // handle/log the exception $e->GetMessage()
            $this->logger->error($e->GetMessage());
        }
    }

    /**
     * validates that User has a mangopayid
     *
     * @param $user
     */
    public function hasUserMangoPayId(User $user)
    {
        if (!$user->getMangoPayUserId()) {
            throw new UnexpectedValueException('MangoPayUserId can not be empty!');
        }

        return true;
    }

    /**
     * validates that Asset has a mangopayid
     *
     * @param $asset
     */
    public function hasAssetMangoPayId(Asset $asset)
    {
        if (!$asset->getMangoPayUserId()) {
            return 'MangoPayUserId can not be empty';
        }

        return true;
    }

    /**
     * Creates a mangoPay Wallet for a Asset
     *
     * @param $asset
     */
    public function createAssetWallet(Asset $asset)
    {
        $isAssetValid = $this->isAssetValidForMangoPay($asset);
        $hasAssetMangoPayId = $this->hasAssetMangoPayId($asset);

        if ($isAssetValid !== true) {
            return $isAssetValid;
        } elseif ($hasAssetMangoPayId !== true) {
            return $hasAssetMangoPayId;
        }

        $wallet = new Wallet();
        $wallet->Owners = [$asset->getMangoPayUserId()];

        // @TODO Should these description/curreny and tag be set as constants and are they correct
        $wallet->Description = 'yielders Wallet for Organization ' . $asset->getId();
        $wallet->Currency = 'GBP';
        $wallet->Tag = $asset->getId();

        $this->logger->debug(
            'Mangopay request for createAssetWallet = [' . json_encode($wallet) . ']',
        );

        $response = $this->executeMangoPayWalletCreate($wallet);

        $this->logger->debug(
            'Got response from Mangopay[' . json_encode($response) . ']',
        );
        $this->handleMangopayResponse($response);

        //$asset->setMangoPayWalletId( $response->Id );
        //$this->em->merge( $asset );
        //$this->em->flush();

        return $response;
    }

    //@todo we need a test case for this
    public function isUserValidForMangoPay(User $user)
    {
        if (!$user->getEmail()) {
            $this->logger->error('User must have am email address!');
            throw new UnexpectedValueException('User must have am email address!');
        }

        if (!$user->getFirstname()) {
            $this->logger->error('User must have a first name!');
            throw new UnexpectedValueException('User must have a first name!');
        }

        if (!$user->getLastname()) {
            $this->logger->error('User must have a first name!');
            throw new UnexpectedValueException('ç');
        }

        if (!$user->getBirthDate()) {
            $this->logger->error('User must have a birth date!');
            throw new UnexpectedValueException('User must have a birth date!');
        }

        if (!$user->getNationality()) {
            $this->logger->error('User must have a nationality!');
            throw new UnexpectedValueException('User must have a nationality!');
        }

        if (!$user->getMainAddress()->getCountry()) {
            $this->logger->error('User must have a country in address!');
            throw new UnexpectedValueException('User must have a country in address!');
        }

        return true;
    }

    /**
     * validates that an asset is ready for MangoPay
     *
     * @param $asset
     */
    public function isAssetValidForMangoPay(Asset $asset)
    {
        if (!$asset->getContactPoint()) {
            throw new UnexpectedValueException('Asset PointofContact can not be empty');
        }

        if ($asset->getAddresses()->count() == 0) {
            throw new UnexpectedValueException('Address cannot be empty');
        }

        /** @var AssetAddress $address */
        $address = $asset->getAddresses()->first();

        if (!$address->getAddress1()) {
            throw new UnexpectedValueException('Address line 1 cannot be empty');
        }

        if (!$address->getCity()) {
            throw new UnexpectedValueException('City cannot be empty');
        }

        //  if (!$address->getRegion() ){
        //      throw new UnexpectedValueException('Region cannot be empty');
        //  }

        if (!$address->getPostCode()) {
            throw new UnexpectedValueException('PostCode cannot be empty');
        }

        if (!$address->getCountry()) {
            throw new UnexpectedValueException('Country cannot be empty');
        }

        if (!$asset->getOrgEmail()) {
            throw new UnexpectedValueException('Asset OrgEmail can not be empty');
        }

        return true;
    }

    public function getUserMangoPayWalletPayIn($user)
    {
        $this->logger->info('Doing getUserMangoPayWalletPayIn');

        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $this->logger->debug(
            'Mangopay request for getUserMangoPayWalletPayIn for mangopay walletId=['
            . $user->getMangoPayWalletId()
            . '] = [ expected empty (sometimes) ]',
        );

        // Response is an array
        $response = $this->executeMangopayUsersGetPayIns($user->getMangoPayWalletId());

        $this->logger->debug(
            'Got response from getUserMangoPayWalletTransactions ['
            . json_encode($response)
            . ']',
        );
        // Don't handle errors for this request
        //  $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Gets a mangopay users wallet transactions
     * @param User $user
     * @throws ApiException
     */
    public function getUserMangoPayWalletTransactions(
        $user,
        $page = 1,
        $pageSize = 50,
        $startDate = null,
        $endDate = null,
        ?string $scaContext = null,
    ) {
        $this->logger->info('Doing cardUserMangoPayWalletTransactions');

        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $this->logger->debug(
            'Mangopay request for getUserMangoPayWalletTransactions for mangopay walletId=['
            . $user->getMangoPayWalletId()
            . '] = [ expected empty (sometimes) ]',
        );

        // Response is an array
        // Note we keep any Mangopay specific stuff like pagination and filters out of our code, that goes the in executeMangopay* methods
        // FUTURE: if adding more filter options, consider passing them in an array rather than individually

        if (!$page) {
            $response = $this->executeMangopayUsersGetTransactionsMulti(
                $user->getMangoPayWalletId(),
                $startDate,
                $endDate,
                $scaContext,
            );
        } else {
            $response = $this->executeMangopayUsersGetTransactionsPage(
                $user->getMangoPayWalletId(),
                $page,
                $pageSize,
                $startDate,
                $endDate,
                $scaContext,
            );
        }

        $this->logger->debug(
            'Got response from getUserMangoPayWalletTransactions ['
            . json_encode($response)
            . ']',
        );
        // Don't handle errors for this request
        //  $this->handleMangopayResponse($response);

        return $response;
    }

    /**
     * Gets a mangopay users wallet last transaction
     * @param User $user
     * @throws ApiException
     */
    public function getUserMangoPayWalletLastTransaction($user)
    {
        $this->logger->info('Doing getUserMangoPayWalletLastTransaction');

        $isUserValid = $this->isUserValidForMangoPay($user);
        $hasUserMangoPayId = $this->hasUserMangoPayId($user);

        if ($isUserValid !== true) {
            return $isUserValid;
        } elseif ($hasUserMangoPayId !== true) {
            return $hasUserMangoPayId;
        }

        $this->logger->debug(
            'Mangopay request for getUserMangoPayWalletLastTransaction for mangopay walletId=['
            . $user->getMangoPayWalletId()
            . '] = [ expected empty (sometimes) ]',
        );

        // Response is an array
        $response = $this->executeMangopayUsersGetLastTransaction(
            $user->getMangoPayWalletId(),
        );

        $this->logger->debug(
            'Got response from getUserMangoPayWalletLastTransaction ['
            . json_encode($response)
            . ']',
        );
        // Don't handle errors for this request
        //  $this->handleMangopayResponse($response);

        return $response;
    }

    public function handleMangopayResponse($response)
    {
        //Check to see if we got a ResultMessage
        if (property_exists($response, 'ResultMessage')) {
            $resultMessage = $response->ResultMessage;
        } else {
            $resultMessage = 'No ResultMessage';
        }

        //Check to see if we got a ResultCode
        if (property_exists($response, 'ResultCode')) {
            $resultCode = $response->ResultCode;
        } else {
            $resultCode = 'No ResultCode';
        }

        //Check to see if we got a Status
        if (property_exists($response, 'Status')) {
            $status = $response->Status;
        } else {
            $status = 'No Status';
        }

        //Check to see if we got a CreationDate
        if (property_exists($response, 'CreationDate')) {
            $creationDate = $response->CreationDate;
        } else {
            $creationDate = 'No Creation Date';
        }

        $message = "[{$resultCode}] {$resultMessage}";

        if ($status === 'FAILED') { //something went wrong
            $this->logger->error("Mangopay request --FAILED-- {$message}");
            if ($resultCode === '001011') {
                // This means the Transaction amount is higher than maximum permitted amount in this case return an explicit message
                throw new ApiException(
                    $message,
                    ApiException::ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT,
                );
            } elseif ($resultCode === '001001') {
                // Insufficient funds in wallet to do this transaction in this case return an explicit message
                throw new ApiException(
                    $message,
                    ApiException::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET,
                );
            }
            //otherwise throw generic exception
            throw new ApiException($message);
        } elseif ($status === 'ERROR') {
            //everything ok
            $this->logger->info("Mangopay request --FAILED-- {$message}");
            throw new ApiException($message);
        } elseif ($status === 'SUCCEEDED') {
            //everything ok
            $this->logger->info("Mangopay request --SUCCEEDED-- {$message}");
            return;
        } elseif ($status === 'CREATED') {
            $this->logger->info("Mangopay request --CREATED-- {$message}");
            //everything ok
            return;
        } elseif ($creationDate !== 'No Creation Date') {
            //Some mangopay responses don't have a Status, try and use CreatedDate instead??
            $this->logger->info("Mangopay request --CREATED-- {$message}");
            //everything ok
            return;
        } else {
            //something weird happened
            throw new \Exception($message);
        }
    }

    public function sendMangopayPayinBankTransferMailToUser(
        User $user,
        $wireReference,
        $type,
        $ownerName,
        $BIC,
        $wireAmount,
    ) {
        $this->logger->info($user->getUserIdentifier());

        $sent = $this->mailerService->sendMail(
            $user,
            MailerService::TYPE_MANGOPAY_PAYIN_BANKTRANSFER,
            [
                'user' => $user,
                'wireReference' => $wireReference,
                'type' => $type,
                'ownerName' => $ownerName,
                'BIC' => $BIC,
                'Amount' => number_format($wireAmount / 100, 2, '.', ','),
            ],
        );

        if ($sent == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function buildTransferObj(
        string $authUserId,
        string $debitedWalletId,
        string $creditedWalletId,
        float $amount,
        float $fee,
        string $metadata,
        bool $skipWalletIds = false,
    ): Transfer {
        $debitedFunds = new Money();
        $debitedFunds->Amount = (int) round($amount * 100);
        $debitedFunds->Currency = 'GBP';

        $fees = new Money();
        $fees->Amount = (int) round($fee * 100);
        $fees->Currency = 'GBP';

        $transfer = new Transfer();
        $transfer->AuthorId = $authUserId;
        if (!$skipWalletIds) {
            $transfer->Tag =
                'Transfer: ' . $debitedWalletId . ' to ' . $creditedWalletId . ';';
        }

        $transfer->DebitedWalletId = $debitedWalletId;
        $transfer->CreditedWalletId = $creditedWalletId;
        $transfer->DebitedFunds = $debitedFunds;
        $transfer->Fees = $fees;

        if ($metadata !== '') {
            $transfer->Tag = $transfer->Tag . $metadata;
        }

        return $transfer;
    }

    public function buildTransferPayment(
        Asset $asset,
        User $payee,
        string $assetUserId,
        float $amount,
        string $transferType,
        ?string $debitWalletId = null,
    ): Transfer {
        $metadata = [
            'AstName' => $asset->getName(),
            'AstCode' => $asset->getCompanyNumber(),
            'Type' => $transferType,
        ];

        // backwards compatibility with older payments tool
        if (is_null($debitWalletId)) {
            $debitWalletId = $asset->getAdditionalWallet();
        }
        return $this->buildTransferObj(
            $assetUserId,
            $debitWalletId,
            $payee->getMangoPayWalletId(),
            $amount,
            0,
            Helper::stringifyKeyValuePairs($metadata),
        );
    }

    public function buildCardPayinObj(
        string $mangopayUserId,
        string $mangopayWalletId,
        string $cardId,
        int $amount,
        string $currency,
        string $ipAddress,
        string $acceptHeader,
        string $userAgent,
        string $language,
        int $screenWidth,
        int $screenHeight,
        int $colorDepth,
        string $timeZoneOffset,
        bool $javaEnabled,
        bool $javascriptEnabled,
        string $secureModeReturnURL,
    ): PayIn {
        $payIn = new PayIn();
        $payIn->AuthorId = $mangopayUserId;
        $payIn->CreditedUserId = $mangopayUserId;
        $payIn->CreditedWalletId = $mangopayWalletId;

        $payIn->DebitedFunds = new Money();
        $fee = round($amount / 100, 0);
        $payIn->DebitedFunds->Amount = $amount + $fee;
        $payIn->DebitedFunds->Currency = $currency;
        $payIn->Fees = new Money();
        $payIn->Fees->Amount = $fee;
        $payIn->Fees->Currency = $currency;
        $payIn->Tag = 'Type:Card Payment';

        $payIn->PaymentDetails = new PayInPaymentDetailsCard();
        $payIn->PaymentDetails->CardId = $cardId;
        $payIn->PaymentDetails->CardType = self::DEFAULT_CARD_TYPE;

        $payIn->PaymentDetails->IpAddress = $ipAddress;
        $payIn->PaymentDetails->BrowserInfo = new BrowserInfo();
        $payIn->PaymentDetails->BrowserInfo->AcceptHeader = $acceptHeader;
        $payIn->PaymentDetails->BrowserInfo->UserAgent = $userAgent;
        $payIn->PaymentDetails->BrowserInfo->Language = $language;
        $payIn->PaymentDetails->BrowserInfo->ScreenWidth = $screenWidth;
        $payIn->PaymentDetails->BrowserInfo->ScreenHeight = $screenHeight;
        $payIn->PaymentDetails->BrowserInfo->ColorDepth = $colorDepth;
        $payIn->PaymentDetails->BrowserInfo->TimeZoneOffset = $timeZoneOffset;
        $payIn->PaymentDetails->BrowserInfo->JavaEnabled = $javaEnabled;
        $payIn->PaymentDetails->BrowserInfo->JavascriptEnabled = $javascriptEnabled;

        $payIn->ExecutionDetails = new PayInExecutionDetailsDirect();
        $payIn->ExecutionDetails->SecureModeReturnURL = $secureModeReturnURL;

        return $payIn;
    }

    /**
     * Used for Payment Order execution
     */
    public function createTransferPayment(
        Asset $asset,
        User $payee,
        string $assetWalletUserId,
        float $amount,
        string $transferType,
        ?string $debitWalletId = null,
    ): Transfer {
        $transfer = $this->buildTransferPayment(
            $asset,
            $payee,
            $assetWalletUserId,
            $amount,
            $transferType,
            $debitWalletId,
        );
        // Explicitly state that this is an automated action and no SCA should be used
        $transfer->ScaContext = 'USER_NOT_PRESENT';
        // $transfer->Status = 'SUCCEEDED';
        // $transfer->Id = '13131';
        // return $transfer;
        return $this->executeMangopayTransfersCreate($transfer);
    }
}
