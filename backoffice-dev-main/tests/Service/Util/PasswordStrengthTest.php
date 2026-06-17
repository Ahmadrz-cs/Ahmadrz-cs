<?php

/**
 * Created by PhpStorm.
 * User: plok
 * Date: 28/09/17
 * Time: 22:26
 */

namespace App\Tests\Service\Util;

use App\Service\Util\PasswordStrengthValidator;

class PasswordStrengthTest extends \PHPUnit\Framework\TestCase
{
    public function testPasswordLength(): void
    {
        $passwordEmpty = '';

        /** @var PasswordStrengthValidator $psv */
        $psv = new PasswordStrengthValidator();
        $response = $psv->validate($passwordEmpty);
        $this->assertEquals($psv->tooShortMessage, $response);

        $passwordSmall = '123';
        $response = $psv->validate($passwordSmall);
        $this->assertEquals($psv->tooShortMessage, $response);

        $passwordSmall = '1234567';
        $response = $psv->validate($passwordSmall);
        $this->assertEquals($psv->tooShortMessage, $response);

        //        $passwordSmall = 'Abcd12345';
        //        $response = $psv->validate($passwordSmall);
        //        $this->assertEquals($psv->requireSpecicalChareMessage, $response);

        $passwordSmall = '12345678';
        $response = $psv->validate($passwordSmall);
        $this->assertEquals($psv->missingLettersMessage, $response);

        $passwordLarge = '12345678910';
        $response = $psv->validate($passwordLarge);
        $this->assertEquals($psv->missingLettersMessage, $response);
    }

    public function testPasswordLetters(): void
    {
        $password_numbers = '12345678';

        /** @var PasswordStrengthValidator $psv */
        $psv = new PasswordStrengthValidator();
        $response = $psv->validate($password_numbers);
        $this->assertEquals($psv->missingLettersMessage, $response);

        $passwordSmall = 'abcdef';
        $response = $psv->validate($passwordSmall);
        $this->assertEquals($psv->tooShortMessage, $response);

        $password = 'abcdefg';
        $response = $psv->validate($password);
        $this->assertEquals($psv->tooShortMessage, $response);

        $password = 'abcdefgh';
        $response = $psv->validate($password);
        $this->assertEquals($psv->requireCaseDiffMessage, $response);

        $password = 'abcdefgH';
        $response = $psv->validate($password);
        $this->assertEquals($psv->missingNumbersMessage, $response);
    }

    public function testPasswordLettersNumbers(): void
    {
        /** @var PasswordStrengthValidator $psv */
        $psv = new PasswordStrengthValidator();

        $password = 'abcdefgDSDFDSFDSd';
        $response = $psv->validate($password);
        $this->assertEquals($psv->missingNumbersMessage, $response);

        $password = 'abcdefgH2!';
        $response = $psv->validate($password);
        $this->assertEquals('', $response);
    }

    public function testPasswordSpecialChars(): void
    {
        /** @var PasswordStrengthValidator $psv */
        $psv = new PasswordStrengthValidator();

        $password = '123HE45yz';
        $response = $psv->validate($password);
        $this->assertEquals('', $response);

        $password = 'AbCd123!@#';
        $response = $psv->validate($password);
        $this->assertEquals('', $response);
    }
}
