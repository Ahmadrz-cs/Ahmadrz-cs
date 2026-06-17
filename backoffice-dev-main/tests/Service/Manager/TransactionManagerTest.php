<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 24/01/19
 * Time: 20:14
 */

namespace App\Tests\Service\Manager;

use App\Entity\Investment;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use App\Service\Manager\TransactionManager;
use App\Test\FixtureTestCase;
use App\Test\Util\EntityIdTestUtil;

// use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TransactionManagerTest extends FixtureTestCase
{
    private TransactionManager $service;

    protected function setUp(): void
    {
        // self::bootKernel();
        parent::setUp();
        $this->service = static::getContainer()->get(TransactionManager::class);
    }

    public function testCreateTransaction_NormalInvestment(): void
    {
        // Analyst user investing in an asset
        $transactionRequest = [
            'amount' => 10250,
            'user_wallet_id' => 'wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV',
            'org_wallet_id' => 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
            'currency' => 'GBP',
            'fee_amount' => '0',
            'card_type' => 'CB_VISA_MASTERCARD',
        ];
        $transactionResponse = [
            'DebitedWalletId' => 'wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV',
            'CreditedWalletId' => 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
            'AuthorId' => 'user_m_01HW3EF0GMCSYHYNC3Y8B5GY59',
            'CreditedUserId' => 'user_m_01HW3CCXCYF1W0QNYE3KXVNRRQ',
            'DebitedFunds' => [
                'Currency' => 'GBP',
                'Amount' => 10250,
            ],
            'CreditedFunds' => [
                'Currency' => 'GBP',
                'Amount' => 10250,
            ],
            'Fees' => [
                'Currency' => 'GBP',
                'Amount' => 0,
            ],
            'Status' => 'SUCCEEDED',
            'ResultCode' => '000000',
            'ResultMessage' => 'Success',
            'ExecutionDate' => 1713774420,
            'Type' => 'TRANSFER',
            'Nature' => 'REGULAR',
            'Id' => 'xfer_m_01HW5R4MH8BZCBN9SEDVRR9XBJ',
            'Tag' => 'Transfer: wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV to wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
            'CreationDate' => 1713774419,
        ];

        // The transaction request and response are objects...rather than arrays
        $transactionId = $this->service->createTransaction(
            json_decode(json_encode($transactionRequest)),
            json_decode(json_encode($transactionResponse)),
            TRANS_TYPE_CONSTANT::TRANS_NP,
        );

        /** @var TransactionRepository $transactionRepository */
        $transactionRepository = static::getContainer()
            ->get(\Doctrine\ORM\EntityManagerInterface::class)
            ->getRepository(Transaction::class);
        $transaction = $transactionRepository->find($transactionId);

        $this->assertEquals(10250, $transaction->getValueAmount());
        $this->assertEquals(
            'wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV',
            $transaction->getDebitedWalletId(),
        );
        $this->assertEquals(
            'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2',
            $transaction->getCreditedWalletId(),
        );
        $this->assertEquals(
            'xfer_m_01HW5R4MH8BZCBN9SEDVRR9XBJ',
            $transaction->getExternalId(),
        );
        $this->assertEquals('SUCCEEDED', $transaction->getPaymentStatus());
    }

    public function testCreateInvestmentTransaction(): void
    {
        $user = EntityIdTestUtil::setEntityId(new User(), 5146);
        $investment = EntityIdTestUtil::setEntityId(new Investment(), 13681);
        $investment->setUser($user);
        $transfer = new \MangoPay\Transfer();
        $transfer->Id = 'xfer_c_01JXAWYTQVQD78EC5JMD822AX0';
        $debit = new \MangoPay\Money();
        $debit->Amount = 40684;
        $debit->Currency = 'GBP';
        $fee = new \MangoPay\Money();
        $fee->Amount = 50;
        $fee->Currency = 'GBP';
        $transfer->DebitedFunds = $debit;
        $transfer->Fees = $fee;
        $transfer->Status = \MangoPay\TransactionStatus::Created;
        $transfer->DebitedWalletId = 'wlt_m_01HW3EFRH8GM978NHCM8ZGGXGV';
        $transfer->CreditedWalletId = 'wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2';

        $actual = $this->service->createInvestmentTransaction($investment, $transfer);

        $this->assertEquals(40684, $actual->getValueAmount());
        $this->assertEquals(50, $actual->getFeeAmount());
        $this->assertEquals($transfer->DebitedWalletId, $actual->getDebitedWalletId());
        $this->assertEquals(
            $transfer->CreditedWalletId,
            $actual->getCreditedWalletId(),
        );
        $this->assertEquals($transfer->Id, $actual->getExternalId());
        $this->assertEquals($transfer->Status, $actual->getPaymentStatus());
        $this->assertEquals($transfer->DebitedFunds->Currency, $actual->getCurrency());
        $this->assertEquals($user->getId(), $actual->getDebitorId());
        $this->assertEquals($investment->getId(), $actual->getInvId());
        $this->assertEquals(TRANS_TYPE_CONSTANT::TRANS_NP, $actual->getTransType());
    }

    public function testUpdateInvestmentIdOnTransaction(): void
    {
        // $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $em = $this->entityManager;

        $transaction = new \App\Entity\Transaction();
        $investment = new \App\Entity\Investment();
        $investment->setName('investment transaction test');
        $transaction->setReferenceId('00000');
        $em->persist($investment);
        $em->persist($transaction);
        $em->flush();

        $this->service->updateInvestmentIdonTransaction(
            $transaction->getReferenceId(),
            $investment->getId(),
        );
        $this->assertSame($investment->getId(), $transaction->getInvId());
    }

    public function testScenarioUpdateInvestmentIdOnTransactionNoTransactionFound(): void
    {
        $this->assertNull($this->service->updateInvestmentIdonTransaction(0, 0));
    }
}
