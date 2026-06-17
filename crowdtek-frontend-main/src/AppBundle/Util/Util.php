<?php

namespace AppBundle\Util;

use SameerShelavale\PhpCountriesArray\CountriesArray;

class Util
{
    /**
     * @param $params
     * @param $type
     * @param string $default
     * @return string
     */
    public static function getInfo($params, $type, $default = '')
    {
        if (!empty($params['info'])) {
            foreach ($params['info'] as $data) {
                if ($data['type'] == $type) {
                    return $data['value'];
                }
            }
        }
        return $default;
    }

    /**
     * @param $user
     * @return array
     */
    public static function getUserInfoArray($user)
    {
        $user_info_dict = [];
        foreach ($user as $info) {
            $user_info_dict[$info['type']] = $info['value'];
        }
        return $user_info_dict;
    }

    /**
     * @param $array
     * @return array
     */
    public static function convertArrayBoolsToInt($array)
    {
        foreach ($array as $key => $value) {
            if (gettype($value) == "boolean") {
                $array[$key] = (int) $value;
            }
        }
        return $array;
    }

    /**
     * @param int $codeLength
     * @return string
     */
    public static function alphaNumCodeGenerator($codeLength = 5)
    {
        $characters = 'ABCDEFGHIJKLLMNOPQRSTUVWXYZ0123456789';
        $length = strlen($characters) - 1;
        $uniqueId = '';
        for ($i = 0; $i < $codeLength; $i++) {
            $uniqueId .= $characters[rand(0, $length)];
        }
        return $uniqueId;
    }

    /**
     * @param $input
     * @return array
     */
    public static function arrayFilterRecursive($input)
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::arrayFilterRecursive($value);
            }
        }

        return array_filter($input, function ($v) {
            return !is_null($v);
        });
    }

    /**
     *
     * @param type $num
     * @return boolean
     */
    public function convertNumberToWord($num = false)
    {
        $num = str_replace([',', ' '], '', trim($num));
        if (!$num) {
            return false;
        }
        $num = (int) $num;
        $words = [];
        $list1 = [
            '',
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine',
            'ten',
            'eleven',
            'twelve',
            'thirteen',
            'fourteen',
            'fifteen',
            'sixteen',
            'seventeen',
            'eighteen',
            'nineteen'
        ];
        $list2 = ['', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred'];
        $list3 = [
            '',
            'thousand',
            'million',
            'billion',
            'trillion',
            'quadrillion',
            'quintillion',
            'sextillion',
            'septillion',
            'octillion',
            'nonillion',
            'decillion',
            'undecillion',
            'duodecillion',
            'tredecillion',
            'quattuordecillion',
            'quindecillion',
            'sexdecillion',
            'septendecillion',
            'octodecillion',
            'novemdecillion',
            'vigintillion'
        ];
        $num_length = strlen($num);
        $levels = (int) (($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num = substr('00' . $num, -$max_length);
        $num_levels = str_split($num, 3);
        for ($i = 0; $i < count($num_levels); $i++) {
            $levels--;
            $hundreds = (int) ($num_levels[$i] / 100);
            $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ($hundreds == 1 ? '' : 's') . ' ' : '');
            $tens = (int) ($num_levels[$i] % 100);
            $singles = '';
            if ($tens < 20) {
                $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '');
            } else {
                $tens = (int) ($tens / 10);
                $tens = ' ' . $list2[$tens] . ' ';
                $singles = (int) ($num_levels[$i] % 10);
                $singles = ' ' . $list1[$singles] . ' ';
            }
            $words[] = $hundreds . $tens . $singles . (($levels && (int) ($num_levels[$i])) ? ' ' . $list3[$levels] . ' ' : '');
        } //end for loop
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }
        return implode(' ', $words);
    }

    public static function roundToNearestNumber($number, $nearest = 5)
    {
        if ($number < $nearest) {
            return $nearest;
        }

        return ceil($number / $nearest) * $nearest;
    }

    public static function getPrevMonth($date)
    {
        return date('Y-m', strtotime($date . " -1 month"));
    }

    public static function getNextMonth($date)
    {
        return date('Y-m', strtotime($date . " +1 month"));
    }



    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function getAllCountries($keyField, $requestedField)
    {
        $countries = CountriesArray::get($keyField, $requestedField);

        return $countries;
    }

    /** Effectively deprecates roundToNearestNumber() */
    public static function roundToMultiple($number, $multiple, $round = "floor")
    {
        if ($round == "floor") {
            return floor($number / $multiple) * $multiple;
        }
        if ($round == "ceil") {
            return ceil($number / $multiple) * $multiple;
        }
        if ($round == "round") {
            return round($number / $multiple) * $multiple;
        }
    }

    public static function filterArrayBy($array, $key, $matchArray, $method = "allowlist")
    {
        $filteredArray = [];
        foreach ($array as $item) {
            if ($method == "allowlist" && in_array($item[$key], $matchArray)) {
                $filteredArray[] = $item;
            }
            if ($method == "denylist" && !in_array($item[$key], $matchArray)) {
                $filteredArray[] = $item;
            }
        }
        return $filteredArray;
    }

    public static function getAssetTermRemaining(array $assetApiData): int
    {
        if (
            array_key_exists('term_remaining', $assetApiData) &&
            !is_null($assetApiData['term_remaining'])
        ) {
            return $assetApiData['term_remaining'];
        }
        $termLengthMonths = Util::getInfo($assetApiData, 'investment_term', 0);
        $createdAt = $assetApiData['created_at'];
        $termPassed = ceil((time() - strtotime($createdAt)) / (30 * 24 * 3600));
        $termRemaining = intval($termLengthMonths) - $termPassed;
        if ($termRemaining < 0) {
            $termRemaining = 0;
        }
        return $termRemaining;
    }
}
