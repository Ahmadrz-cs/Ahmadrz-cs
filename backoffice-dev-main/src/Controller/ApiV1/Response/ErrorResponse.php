<?php

namespace App\Controller\ApiV1\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ErrorResponse extends JsonResponse
{
    /**
     * List of possible API error Response
     *
     * 000 - 099 are common errors
     * 101 - 199 are user errors
     * 200 - 299 are asset
     * 300 - 399 are offering
     * 400 - 499 are secondary offering
     * 500 - 599 are investment
     * 600 - 650 are payout
     * 651 - 699 are wallet
     * 700 - 750 are contego
     * 900 - 999 are mangopay
     *
     */

    public const ERROR_NETWORK_INVALID = 001;
    public const ERROR_INSUFFICIENT_PARAMS = 002;
    public const ERROR_MISSING_REQUEST_DATA = 003;
    public const ERROR_SYSTEM_ERROR = 004;
    public const ERROR_INSUFFICIENT_ENTITLEMENTS = 005;
    public const ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION = 006;
    public const ERROR_USER_NOT_LOGGED_IN = 007;

    public const ERROR_DOCUMENT_MISSING_FILE_NAME = 050;
    public const ERROR_DOCUMENT_MISSING_FILE_TYPE = 051;
    public const ERROR_DOCUMENT_MISSING_CONTENT = 052;
    public const ERROR_DOCUMENT_NOT_FOUND = 053;

    public const ERROR_USER_NOT_FOUND = 101;
    public const ERROR_USER_IS_NOT_AN_INVESTOR = 102;
    public const ERROR_USER_TOKEN_EXPIRED = 103;
    public const ERROR_USER_PASSWORD_DONT_MATCH = 104;
    public const ERROR_USER_PASSWORD_CAN_NOT_CHANGE = 105;
    public const ERROR_USER_CURRENT_PASSWORD_MISSING = 106;
    public const ERROR_USER_CONFIRM_PASSWORD_MISSING = 107;
    public const ERROR_USER_NEW_PASSWORD_MISSING = 108;
    public const ERROR_USER_CONFIRM_PASSWORD_NOT_MACHING = 109;
    public const ERROR_USER_PASSWORD_NOT_MATCHING = 110;
    public const ERROR_USER_PASSWORD_MACHING_WITH_CURRENT_PASSWORD = 111;
    public const ERROR_USER_DATA_NOT_PRESENT = 112;
    public const ERROR_USER_MISSING_EMAIL = 113;
    public const ERROR_USER_MISSING_PASSWORD = 114;
    public const ERROR_USER_MISSING_VERIFY_URL = 115;
    public const ERROR_USER_EMAIL_ALREADY_EXISTS = 116;
    public const ERROR_USER_USERNAME_ALREADY_EXISTS = 117;
    public const ERROR_USER_CURRENT_PASSWORD_INVALID = 118;
    public const ERROR_USER_NOT_MATCHED = 119;
    public const ERROR_USER_UPDATE_FAILED = 120;
    public const ERROR_USER_STATE_CHANGE_NOT_POSSIBLE = 121;
    public const ERROR_USER_PASSWORD_STRENGTH = 122;
    public const ERROR_USER_ALREADY_VERIFIED_EMAIL = 123;
    public const ERROR_USER_HAS_NO_DIRECT_DEBIT = 124;
    public const ERROR_USER_BANK_ACCOUNT_REGISTRATION_DUPLICATED = 125;

    public const ERROR_ASSET_NOT_FOUND = 201;
    public const ERROR_ASSET_MISSING_DISPLAY_NAME = 202;

    public const ERROR_OFFERING_NOT_FOUND = 301;
    public const ERROR_OFFERING_EMPTY_FIELD = 302;
    public const ERROR_OFFERING_INVEST_MISSING_OFF_ID = 303;
    public const ERROR_OFFERING_MISSING_NAME = 304;
    public const ERROR_OFFERING_MISSING_FUNDING_GOAL = 305;
    public const ERROR_OFFERING_MISSING_INV_ID = 306;
    public const ERROR_OFFERING_STATE_CHANGE_NOT_POSSIBLE = 307;

    public const ERROR_SECONDARY_OFFERING_NOT_FOUND = 401;

    public const ERROR_INVESTMENT_NOT_FOUND = 501;
    public const ERROR_INVESTMENT_NOT_SETTLED_INVESTOR_BLOCKED = 502;
    public const ERROR_INVESTMENT_IN_SETTLED_STATE = 503;
    public const ERROR_INVESTMENT_STATE_IS_MISSING = 504;
    public const ERROR_INVESTMENT_STATE_CHANGE_NOT_POSSIBLE = 505;
    public const ERROR_INVESTMENT_MISSING_AMOUNT = 506;
    public const ERROR_INVESTMENT_VALUE_LIMIT = 507;
    public const ERROR_INVESTMENT_DOCUMENT_NOT_FOUND = 508;

    public const ERROR_PAYOUT_NOT_FOUND = 601;
    public const ERROR_PAYOUT_CURRENCY_NOT_VALID = 602;
    public const ERROR_PAYOUT_DUEDATE_NOT_VALID = 603;
    public const ERROR_PAYOUT_AMMOUNT_NOT_VALID = 604;
    public const ERROR_PAYOUT_TYPE_NOT_VALID = 605;
    public const ERROR_PAYOUT_GENERIC = 606;

    public const ERROR_WALLET_NOT_FOUND = 651;
    public const ERROR_WALLET_TRANS_MISSING_TRANS_AMOUNT = 652;
    public const ERROR_WALLET_TRANS_MISSING_TRANS_CURRENCY = 653;
    public const ERROR_WALLET_MISSING_CURRENCY = 654;
    public const ERROR_WALLET_USER_MISSING_WALLET = 655;

    public const ERROR_CONTEGO_USER_CHECK_FAILED = 700;

    public const ERROR_MANGOPAY_CARD_PAYIN_FAILED = 901;
    public const ERROR_MANGOPAY_TRANSFER_FAILED = 902;
    public const ERROR_MANGOPAY_BANK_ACCOUNT_FAILED = 903;
    public const ERROR_MANGOPAY_REGISTER_CARD_FAILED = 904;
    public const ERROR_MANGOPAY_CREATE_KYC_DOCUMENT_FAILED = 905;
    public const ERROR_MANGOPAY_USER_MISSING_ID = 906;
    public const ERROR_MANGOPAY_USER_CREATE_FAILED = 907;
    public const ERROR_MANGOPAY_BANK_ACCOUNT_GET_FAILED = 908;
    public const ERROR_MANGOPAY_RESGISTER_ASSET_FAILED = 909;
    public const ERROR_MANGOPAY_BANKWIRE_PAYIN_CREATE_FAILED = 910;
    public const ERROR_MANGOPAY_CREATE_USER_WALLET_FAILED = 911;
    public const ERROR_MANGOPAY_CREATE_CARD_FAILED = 912;
    public const ERROR_MANGOPAY_CREATE_CARD_PAYIN_FAILED = 913;
    public const ERROR_MANGOPAY_KYC_CHECK_FAILED = 914;
    public const ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT = 915;
    public const ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET = 916;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_BANK_ACCOUNT_FAILED = 917;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_MANDATE_FAILED = 918;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_GET_MANDATE_FAILED = 919;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_MANDATE_EXPIRED = 920;
    public const ERROR_MANGOPAY_REMOVING_DIRECT_DEBIT_MANDATE_FAILED = 921;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_PAYIN_FAILED = 922;
    public const ERROR_MANGOPAY_DIRECT_DEBIT_BANK_ACCOUNT_DEACTIVATE_FAILED = 923;

    public const ERROR_MANGOPAY_UNKNOWN = 999;

    /**
     * @var array List of possible API errors
     */
    public static $errorDetails = [
        self::ERROR_USER_HAS_NO_DIRECT_DEBIT => [
            'user_message' => 'User has not setup a direct debit payment',
            'devMessage' => 'A request to check a users direct debit details failed as there is not Dierct Debit entity for this user',
            'api_error_code' => 124,
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_BANK_ACCOUNT_REGISTRATION_DUPLICATED => [
            'user_message' => 'A pending or active bank account registration with the information provided already exists.',
            'devMessage' => 'A pending or active bank account registration with the information provided already exists.',
            'api_error_code' => 125,
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_ALREADY_VERIFIED_EMAIL => [
            'user_message' => 'User already in Email verified state, resending verification email not possible',
            'devMessage' => 'A request to resend a verification email is not possible as the users email as already been verified',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_PASSWORD_STRENGTH => [
            'user_message' => 'Password strength is unacceptable <br><br>
                                 <strong>Passwords must satisfy the following criteria:</strong>
                                 <ul>
                                 <li>Eight or more characters</li>
                                 <li>A mix of lower and upper case English letters</li>
                                 <li>At least one number (0-9)</li>
                                 <li>No number sequences (eg. 5678)</li>
                                 </ul>',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_INVESTMENT_VALUE_LIMIT => [
            'user_message' => 'Investment amount exceeds maximum investment amount',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_INVESTMENT_MISSING_AMOUNT => [
            'user_message' => 'Please enter investment amount',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_OFFERING_MISSING_NAME => [
            'user_message' => 'Please enter name',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_OFFERING_MISSING_FUNDING_GOAL => [
            'user_message' => 'Please enter funding goal',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_OFFERING_MISSING_INV_ID => [
            'user_message' => 'Offering not detected as a relisting. Missing investment id.',
            'devMessage' => 'Offering not detected as a relisting. Missing investment id.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_OFFERING_STATE_CHANGE_NOT_POSSIBLE => [
            'user_message' => 'Lifecycle state change is not possible for the offering.',
            'devMessage' => 'Lifecycle state change is not possible for the offering.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_NOT_LOGGED_IN => [
            'user_message' => 'User is not logged and action cannot be performed',
            'devMessage' => 'A User has attempted to perform an action but isn\'t logged in',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_DOCUMENT_MISSING_FILE_NAME => [
            'user_message' => 'Missing file name',
            'devMessage' => 'A document POST request was made without passing in a file name',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_DOCUMENT_MISSING_FILE_TYPE => [
            'user_message' => 'Missing file type',
            'devMessage' => 'A document POST request was made without passing in a file type',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_DOCUMENT_MISSING_CONTENT => [
            'user_message' => 'Missing document Content',
            'devMessage' => 'A document POST request was made without passing a base64 encoded document content',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_DOCUMENT_NOT_FOUND => [
            'user_message' => 'The requested Document could not be located',
            'devMessage' => 'No Document with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        self::ERROR_USER_UPDATE_FAILED => [
            'user_message' => 'User could not be updated',
            'devMessage' => 'A attempt to update the User failed for an handled reason. check logs',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_NOT_MATCHED => [
            'user_message' => 'User details does not match user searched for',
            'devMessage' => 'The user details passed in does not match the user internally retrieved from the DB',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_USER_MISSING_ID => [
            'user_message' => 'User does not have a MangoPay account!',
            'devMessage' => 'User does not have a Mangopay account, mangoPayUserId is not set',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_USER_CREATE_FAILED => [
            'user_message' => 'Unable to create mangopay account, please contact the system administrator!',
            'devMessage' => 'Unable to create mangopay account, please contact the system administrator!',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_BANK_ACCOUNT_GET_FAILED => [
            'user_message' => 'Unable to get users mangopay bank account, please contact the system administrator!',
            'devMessage' => 'Unable to create mangopay account.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_RESGISTER_ASSET_FAILED => [
            'user_message' => 'Unable to register asset with mangopay, please contact the system administrator!',
            'devMessage' => 'Unable to register asset with mangopay.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_BANKWIRE_PAYIN_CREATE_FAILED => [
            'user_message' => 'Unable create mangopay bankwire payin, please contact the system administrator!',
            'devMessage' => 'Unable create mangopay bankwire payin.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_CREATE_USER_WALLET_FAILED => [
            'user_message' => 'Unable create mangopay user wallet, please contact the system administrator!',
            'devMessage' => 'Unable create mangopay user wallet.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_CREATE_CARD_FAILED => [
            'user_message' => 'Unable create mangopay card, please contact the system administrator!',
            'devMessage' => 'Unable create mangopay card.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_CREATE_CARD_PAYIN_FAILED => [
            'user_message' => 'Unable create mangopay card payin, please contact the system administrator!',
            'devMessage' => 'Unable create mangopay card payin.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_KYC_CHECK_FAILED => [
            'user_message' => 'Unable create mangopay kyc check, please contact the system administrator!',
            'devMessage' => 'Unable create mangopay kyc check.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT => [
            'user_message' => 'Transaction amount is higher than maximum permitted amount! Please contact the <a href="/contact-us">Yielders</a> team for more information.',
            'devMessage' => 'Transaction amount is higher than maximum permitted amount!',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET => [
            'user_message' => 'Insufficient funds, please top up your wallet.',
            'devMessage' => 'Insufficient funds, please top up your wallet',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_USERNAME_ALREADY_EXISTS => [
            'user_message' => 'Email address is already registered on the platform',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_EMAIL_ALREADY_EXISTS => [
            'user_message' => 'Email address is already registered on the platform',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_MISSING_EMAIL => [
            'user_message' => 'Please enter email',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_MISSING_PASSWORD => [
            'user_message' => 'Please enter password',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_USER_MISSING_VERIFY_URL => [
            'user_message' => 'Please enter verify url',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_WALLET_USER_MISSING_WALLET => [
            'user_message' => 'User is missing a wallet',
            'devMessage' => 'User does not have a wallet',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_OFFERING_INVEST_MISSING_OFF_ID => [
            'user_message' => 'Please enter offering_id',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_ASSET_MISSING_DISPLAY_NAME => [
            'user_message' => 'Please enter display_name',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_WALLET_MISSING_CURRENCY => [
            'user_message' => 'Please enter currency',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_WALLET_TRANS_MISSING_TRANS_CURRENCY => [
            'user_message' => 'Please enter transaction_currency',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_WALLET_TRANS_MISSING_TRANS_AMOUNT => [
            'user_message' => 'Please enter transaction_amount',
            'devMessage' => '',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * The insufficient entitlements to perform action
         */
        self::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION => [
            'user_message' => 'You have insufficient entitlements to perform the current action',
            'devMessage' => 'User has tried to execute an action that he doesn\'t have the right entitlements to perform',
            'http' => Response::HTTP_FORBIDDEN,
        ],

        /**
         * The insufficient entitlements to access route
         */
        self::ERROR_INSUFFICIENT_ENTITLEMENTS => [
            'user_message' => 'You have insufficient entitlements to access this route',
            'devMessage' => 'User has insuffient entitlements/roles to access the route',
            'http' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],

        /**
         * The supplied request is missing data
         */
        self::ERROR_SYSTEM_ERROR => [
            'user_message' => 'An unexpected error occurred during your operation, contact support team',
            'devMessage' => 'An unexpected error occurred, please check logs',
            'http' => Response::HTTP_UNPROCESSABLE_ENTITY,
        ],

        /**
         * The supplied request is missing data
         */
        self::ERROR_MISSING_REQUEST_DATA => [
            'user_message' => 'The request is missing request data',
            'devMessage' => 'User made a request without passing any data',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * The supplied network key is either invalid
         * or the client doesn't have permission to access
         * this network
         */
        self::ERROR_NETWORK_INVALID => [
            'user_message' => 'Network Key not recognized - please check that you have entered the correct network key without any typos',
            'devMessage' => 'No network with key was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        self::ERROR_INSUFFICIENT_PARAMS => [
            'user_message' => 'Not enough parameters - Please make sure you are passing in all the required values',
            'devMessage' => 'Some required parameters are missing from the request',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested user was not found. We look users up by their emails, so a mistyped email will not fetch any
         * results.
         */
        self::ERROR_USER_NOT_FOUND => [
            'user_message' => 'Email address not recognized - please check that you have entered the correct email address without any typos',
            'devMessage' => 'No User with this email address was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested user was not found. We look users up by their emails, so a mistyped email will not fetch any
         * results.
         */
        self::ERROR_USER_TOKEN_EXPIRED => [
            'user_message' => 'Your password reset link has expired - please request a new verification email and click the link within 24 hours',
            'devMessage' => 'The User\'s password reset link has expired after 24 hours. Please allow the User to request a new link in order to reset their password.',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested user was not found. We look users up by their emails, so a mistyped email will not fetch any
         * results.
         */
        self::ERROR_USER_PASSWORD_DONT_MATCH => [
            'user_message' => 'Your passwords dont match',
            'devMessage' => 'The password and password_confirm values must be the same',
            'http' => Response::HTTP_CONFLICT,
        ],

        /**
         * The requested Asset was not found.
         * Asset lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_ASSET_NOT_FOUND => [
            'user_message' => 'The requested asset could not be located',
            'devMessage' => 'No Asset with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Wallet was not found.
         * Wallet lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_WALLET_NOT_FOUND => [
            'user_message' => 'The requested wallet could not be located',
            'devMessage' => 'No Wallet with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * Something failed while trying to do a mangopay->PayIns->Create
         */
        self::ERROR_MANGOPAY_CARD_PAYIN_FAILED => [
            'user_message' => 'An unknown error ocurred whilst trying add funds to your wallet using a card.',
            'devMessage' => 'An unknown error ocurred whilst trying add funds to your wallet using a card.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * Something failed while trying to do a mangopay->Transfers->Create
         */
        self::ERROR_MANGOPAY_TRANSFER_FAILED => [
            'user_message' => 'Your investment has not been successful; your account is still being validated. Please contact the admin team on <a href="mailto:team@yielders.co.uk">team@yielders.co.uk</a> or call 0207 2054650, if the issue persists after 48 hours.',
            'devMessage' => 'An unknown error ocurred whilst trying to transfer funds.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * Something failed while trying to do a mangopay->Users->CreateBankAccount
         */
        self::ERROR_MANGOPAY_BANK_ACCOUNT_FAILED => [
            'user_message' => 'An unknown error ocurred whilst trying to create a mangopay bank account for a user.',
            'devMessage' => 'An unknown error ocurred whilst trying to create a mangopay bank account for a user.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * Something failed while trying to do a mangopay->Users->CreateBankAccount
         */
        self::ERROR_MANGOPAY_REGISTER_CARD_FAILED => [
            'user_message' => 'An unknown error ocurred whilst trying to register a card with mangopay.',
            'devMessage' => 'An unknown error ocurred whilst trying to register a card with mangopay.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * Something failed while trying to do a mangopay->Users->CreateKYCDocuement
         */
        self::ERROR_MANGOPAY_CREATE_KYC_DOCUMENT_FAILED => [
            'user_message' => 'An unknown error ocurred whilst trying to create a mangopay kyc document.',
            'devMessage' => 'An unknown error ocurred whilst trying to create a mangopay kyc document.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        /**
         * Some unknown failure when doing a mangopay call
         */
        self::ERROR_MANGOPAY_UNKNOWN => [
            'user_message' => 'An unknown error ocurred whilst trying to use the mangopay service',
            'devMessage' => 'An unknown error ocurred whilst trying to use the mangopay service',
            'http' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ],

        /**
         * The requested Offering was not found.
         * Offering lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_OFFERING_NOT_FOUND => [
            'user_message' => 'The requested offering could not be located',
            'devMessage' => 'No offering with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Offering Update field is empty .
         */
        self::ERROR_OFFERING_EMPTY_FIELD => [
            'user_message' => 'Please give data to make  update ',
            'devMessage' => 'Field is empty',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /*
         * The requested Investment in settled state .
         */
        self::ERROR_INVESTMENT_IN_SETTLED_STATE => [
            'user_message' => 'investment in settled state ',
            'devMessage' => 'Investment in settled state',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Seconday Offering was not found.
         * Seconday Offering lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_SECONDARY_OFFERING_NOT_FOUND => [
            'user_message' => 'The requested seconday offering could not be located',
            'devMessage' => 'No seconday offering with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Investment was not found.
         * Investment lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_INVESTMENT_NOT_FOUND => [
            'user_message' => 'The requested investment could not be located',
            'devMessage' => 'No investment with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Investment was not found.
         * Investment lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_INVESTMENT_DOCUMENT_NOT_FOUND => [
            'user_message' => 'The requested investment document could not be located',
            'devMessage' => 'No investment document with this investment id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * The requested Investment  was not found.
         * Investment lookup is done by the ID, which is expected to be an integer.
         */
        self::ERROR_PAYOUT_NOT_FOUND => [
            'user_message' => 'The requested payout could not be located',
            'devMessage' => 'No payout with this id was found',
            'http' => Response::HTTP_NOT_FOUND,
        ],

        /**
         * Request to settlement failed as the investor is blocked
         */
        self::ERROR_INVESTMENT_NOT_SETTLED_INVESTOR_BLOCKED => [
            'user_message' => 'The investment cannot be settled ',
            'devMessage' => 'The investor for investment is blocked',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         * Request to payout failed as the user is not an investor
         */
        self::ERROR_USER_IS_NOT_AN_INVESTOR => [
            'user_message' => 'You are not an investor.',
            'devMessage' => 'User is not an Investor',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as currency is not valid
         */
        self::ERROR_PAYOUT_CURRENCY_NOT_VALID => [
            'user_message' => 'Please enter a valid currency.',
            'devMessage' => 'Please enter a valid currency code',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as duedate is not valid
         */
        self::ERROR_PAYOUT_DUEDATE_NOT_VALID => [
            'user_message' => 'Please enter a valid duedate.',
            'devMessage' => 'Please enter a valid duedate',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as duedate is not valid
         */
        self::ERROR_PAYOUT_AMMOUNT_NOT_VALID => [
            'user_message' => 'Please enter a valid ammount.',
            'devMessage' => 'Please enter a valid ammount',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as duedate is not valid
         */
        self::ERROR_PAYOUT_TYPE_NOT_VALID => [
            'user_message' => 'Please enter a valid payout type.',
            'devMessage' => 'Please enter a valid payout type',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed - generic
         */
        self::ERROR_PAYOUT_GENERIC => [
            'user_message' => 'Unable to create payout. Please check you have sufficient funds in your wallet.',
            'devMessage' => 'Unable to create payout',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as duedate is not valid
         */
        self::ERROR_INVESTMENT_STATE_IS_MISSING => [
            'user_message' => 'Lifecycle state is missing for the investment.',
            'devMessage' => 'Lifecycle state is missing for the investment.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to payout failed as duedate is not valid
         */
        self::ERROR_INVESTMENT_STATE_CHANGE_NOT_POSSIBLE => [
            'user_message' => 'Lifecycle state change is not possible for the investment.',
            'devMessage' => 'Lifecycle state change is not possible for the investment.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         *
         */
        self::ERROR_USER_STATE_CHANGE_NOT_POSSIBLE => [
            'user_message' => 'Lifecycle state change is not possible for the User.',
            'devMessage' => 'Lifecycle state change is not possible for the User.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         * Request to User Data absent
         */
        self::ERROR_USER_DATA_NOT_PRESENT => [
            'user_message' => 'Enter all data field.',
            'devMessage' => 'Enter all data field .',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_PASSWORD_CAN_NOT_CHANGE => [
            'user_message' => 'Password could not be changed. ',
            'devMessage' => 'User initiated password change failed... check logs',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_PASSWORD_NOT_MATCHING => [
            'user_message' => 'Current password is not matching.',
            'devMessage' => 'Current password is not matching',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_CURRENT_PASSWORD_MISSING => [
            'user_message' => 'Please enter current password.',
            'devMessage' => 'Please enter current password.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         * Request to user password change  failed for current password being invalid
         */
        self::ERROR_USER_CURRENT_PASSWORD_INVALID => [
            'user_message' => 'Current password is not valid',
            'devMessage' => 'Current password is not valid.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_NEW_PASSWORD_MISSING => [
            'user_message' => 'Please enter new password.',
            'devMessage' => 'Please enter new password.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_CONFIRM_PASSWORD_MISSING => [
            'user_message' => 'Please enter confirm password.',
            'devMessage' => 'Please enter confirm password.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_CONFIRM_PASSWORD_NOT_MACHING => [
            'user_message' => 'Confirm password is not matching with new password.',
            'devMessage' => 'Confirm password is not matching with new password.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],
        /**
         * Request to user password change  failed due to miss match
         */
        self::ERROR_USER_PASSWORD_MACHING_WITH_CURRENT_PASSWORD => [
            'user_message' => 'New password is matching with current password.Please enter another password',
            'devMessage' => 'New password is matching with current password.',
            'http' => Response::HTTP_NOT_ACCEPTABLE,
        ],

        self::ERROR_CONTEGO_USER_CHECK_FAILED => [
            'user_message' => 'Failed to do a contego check for user.',
            'devMessage' => 'Failed to do a contego check for user.',
            'http' => Response::HTTP_BAD_REQUEST,
        ],

        self::ERROR_MANGOPAY_DIRECT_DEBIT_MANDATE_EXPIRED => [
            'user_message' => 'The direct debit mandate has expired as it was not confirmed.',
            'devMessage' => 'The user did not confirm the direct debit mandate and has expired.',
            'api_error_code' => 920,
            'http' => Response::HTTP_BAD_REQUEST,
        ],
    ];

    /**
     * ErrorResponse constructor.
     * @param mixed|null $errorCode
     * @param bool $useHttpStatus Whether to set the relevant HTTP status code rather than use 200
     */
    public function __construct(
        $errorCode,
        $message = null,
        bool $useHttpStatus = false,
    ) {
        if (!is_int($errorCode)) {
            return parent::__construct(self::getDefaultError());
        }

        $errorDetails = $this->getErrorDetailsForError($errorCode, $message);

        $responseTemplate = [
            'outcome' => 'fail',
            'data' => $errorDetails['data'],
            'status' => $errorDetails['http'],
        ];
        // Historically, errors returned (incorrectly with status code 200)
        // To minimise disruption, this behaviour will be retained in the legacy APIv1
        // But can be overriden to use the correct status code for a given error via a toggle
        $status = $useHttpStatus ? $errorDetails['http'] : 200;
        parent::__construct($responseTemplate, $status);
    }

    private static function getDefaultError()
    {
        return [
            'data' => [
                'user_message' => 'Unknown Error.',
                'devMessage' => 'Unknown Error',
            ],
            'http' => Response::HTTP_BAD_REQUEST,
        ];
    }

    /**
     * This is proposal1 for calling the error functions
     *
     * advantage here is that everything about one error stays together
     * but if tomorrow we plan to change something, it'll be a lot tough
     *
     * @param $errorCode
     * @return array
     */
    protected function getErrorDetailsForError($errorCode, $message)
    {
        $errorTemplate = self::getDefaultError();

        if (!isset(self::$errorDetails[$errorCode])) {
            return $errorTemplate;
        }

        //we want to add the message generated via to thrown exception to the devMessage
        self::$errorDetails[$errorCode]['devMessage'] =
            self::$errorDetails[$errorCode]['devMessage'] . '[' . $message . ']';

        //@todo we are adding to user_message for now, will remove this later
        //Removing stack strace message as of 2017-11-15
        //self::$errorDetails[ $errorCode ]['user_message'] = self::$errorDetails[ $errorCode ]['user_message'] . "[" . $message . "]";
        // self::$errorDetails[$errorCode]['user_message'] = self::$errorDetails[$errorCode]['user_message'];

        $return = [];

        $return['data'] = array_merge(
            $errorTemplate['data'],
            self::$errorDetails[$errorCode],
        );
        $return['http'] = $return['data']['http'];

        unset($return['data']['http']);

        return $return;
    }
}
