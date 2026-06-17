<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 02/02/17
 * Time: 19:02
 */

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class WalletController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
    ) {}

    /**
     *
     * @param $request
     * @param $wallet_id
     * @POST("/%api_network_path%/wallets/{wallet_id}/transactions")
     * @return JsonResponse
     */
    #[Rest\View]
    public function createSelfWalletTransaction(Request $request, $wallet_id)
    {
        $this->logger->info($request->getContent());
        /** @var User $logged_user */
        $logged_user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS);
        }

        $data = json_decode($request->getContent());

        if (empty($data)) {
            return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
        }

        if (empty($data->transaction_amount)) {
            return new ErrorResponse(ErrorResponse::ERROR_WALLET_TRANS_MISSING_TRANS_AMOUNT);
        }

        if (empty($data->transaction_currency)) {
            return new ErrorResponse(ErrorResponse::ERROR_WALLET_TRANS_MISSING_TRANS_CURRENCY);
        }

        $trans_id = $this->generateTransactionSchema($data);

        if (!is_int($trans_id)) {
            return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'transaction_id' => $trans_id,
            ],
            'status' => 200,
        ]);
    }

    protected function generateTransactionSchema($param)
    {
        $trans_new = new Transaction();

        if (!empty($param->wallet_id)) {
            $trans_new->setWalletId($param->wallet_id);
        }

        if (!empty($param->transaction_amount)) {
            $trans_new->setValueAmount($param->transaction_amount);
        }

        if (!empty($param->transaction_currency)) {
            $trans_new->setCurrency($param->transaction_currency);
        }

        if (!empty($param->transaction_description)) {
            $trans_new->setTransactionDescription($param->transaction_description);
        }

        if (!empty($param->payment_status)) {
            $trans_new->setPaymentStatus($param->payment_status);
        }

        if (!empty($param->payment_service)) {
            $trans_new->setPaymentStatus($param->payment_service);
        }

        if (!empty($param->payout_id)) {
            $trans_new->setPayoutId($param->payout_id);
        }

        if (!empty($param->original_transaction_amount)) {
            $trans_new->setOriginalTransactionAmount($param->original_transaction_amount);
        }

        if (!empty($param->original_transaction_currency)) {
            $trans_new->setOriginalTransactionCurrency($param->original_transaction_currency);
        }

        if (!empty($param->payment_service_log_id)) {
            $trans_new->setPaymentServiceLogId($param->payment_service_log_id);
        }

        if (!empty($param->confirmation_number)) {
            $trans_new->setConfirmationNumber($param->confirmation_number);
        }

        $em = $this->doctrine->getManager();
        /** @var \App\Repository\TransactionRepository */
        $repository = $em->getRepository(Transaction::class);
        $repository->save($trans_new);

        $em->flush();

        $transId = $trans_new->getId();

        return $transId;
    }
}
