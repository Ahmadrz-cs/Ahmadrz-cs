<?php

/**
 * Created by PhpStorm.
 * User: plok
 * Date: 26/01/17
 * Time: 11:52
 */

namespace App\Service\Util;

use SameerShelavale\PhpCountriesArray\CountriesArray;
use Symfony\Component\Intl\Intl;

class Helper
{
    /**
     * HELPER METHOD TO FORMAT DATES
     *
     */
    public static function formatDate(?\DateTime $date = null)
    {
        if (isset($date)) {
            //we have a date so lets format it
            return $date->format(\DateTime::ATOM);
        } else {
            //date isn't set so return null
            return null;
        }
    }

    /**
     * CONVERTS KEY/VALUE PAIRS INTO INFO FORMAT
     */
    public static function convertInInfo($type, $value)
    {
        return [
            'type' => $type,
            'value' => $value,
        ];
    }

    /**
     * @param $params
     * @param $type
     * @return string
     */
    public static function getInfo($params, $type, $getValue = false)
    {
        if (!empty($params['info'])) {
            foreach ($params['info'] as $data) {
                if ($data['type'] == $type) {
                    if ($getValue) {
                        return $data['value'];
                    } else {
                        return $data;
                    }
                }
            }
        }
        return null;
    }

    /**
     * @param $countryData
     * Can be either the country name (United Kingdom) or ISO country code (GB)
     * @return mixed returns the code (if a name is passed in) and name (if a code is  passed in)
     * @throws \Exception
     */
    public static function getCountryCode($countryData)
    {
        if (empty($countryData)) {
            return '';
        }

        //special situation for Palestinian Territories Issue backoffice_1204
        //doesnt use the array
        if ($countryData == 'Palestinian Territories' || $countryData == 'PS') {
            if ($countryData == 'Palestinian Territories') {
                return 'PS';
            } else {
                return 'Palestinian Territories';
            }
        }

        //special situation for Syria Issue backoffice_1198
        //doesnt use the array
        if ($countryData == 'Syria' || $countryData == 'SY') {
            if ($countryData == 'Syria') {
                return 'SY';
            } else {
                return 'Syrian Arab Republic';
            }
        }

        // This allows us to convert country name to ISO 3166-1 alpha-2
        // e.g. United Kingdom to GB
        // Maybe this should not be here ???
        $countriesByName = CountriesArray::get('name', 'alpha2'); // United Kingdom -> GB
        $countriesByCode = CountriesArray::get('alpha2', 'name'); // GB -> United Kingdom

        if (in_array($countryData, $countriesByName)) {
            //Passing in Code, lets return the Name
            $return_country = $countriesByCode[$countryData];
        } elseif (in_array($countryData, $countriesByCode)) {
            //Passing in the Name, lets return the Code
            $return_country = $countriesByName[$countryData];
        } else {
            // We couldn't find the country by name or code
            throw new \Exception(
                'Unable to translate ['
                . $countryData
                . '] into a valid ISO 3166-1 alpha-2 country code',
            );
        }

        return $return_country;
    }

    /**
     * Search a list of wallets for a specific wallet
     */
    public static function findWalletinList($wallet_id, $walletList)
    {
        //format
        // [{"Owners":["20549155"],"Description":"Crowdtek Wallet",
        //"Balance":{"Currency":"GBP","Amount":660575},"Currency":"GBP",
        //"Id":"wlt_m_01HW3CMGAM5NJJ01RM8TQ6H8BS","Tag":"Custom TAG","CreationDate":1486385575},

        foreach ($walletList as $wallet) {
            if ($wallet->Id === $wallet_id) {
                return true;
            }
        }
        return false;
    }

    /**
     * special function to remove the content (ie the document content) from the request
     * so it can be printed to the log file
     *
     * @param $content
     * @return string
     */
    public static function cleanDocumentLogger($content)
    {
        $return = json_decode($content, true);

        unset($return['document_content']);

        return json_encode($return);
    }

    /**
     * Goes through array and turns boolean into 1 or 0
     * Primarily intended for Salesforce interaction
     *
     * @param $array
     * @return array
     */
    public static function convertArrayBoolsToInt($array)
    {
        foreach ($array as $key => $value) {
            if (gettype($value) == 'boolean') {
                $array[$key] = (int) $value;
            }
        }
        return $array;
    }

    /**
     * Handle different mangopay account types and map to
     * fields on our own api route
     *
     * @param $array
     * @return array
     */
    public static function handleMangopayBankAccounts($mpAccount)
    {
        /**
         * result will be an array with keys:
         * - id
         * - type
         * - active
         * - owner_name
         * - creation_date
         * - account_number
         * - sort_code [if type is GB]
         */

        $result = [];

        // be extra cautious for now and use try catch block for managing account info
        try {
            if (isset($mpAccount['Details'])) {
                if ($mpAccount['Type'] == 'GB') {
                    $result['account_number'] = $mpAccount['Details']['AccountNumber'];
                    $result['sort_code'] = $mpAccount['Details']['SortCode'];
                }
                if ($mpAccount['Type'] == 'IBAN') {
                    // set IBAN to "account_number" field for simplicity (plus the "AN" literally stands for account number)
                    $result['account_number'] = $mpAccount['Details']['IBAN'];

                    // don't include BIC - IBAN is already unique and includes all the necessary identifiable info
                }
            } else {
                if ($mpAccount['Type'] == 'GB') {
                    $result['account_number'] = $mpAccount['AccountNumber'];
                    $result['sort_code'] = $mpAccount['SortCode'];
                }
                if ($mpAccount['Type'] == 'IBAN') {
                    $result['account_number'] = $mpAccount['IBAN'];
                }
            }
        } catch (\Exception $e) {
            // if problem setting account info, don't include it
        }

        $result['id'] = $mpAccount['Id'];
        $result['type'] = $mpAccount['Type'];
        $result['active'] = $mpAccount['Active'];
        $result['owner_name'] = $mpAccount['OwnerName'];
        $result['created_at'] = $mpAccount['CreationDate'];

        return $result;
    }

    /**
     * Converts array of numbers into cumulative form
     *
     * Input:   [2,4,1,5,6,1,8]
     * Output:  [2,6,7,12,18,19,27]
     */
    public static function convertArrayToCumulative($array, $baseAmount = 0)
    {
        $cumulativeArray = $array;
        for ($i = 0; $i < count($cumulativeArray); $i++) {
            if ($i == 0) {
                $cumulativeArray[$i] += $baseAmount;
            } else {
                $cumulativeArray[$i] += $cumulativeArray[$i - 1];
            }
        }
        return $cumulativeArray;
    }

    /**
     * Generate date strings for past N months
     * Default is last 12 months (including current) in format YYYY-MM in ascending order
     */
    public static function generatePastMonthsStrings(
        $numberOfMonths = 12,
        $offsetFromToday = 0,
        $dateFormat = 'Y-m',
        $order = 'ASC',
    ) {
        $months = [];
        $currentDate = date($dateFormat, time());
        for ($i = $offsetFromToday; $i < ($offsetFromToday + $numberOfMonths); $i++) {
            $months[] = date($dateFormat, strtotime("{$currentDate} -{$i} months"));
        }

        if ($order == 'ASC') {
            $months = array_reverse($months);
        }
        return $months;
    }

    /**
     * Convert roles array into serializable for debugging
     */
    public static function getSerializableRoles(array $listOfRoles): array
    {
        $roles = [];
        foreach ($listOfRoles as $role) {
            $roles[] = $role->getRole();
        }
        return $roles;
    }

    public static function isValidDate(string $input): bool
    {
        return (bool) preg_match(
            '~^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$~',
            $input,
        );
    }

    /**
     * Helper to get the mime type of a file
     */
    public static function getFileMimeType(string $fileContent): ?string
    {
        $finfo = finfo_open();
        $mimeType = finfo_buffer($finfo, $fileContent, FILEINFO_MIME_TYPE);
        finfo_close($finfo);

        if ($mimeType) {
            return $mimeType;
        }

        return null;
    }

    /**
     * @template T
     * @param T[] $entityArray
     * @return T[]
     */
    public static function convertArrayKeysAsIds(array $entityArray): array
    {
        /**
         * Converts an array or ArrayCollection (iterable) of entities
         * Into an array where the Id is the key
         */
        $arrayWithIdKeys = [];
        foreach ($entityArray as $entity) {
            $arrayWithIdKeys[$entity->getId()] = $entity;
        }
        return $arrayWithIdKeys;
    }

    public static function stringifyKeyValuePairs(
        array $kvPairs,
        string $separator = ';',
    ): string {
        $stringified = '';
        foreach ($kvPairs as $key => $value) {
            $stringified .= "$key:$value$separator";
        }
        return $stringified;
    }

    public static function preparePhoneNumber(string $phoneNumberString): ?string
    {
        // If the string contains any alphabet characters, we'll return null to indicate it is invalid
        // There's no obvious way to fix it
        if (preg_match('/[a-z]/i', $phoneNumberString)) {
            return null;
        }

        // For everything any, strip any character that isn't a digit or a plus sign
        $plusAndNumbersOnly = preg_replace('/[^\d\+]/i', '', $phoneNumberString);

        // For numbers strating with zero, we will prefix with the UK country code as the default
        $firstCharacter = substr($plusAndNumbersOnly, 0, 1);
        if ($firstCharacter == '0') {
            $plusAndNumbersOnly = '+44' . substr($plusAndNumbersOnly, 1);
        }
        // E.164 numbers are at most 15 digits long and the plus sign
        if ($firstCharacter == '+') {
            $plusAndNumbersOnly = '+' . substr($plusAndNumbersOnly, 1, 15);
        }
        // There will still be plenty of invalid numbers, but users can fix it on Mangopay during SCA enrollment
        return $plusAndNumbersOnly;
    }
}
