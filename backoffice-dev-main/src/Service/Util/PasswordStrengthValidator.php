<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 28/09/17
 * Time: 22:04
 */

namespace App\Service\Util;

class PasswordStrengthValidator
{
    private $minLength = 8;
    public $tooShortMessage = 'Your password must be at least 8 characters long.';
    public $missingLettersMessage = 'Your password must include at least one letter.';
    public $requireCaseDiffMessage = 'Your password must include both upper and lower case letters.';
    public $missingNumbersMessage = 'Your password must include at least one number.';

    //    public $requireSpecicalChareMessage
    //        = 'Your password must contain special chars.';

    public function validate($value)
    {
        if ($value === null) {
            $value = '';
        }

        if (function_exists('grapheme_strlen')) {
            $length = grapheme_strlen($value);
        } else {
            $length = mb_strlen($value, 'UTF-8');
        }

        if ($length < $this->minLength) {
            return $this->tooShortMessage;
        }

        if (!preg_match('/\pL/', $value)) {
            return $this->missingLettersMessage;
        }

        if (!preg_match('/(\p{Ll}+.*\p{Lu})|(\p{Lu}+.*\p{Ll})/', $value)) {
            return $this->requireCaseDiffMessage;
        }

        if (!preg_match('/\pN/', $value)) {
            return $this->missingNumbersMessage;
        }

        //        if(!preg_match('[\W]',$value))
        //        {
        //            return $this->requireSpecicalChareMessage;
        //        }
    }
}
