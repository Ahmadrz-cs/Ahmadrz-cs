<?php

namespace App\Tests\Entity;

use App\Entity\Payout;
use App\Entity\Transaction;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class TransactionTest
 * @package App\Tests\Entity
 */
class TransactionTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateTransaction(): void
    {
        $dW = 1234;
        $cW = 5678;
        $dU = 111;
        $cU = 222;
        $sA = 12300;
        $vA = 8888;
        $ccy = 'GBP';
        $type = 'INWARD';
        $desc = 'divident payment sp 23';
        $off_id = 9999;
        $inv_id = 9900;
        $fee = 3.04;
        $external_id = 10000236;
        $payment_status = 'SUCCEEDED';

        /** @var Transaction $trans */
        $trans = new Transaction();

        $trans->setCreditedWalletId($cW);
        $trans->setDebitedWalletId($dW);
        $trans->setCreditorId($cU);
        $trans->setDebitorId($dU);
        $trans->setShareAmount($sA);
        $trans->setValueAmount($vA);
        $trans->setCurrency($ccy);
        $trans->setTransType($type);
        $trans->setComments($desc);
        $trans->setOfferingId($off_id);
        $trans->setInvId($inv_id);
        $trans->setFeeAmount($fee);
        $trans->setExternalId($external_id);
        $trans->setPaymentStatus($payment_status);

        // assert that the values are returned
        $this->assertEquals($cW, $trans->getCreditedWalletId());
        $this->assertEquals($dW, $trans->getDebitedWalletId());
        $this->assertEquals($cU, $trans->getCreditorId());
        $this->assertEquals($dU, $trans->getDebitorId());
        $this->assertEquals($sA, $trans->getShareAmount());
        $this->assertEquals($vA, $trans->getValueAmount());
        $this->assertEquals($ccy, $trans->getCurrency());
        $this->assertEquals($type, $trans->getTransType());
        $this->assertEquals($desc, $trans->getComments());
        $this->assertEquals($off_id, $trans->getOfferingId());
        $this->assertEquals($inv_id, $trans->getInvId());
        $this->assertEquals($fee, $trans->getFeeAmount());
        $this->assertEquals($external_id, $trans->getExternalId());
        $this->assertEquals($payment_status, $trans->getPaymentStatus());
    }

    public function testGettersAndSetters(): void
    {
        // checks getter and setter aliases

        /** @var Transaction $trans */
        $transaction = new Transaction();

        $transaction->setType('dividend');
        $transaction->setReferenceId('01AB012');
        $transaction->setCreditUserId(15);
        $transaction->setDebitUserId(10);
        $transaction->setCreditResourceId('81AZ1');
        $transaction->setDebitResourceId('510018');
        $transaction->setAmount('25.50');
        $transaction->setFee('5.75');

        $this->assertEquals('dividend', $transaction->getType());
        $this->assertEquals('01AB012', $transaction->getReferenceId());
        $this->assertEquals(15, $transaction->getCreditUserId());
        $this->assertEquals(10, $transaction->getDebitUserId());
        $this->assertEquals('81AZ1', $transaction->getCreditResourceId());
        $this->assertEquals('510018', $transaction->getDebitResourceId());
        $this->assertEquals('25.50', $transaction->getAmount());
        $this->assertEquals('5.75', $transaction->getFee());
    }
}
