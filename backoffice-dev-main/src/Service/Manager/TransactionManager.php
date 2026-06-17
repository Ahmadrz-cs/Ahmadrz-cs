<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 11/12/18
 * Time: 23:36
 */

namespace App\Service\Manager;

use App\Entity\Investment;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Entity\Transaction;
use App\Entity\User;
use App\Service\Manager\BaseManager;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionManager extends BaseManager
{
    protected $entityClass = Transaction::class;

    public function findAllTransactions()
    {
        $resultValues = $this->findAllValue();
        return $resultValues;
    }

    /**
     * Using the transfer data create a transaction
     */
    public function createTransaction(
        $transfer_request,
        $transfer_response,
        $payment_type,
        $user = null,
    ) {
        $this->getLogger()->info(
            'transfer_req: ' . new JsonResponse($transfer_request),
        );

        //input
        //$transfer_request= {"amount":10250,"user_wallet_id":"22457558","org_wallet_id":"60769409","currency":"GBP","fee_amount":"0","card_type":"CB_VISA_MASTERCARD"}

        // {"DebitedWalletId":"22457558",
        //"CreditedWalletId":"60769409","AuthorId":"22457557","CreditedUserId":"20549155",
        //"DebitedFunds":{"Currency":"GBP","Amount":10250},
        //"CreditedFunds":{"Currency":"GBP","Amount":10250},"Fees":{"Currency":"GBP","Amount":0},
        //"Status":"SUCCEEDED","ResultCode":"000000","ResultMessage":"Success","ExecutionDate":1548339582,
        //"Type":"TRANSFER","Nature":"REGULAR","Id":"60773041","Tag":"Transfer: 22457558 to 60769409","CreationDate":1548339582}

        $this->getLogger()->info(
            'transfer_resp: ' . new JsonResponse($transfer_response),
        );

        $em = $this->getEntityManager();
        $user_repo = $em->getRepository(User::class);

        //special case for moving funds from asset wallet to a user
        if ($user == null) { //get the user id of the debitor
            $arr_users = $user_repo->findBy([
                'mangoPayWalletId' => $transfer_request->user_wallet_id,
            ]);

            $debitor_id = $arr_users[0]->getId();
        } else {
            $debitor_id = $user->getId();
        }

        //lets create a new transaction
        $new_trans = new Transaction();

        $new_trans->setExternalId($transfer_response->Id);
        $new_trans->setValueAmount($transfer_request->amount);
        $new_trans->setDebitedWalletId($transfer_request->user_wallet_id);
        $new_trans->setCreditedWalletId($transfer_request->org_wallet_id);
        $new_trans->setDebitorId($debitor_id);

        $new_trans->setCurrency($transfer_request->currency);
        $new_trans->setFeeAmount($transfer_request->fee_amount);
        $new_trans->setPaymentStatus($transfer_response->Status);
        $new_trans->setTransType($payment_type);

        $em->persist($new_trans);
        $em->flush();

        $internal_trans_id = $new_trans->getId();

        $this->getLogger()->info('Transaction created : ' . $internal_trans_id);

        return $internal_trans_id;
    }

    public function createInvestmentTransaction(
        Investment $investment,
        \Mangopay\Transfer $mangopayTransfer,
    ): Transaction {
        $this->getLogger()->debug('Creating transaction record for investment payment');

        //lets create a new transaction
        $transaction = new Transaction();

        $transaction->setExternalId($mangopayTransfer->Id);
        $transaction->setInvId($investment->getId());
        $transaction->setDebitorId($investment->getUser()->getId());

        $transaction->setValueAmount($mangopayTransfer->DebitedFunds->Amount);
        $transaction->setDebitedWalletId($mangopayTransfer->DebitedWalletId);
        $transaction->setCreditedWalletId($mangopayTransfer->CreditedWalletId);
        $transaction->setCurrency($mangopayTransfer->DebitedFunds->Currency);
        $transaction->setFeeAmount($mangopayTransfer->Fees->Amount);
        $transaction->setPaymentStatus($mangopayTransfer->Status);
        $transaction->setTransType(TRANS_TYPE_CONSTANT::TRANS_NP);

        return $transaction;
    }

    /***
     *
     * update an existing transaction with investment id
     *
     * @param $trans_id
     * @param $invest_id
     * @return mixed
     */
    public function updateInvestmentIdonTransaction($trans_id, $invest_id)
    {
        $this->getLogger()->info($trans_id . ':inv_id::' . $invest_id);

        $em = $this->getEntityManager();

        //get the exiting trans by trans id
        $translist = $this->findAllValue(null, null, ['external_id' => $trans_id]);

        if (empty($translist)) {
            return;
        }

        /** @var Transaction $trans */
        $trans = $translist[0];

        if (isset($trans)) {
            //set the investment id
            $trans->setInvId($invest_id);
            $em->flush();

            return $trans_id;
        }
    }
}
