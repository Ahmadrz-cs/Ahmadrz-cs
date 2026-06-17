<?php

namespace App\Exception;

class ApiException extends \Exception
{
    // List of possible error codes
    //MangoPay Error codes
    public const ERROR_MANGOPAY_USER_ALREADY_EXISTS = 101;
    public const ERROR_MANGOPAY_CARD_REGISTRATION_FAILED = 102;

    public const ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT = 915;
    public const ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET = 916;

    public const ERROR_USER_NOT_FOUND = 801;

    public const ERROR_NETWORK_INVALID = 900;

    // Array containing human readable error messages with the corresponding error codes
    protected static $messages = [
        self::ERROR_MANGOPAY_USER_ALREADY_EXISTS => 'User is already registered with MangoPay',
        self::ERROR_MANGOPAY_CARD_REGISTRATION_FAILED => 'An error occured while trying to register a new credit/debit card with Mangopay',
        self::ERROR_NETWORK_INVALID => 'The network key is invalid',
        self::ERROR_USER_NOT_FOUND => 'A User with that email doesn\'t exist',
        self::ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT => 'Transaction amount is higher than maximum permitted amount',
        self::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET => 'Insufficient funds, please top up your wallet.',
    ];

    /**
     * Gets the Error message for an Error code
     *
     * @param $key int the error code e.g. 101 as defined above
     * @return mixed|string The human readable error message if the code is found and "Unknown error" otherwise
     */
    public static function getErrorMessage($key)
    {
        if (!isset(static::$messages[$key])) {
            return 'Unknown Error';
        }

        return static::$messages[$key];
    }
}
