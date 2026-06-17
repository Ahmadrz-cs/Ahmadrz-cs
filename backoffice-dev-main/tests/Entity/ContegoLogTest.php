<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 29/01/17
 * Time: 00:54
 */

namespace App\Tests\Entity;

use App\Entity\ContegoLog;
use App\Entity\User;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class ContegoLogTest
 * @package App\Tests\Entity
 */
class ContegoLogTest extends \PHPUnit\Framework\TestCase
{
    public function testSetUser(): void
    {
        try {
            $con_log = new ContegoLog();
            $user_obj = new User();

            $result_obj = $user_obj->getId();
            $con_log->setUser($user_obj);

            $result_get = $con_log->getUser();

            $this->assertEquals($user_obj, $result_get);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetProfileName(): void
    {
        try {
            /** @var ContegoLog $cont_log */
            $cont_log = new ContegoLog();
            $check_value = 'AML Check';
            $cont_log->setProfileName($check_value);
            $result = $cont_log->getProfileName();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetRAG(): void
    {
        try {
            /** @var ContegoLog $cont_log */
            $cont_log = new ContegoLog();
            $check_value = 'AMBER';
            $cont_log->setRAG($check_value);
            $result = $cont_log->getRAG();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetKYCScore(): void
    {
        try {
            /** @var ContegoLog $cont_log */
            $cont_log = new ContegoLog();
            $check_value = '175';
            $cont_log->setKycScore($check_value);
            $result = $cont_log->getKycScore();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetKYCType(): void
    {
        try {
            /** @var ContegoLog $cont_log */
            $cont_log = new ContegoLog();
            $check_value = 'Person Check';
            $cont_log->setKycType($check_value);
            $result = $cont_log->getKycType();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetExtReferenceId(): void
    {
        try {
            /** @var ContegoLog $cont_log */
            $cont_log = new ContegoLog();
            $check_value = '123465';
            $cont_log->setExtReferenceId($check_value);
            $result = $cont_log->getExtReferenceId();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
