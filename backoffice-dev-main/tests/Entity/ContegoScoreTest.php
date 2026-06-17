<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 04/02/17
 * Time: 01:06
 */

namespace App\Tests\Entity;

use App\Entity\ContegoScore;
use App\Entity\User;

/**
 * Class ContegoScoreTest
 * @package App\Tests\Entity
 */
class ContegoScoreTest extends \PHPUnit\Framework\TestCase
{
    public function testSetRAG(): void
    {
        try {
            /** @var ContegoScore $cont_score */
            $cont_score = new ContegoScore();
            $check_value = 'AMBER';
            $cont_score->setRAG($check_value);
            $result = $cont_score->getRAG();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetKYCScore(): void
    {
        try {
            /** @var ContegoScore $cont_score */
            $cont_score = new ContegoScore();
            $check_value = '175';
            $cont_score->setKycScore($check_value);
            $result = $cont_score->getKycScore();

            $this->assertEquals($check_value, $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
