<?php

/**
 * Created by PhpStorm.
 */

namespace App\Tests\Entity;

use App\Entity\Payout;
use App\Entity\PayoutAddFields;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class PayoutAddFieldsTest
 * @package App\Tests\Entity
 */
class PayoutAddFieldsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * function testFieldKey
     */
    public function testFieldKey(): void
    {
        try {
            $payoutAddFields = new PayoutAddFields();

            $payoutAddFields->setFieldKey('field 1');
            $result = $payoutAddFields->getFieldKey();
            $this->assertEquals('field 1', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * function testFieldKey
     */
    public function testValue(): void
    {
        try {
            $payoutAddFields = new PayoutAddFields();

            $payoutAddFields->setFieldValue('Sayak Mukherjee');
            $result = $payoutAddFields->getFieldValue();
            $this->assertEquals('Sayak Mukherjee', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    /**
     * testSetPayout
     */
    public function testSetPayout(): void
    {
        $payoutAddFields = new PayoutAddFields();
        $payout = new Payout();
        $this->assertNull($payoutAddFields->getPayout());

        $payoutAddFields->setPayout($payout);
        $this->assertEquals($payout, $payoutAddFields->getPayout());
    }
}
