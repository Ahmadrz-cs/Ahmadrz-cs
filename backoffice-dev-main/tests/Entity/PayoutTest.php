<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 13/12/16
 * Time: 19:32
 */

namespace App\Tests\Entity;

use App\Entity\Investment;
use App\Entity\Payout;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class PayoutTest
 * @package App\Tests\Entity
 *
 */
class PayoutTest extends \PHPUnit\Framework\TestCase
{
    public function testSetAdditionalType(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();

            $payout_obj->setAdditionalType('Super payment');
            $result = $payout_obj->getAdditionalType();

            $this->assertEquals('Super payment', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetPayoutAmount(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();

            $payout_obj->setPayoutAmount(1500.00);
            $result = $payout_obj->getPayoutAmount();
            $this->assertEquals('1500.00', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetPayOutType(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();

            $payout_obj->setPayoutType(1);
            $result = $payout_obj->getPayoutType();
            $this->assertEquals(1, $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetDueDate(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();

            $payout_obj->setDueDate(new \DateTime('2016/01/20'));
            /** @var \DateTime $result */
            $result = $payout_obj->getDueDate();
            $this->assertEquals(new \DateTime('2016/01/20'), $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCurrency(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();
            $payout_obj->setCurrency('GBP');
            $result = $payout_obj->getCurrency();

            $this->assertEquals('GBP', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetInvestment(): void
    {
        try {
            /** @var Payout $payout_obj */
            $payout_obj = new Payout();

            /** @var Investment $invest_obj */
            $invest_obj = new Investment();
            $invest_obj->setName('my investment');

            $payout_obj->setInvestment($invest_obj);

            /** @var Investment $invest_obj */
            $result = $payout_obj->getInvestment();

            $this->assertEquals($invest_obj->getName(), $result->getName());
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
