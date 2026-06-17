<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\User;
use App\Entity\Wallet;
use App\Service\MangoPay;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class SelfWalletController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MangoPay $mangopayService,
    ) {}

    #[Get('%api_network_path%/self/mangopay/wallet')]
    #[Rest\View]
    public function getselfMangoPayWalletSca(
        #[CurrentUser] User $user,
        #[MapQueryParameter] ?bool $sca = null,
    ): ErrorResponse|JsonResponse {
        $this->logger->info("Getting wallet for self user #{$user->getId()}");

        if (!$user->isAMangoPayUser()) {
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID);
        }
        if (!$user->getMangoPayWalletId()) {
            return new ErrorResponse(ErrorResponse::ERROR_WALLET_USER_MISSING_WALLET);
        }
        // null for ScaContext means Mangopay will use whatever is their default
        if ($sca === null) {
            $scaContext = $sca;
        } else {
            $scaContext = $sca ? 'USER_PRESENT' : 'USER_NOT_PRESENT';
        }
        try {
            $mangopayWallet = $this->mangopayService->getSingleWallet(
                walletId: $user->getMangoPayWalletId(),
                scaContext: $scaContext,
            );
        } catch (\MangoPay\Libraries\ResponseException $re) {
            $this->logger->error('Error getting user mangopay wallet', [
                $re->GetErrorDetails()->Type,
                $re->GetErrorDetails()->Message,
            ]);
            // Specifically handle SCA authentication error
            // See official SDK tests for further info on how SCA works for wallets
            // as it's a bit different to SCA on users/transfers
            // https://github.com/Mangopay/mangopay2-php-sdk/blob/master/tests/Cases/WalletsTest.php
            if (
                401 === $re->getCode()
                && isset($re->GetErrorDetails()->Errors['Sca'])
                && isset($re->GetErrorDetails()->Data['RedirectUrl'])
            ) {
                $this->logger->debug('Sca required to get wallet');
                return new JsonResponse([
                    'outcome' => 'fail',
                    'data' => [
                        'user_message' => 'SCA required to view wallet',
                        'developer_message' => $re->GetErrorDetails()->Errors['Sca'],
                        'wallet_id' => $user->getMangoPayWalletId(),
                        'redirect_url' => $re->GetErrorDetails()->Data['RedirectUrl'],
                    ],
                    'status' => Response::HTTP_UNAUTHORIZED,
                ], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'user_message' => 'Failed to get Mangopay wallet for user',
                    'developer_message' =>
                        'Failed to get Mangopay wallet for userId ['
                        . $user->getId()
                        . ']',
                ],
                'status' => 200,
            ]);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'id' => $mangopayWallet->Id,
                'tag' => $mangopayWallet->Tag,
                'creation_date' => $mangopayWallet->CreationDate,
                'description' => $mangopayWallet->Description,
                'balance' => $mangopayWallet->Balance->Amount,
                'currency' => $mangopayWallet->Balance->Currency,
            ],
            'status' => 200,
        ]);
    }

    #[Get('%api_network_path%/self/mangopay/wallet/transactions')]
    #[Rest\View]
    public function getselfMangoPayWalletTransactionsSca(
        Request $request,
        #[CurrentUser] User $user,
        #[MapQueryParameter] ?bool $sca = null,
        #[MapQueryParameter] int $page = 1,
        #[MapQueryParameter] int $per_page = 10,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] int $end = 0,
    ): ErrorResponse|JsonResponse {
        $this->logger->info(
            "List wallet transactions for user #{$user->getId()}.",
            $request->query->all(),
        );

        if (!$user->isAMangoPayUser()) {
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID);
        }
        if (!$user->getMangoPayWalletId()) {
            return new ErrorResponse(ErrorResponse::ERROR_WALLET_USER_MISSING_WALLET);
        }
        // null for ScaContext means Mangopay will use whatever is their default
        if ($sca === null) {
            $scaContext = $sca;
        } else {
            $scaContext = $sca ? 'USER_PRESENT' : 'USER_NOT_PRESENT';
        }
        try {
            $transactionArray = $this->mangopayService->getUserMangoPayWalletTransactions(
                user: $user,
                page: $page,
                pageSize: $per_page,
                startDate: $start,
                endDate: $end,
                scaContext: $scaContext,
            );
        } catch (\MangoPay\Libraries\ResponseException $re) {
            $this->logger->error('Error getting user mangopay wallet', [
                $re->GetErrorDetails()->Type,
                $re->GetErrorDetails()->Message,
            ]);
            // Specifically handle SCA authentication error
            // See official SDK tests for further info on how SCA works for wallets
            // as it's a bit different to SCA on users/transfers
            // https://github.com/Mangopay/mangopay2-php-sdk/blob/master/tests/Cases/WalletsTest.php
            if (
                401 === $re->getCode()
                && isset($re->GetErrorDetails()->Errors['Sca'])
                && isset($re->GetErrorDetails()->Data['RedirectUrl'])
            ) {
                $this->logger->debug('Sca required to get wallet transactions');
                return new JsonResponse([
                    'outcome' => 'fail',
                    'data' => [
                        'user_message' => 'SCA required to view wallet transactions',
                        'developer_message' => $re->GetErrorDetails()->Errors['Sca'],
                        'wallet_id' => $user->getMangoPayWalletId(),
                        'redirect_url' => $re->GetErrorDetails()->Data['RedirectUrl'],
                    ],
                    'status' => Response::HTTP_UNAUTHORIZED,
                ], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'user_message' => 'Failed to get Mangopay wallet for user',
                    'developer_message' =>
                        'Failed to get Mangopay wallet for userId ['
                        . $user->getId()
                        . ']',
                ],
                'status' => 200,
            ]);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'transactions' => $transactionArray,
            ],
            'status' => 200,
        ]);
    }

    #[Get('%api_network_path%/self/mangopay/payin/{payinId}')]
    #[Rest\View]
    public function getSelfMangoPayPayin(
        #[CurrentUser] User $user,
        string $payinId,
    ): ErrorResponse|JsonResponse {
        $this->logger->info(
            "Getting payin with id #{$payinId} for user #{$user->getId()}",
        );

        try {
            $payin = $this->mangopayService->getPayIn($payinId);
        } catch (\MangoPay\Libraries\ResponseException $re) {
            $this->logger->error('Error getting payin', [
                $re->GetErrorDetails()->Type,
                $re->GetErrorDetails()->Message,
            ]);
            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'user_message' => 'Failed retrieving payin',
                    'developer_message' => "Failed to get payin with id [{$payinId}]",
                ],
                'status' => 400,
            ], Response::HTTP_BAD_REQUEST);
        }
        $this->logger->debug('Wallet Ids', [
            'creditedWallet' => $payin->CreditedWalletId,
            'userWallet' => $user->getMangoPayWalletId(),
        ]);
        if ($payin->CreditedWalletId == $user->getMangoPayWalletId()) {
            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'id' => $payin->Id,
                    'tag' => $payin->Tag,
                    'payment_type' => $payin->PaymentType,
                    'execution_type' => $payin->ExecutionType,
                    'creation_date' => $payin->CreationDate,
                    'execution_date' => $payin->ExecutionDate,
                    'status' => $payin->Status,
                    'result_code' => $payin->ResultCode,
                    'result_message' => $payin->ResultMessage,
                    'amount' => $payin->CreditedFunds->Amount,
                    'currency' => $payin->CreditedFunds->Currency,
                ],
                'status' => 200,
            ]);
        }
        return new JsonResponse([
            'outcome' => 'fail',
            'data' => [
                'user_message' => 'Failed retrieving payin',
                'developer_message' => "Failed to get payin with id [{$payinId}]",
            ],
            'status' => 404,
        ], Response::HTTP_NOT_FOUND);
    }
}
