<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class InvestmentAddFieldsTest
 * @package App\Tests\Entity
 */
class InvestmentAddFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testFieldKey
     */
    public function testFieldKey(): void
    {
        try {
            $investmentAddFields = new InvestmentAddFields();

            $investmentAddFields->setFieldKey('Key1');
            $result = $investmentAddFields->getFieldKey();
            $this->assertEquals('Key1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testFieldValue
     */
    public function testFieldValue(): void
    {
        try {
            $investmentAddFields = new InvestmentAddFields();

            $investmentAddFields->setFieldValue('Sayak Mukherjee');
            $result = $investmentAddFields->getFieldValue();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /*
     * testSetInvestment
     */
    public function testSetInvestment(): void
    {
        $investmentAddFields = new InvestmentAddFields();
        $investment = new investment();
        $this->assertNull($investmentAddFields->getInvestment());

        $investmentAddFields->setInvestment($investment);
        $this->assertEquals($investment, $investmentAddFields->getInvestment());
    }
}
