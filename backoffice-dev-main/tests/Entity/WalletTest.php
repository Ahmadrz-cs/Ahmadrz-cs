<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 31/12/16
 * Time: 00:10
 */

namespace App\Tests\Entity;

use App\Entity\Wallet;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class WalletTest
 * @package App\Tests\Entity
 *
 */
class WalletTest extends \PHPUnit\Framework\TestCase
{
    public function testSetCurrency(): void
    {
        try {
            /** @var Wallet $payout_obj */
            $payout_obj = new Wallet();
            $payout_obj->setCurrency('GBP');
            $result = $payout_obj->getCurrency();

            $this->assertEquals('GBP', $result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetFreeBalance(): void
    {
        try {
            /** @var Wallet $wallet_obj */
            $wallet_obj = new Wallet();

            $wallet_obj->setFreeBalance(2500.00);
            $result = $wallet_obj->getFreeBalance();
            $this->assertEquals('2500.00', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetBalance(): void
    {
        try {
            /** @var Wallet $wallet_obj */
            $wallet_obj = new Wallet();

            $wallet_obj->setBalance(1500.00);
            $result = $wallet_obj->getBalance();
            $this->assertEquals('1500.00', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testSetCommittedBalance(): void
    {
        try {
            /** @var Wallet $wallet_obj */
            $wallet_obj = new Wallet();

            $wallet_obj->setCommittedBalance(100.00);
            $result = $wallet_obj->getCommittedBalance();
            $this->assertEquals('100.00', $result);
            unset($result);
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
