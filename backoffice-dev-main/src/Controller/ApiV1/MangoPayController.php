<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 17/09/18
 * Time: 19:02
 */

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Entity\User;
use App\Exception\ApiException;
use App\Service\Manager\TransactionManager;
use App\Service\MangoPay;
use App\Service\Util\Helper;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Patch as Patch;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use MangoPay\BankAccount;
use MangoPay\KycDocument;
use MangoPay\PayIn;
use MangoPay\PayOut;
use MangoPay\Transfer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

class MangoPayController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private MangoPay $mangopayService,
    ) {}

    /**
     * @return JsonResponse
     */
    #[Post('%api_network_path%/self/mangopayWallets')]
    #[Rest\View]
    public function postUserMangoPayWalletAction()
    {
        $this->logger->info('Create Users MangoPay Wallet');

        $em = $this->doctrine->getManager();

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        //@TODO check lifecycle status

        //Does this user have a  mangopayId ?
        if (!$currentUser->isAMangoPayUser()) {
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID);
        }

        /** @var \MangoPay\Wallet */
        $mangoPayUserWallet = $this->mangopayService->createUserWallet($currentUser);

        $this->logger->info('MangoPay Wallet Created');

        $currentUser->setMangoPayWalletId($mangoPayUserWallet->Id); // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
        $em->flush();

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'mangopay_wallet_id' => $mangoPayUserWallet->Id, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param User $userId
     * @return JsonResponse
     */
    #[Get('%api_network_path%/users/{userId}/mangopayWallets')]
    #[Rest\View]
    public function getUsersMangoPayWallets($userId)
    {
        $this->logger->info($userId);
        $em = $this->doctrine->getManager();

        /** @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        //Does this user have a  mangopayId ?
        if (!$currentUser->isAMangoPayUser()) {
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID);
        }

        try {
            // If user has a Mangopay wallet id, just return that
            if ($currentUser->getMangoPayWalletId()) {
                // Keep result in an array for backwards compatibility
                $mangoPayUserWallet = [
                    $this->mangopayService->getSingleWallet(walletId: $currentUser->getMangoPayWalletId()),
                ];
            } else {
                //Returns an array of mangoPay Wallets
                /** @var \MangoPay\Wallet[] */
                $mangoPayUserWallet =
                    $this->mangopayService->getUserWallets($currentUser);
            }
        } catch (\MangoPay\Libraries\ResponseException $re) {
            $this->logger->error('Error getting user mangopay wallets list', [
                $re->GetErrorDetails()->Type,
                $re->GetErrorDetails()->Message,
            ]);

            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'user_message' => 'The resource does not exist',
                    'developer_message' =>
                        'The resource does not exist for userId [' . $userId . ']',
                ],
                'status' => 200,
            ]);
        }

        //We should only get 1 mangopay wallet for the user @TODO need to confirm this
        /*if (count($mangoPayUserWallet)> 1) {
         * return new JsonResponse([
         * 'outcome'   => 'fail',
         * 'data'      => [
         * 'user_message'     => 'You seem to have more than one mangopay wallet, this is not allowed.',
         * 'developer_message'=> 'User ['. $userId . '] has more than one mangopay wallet'
         * ],
         * 'status'    => 200,
         * ]);
         * }*/

        //Create an array of the data we are interested in from the response
        $returnArray = [];
        foreach ($mangoPayUserWallet as &$wallet) {
            $data = [
                'id' => $wallet->Id, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
                'tag' => $wallet->Tag,
                'creation_date' => $wallet->CreationDate, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
                'description' => $wallet->Description,
                'balance' => $wallet->Balance->Amount, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
                'currency' => $wallet->Balance->Currency,
            ];
            array_push($returnArray, $data);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'wallets' => $returnArray,
            ],
            'status' => 200,
        ]);
    }

    #[Post('%api_network_path%/users/{userId}/mangopayRegisterSca')]
    #[Rest\View]
    public function postUserMangoPayRegisterSca(string $userId): ErrorResponse|JsonResponse
    {
        $this->logger->info('Register User on MangoPay with Sca:' . $userId);

        $em = $this->doctrine->getManager();

        /** @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            /** @var \MangoPay\UserNaturalSca */
            $mangoPayUser = $this->mangopayService->createNaturalUser($currentUser);
        } catch (\MangoPay\Libraries\ResponseException $e) {
            // handle MP Response Exception - get error details
            $this->logger->error($e->getMessage());
            $this->logger->debug('Mangopay errors: ' . $e->GetErrorDetails());
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_CREATE_FAILED);
        } catch (\Exception $e) {
            // handle any other type of exception
            $this->logger->error($e->getMessage());
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_USER_CREATE_FAILED);
        }

        $this->logger->info('MangoPay request was successful, created MangoPay user with id ['
        . $mangoPayUser->Id
        . ']');

        $currentUser->setMangoPayUserId($mangoPayUser->Id); // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
        $em->flush();

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'mangopay_id' => $mangoPayUser->Id, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
                'kyclevel' => $mangoPayUser->KYCLevel,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param User $userId
     * @return JsonResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayWalletRegister')]
    #[Rest\View]
    public function postUserMangoPayWalletRegister($userId)
    {
        $em = $this->doctrine->getManager();

        /** @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        //@TODO check lifecycle status
        //@TODO Do we need to check if this user already has a mangopay id???

        try {
            /** @var \MangoPay\Wallet */
            $mangoPayWallet = $this->mangopayService->createUserWallet($currentUser);
        } catch (\Exception $e) {
            $this->logger->error(
                "Failed to create Mangopay wallet for user#{$userId}",
                [$e->getMessage()],
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_CREATE_USER_WALLET_FAILED,
                $e->getMessage(),
            );
        }

        $currentUser->setMangoPayWalletId($mangoPayWallet->Id); // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
        $em->flush();

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'mangopay_wallet_id' => $mangoPayWallet->Id, // @codingStandardsIgnoreLine Bad MangoPay CamelCaps
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param User $userId
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayTransfer')]
    #[Rest\View]
    public function postUserMangoPayTransfer(
        Request $request,
        TransactionManager $transactionManager,
        int $userId,
    ) {
        $this->logger->info($request->getContent());
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        //validate the request
        if (empty($data->amount)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay
        //$clean_data =[];
        //$clean_data['amount']=$data->amount;
        //$clean_data['currency']='GBP';
        //$clean_data['fee_amount']=2.5; // $data->fee_amount;
        //$clean_data['user_wallet_id']=$data->user_wallet_id;
        //$clean_data['org_wallet_id']=$data->org_wallet_id;

        try {
            /** @var Transfer $mangopayTransfer */
            $mangopayTransfer = $this->mangopayService->createTransfer(
                $currentUser,
                $data,
            );

            $this->logger->info('Creating a transaction for transfer');
            //create a transaction // do this in the transaction manager
            $internal_trans_id = $transactionManager->createTransaction(
                $data,
                $mangopayTransfer,
                TRANS_TYPE_CONSTANT::TRANS_NP,
            );

            //$this->logger->info(new JsonResponse($mangopayTransfer));
        } catch (\Exception $e) {
            //$this->logger->error("Error occured in Mangopay postUserMangoPayTransfer " . $e->getMessage());
            if ($e->getCode() == 916) {
                $this->logger->error(
                    'Error occured in Mangopay postUserMangoPayTransfer '
                        . $e->getMessage(),
                );
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET,
                    $e,
                );
            } else {
                $this->logger->error(
                    'Error occured in Mangopay postUserMangoPayTransfer '
                        . $e->getMessage(),
                );
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_TRANSFER_FAILED,
                    $e,
                );
            }
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'transfer' => [
                    'id' => $mangopayTransfer->Id,
                    'creation_date' => $mangopayTransfer->CreationDate,
                    'author_id' => $mangopayTransfer->AuthorId,
                    'credited_user_id' => $mangopayTransfer->CreditedUserId,
                    'debited_funds' => $mangopayTransfer->DebitedFunds->Amount,
                    'credited_funds' => $mangopayTransfer->CreditedFunds->Amount,
                    'status' => $mangopayTransfer->Status,
                    'type' => $mangopayTransfer->Type,
                    'execution_date' => $mangopayTransfer->ExecutionDate,
                    'nature' => $mangopayTransfer->Nature,
                    'result_message' => $mangopayTransfer->ResultMessage,
                    'pending_user_action' => $mangopayTransfer->PendingUserAction,
                ],
                'internal_transaction_id' => $internal_trans_id,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param User $userId
     * @param $walletId - not used see NOTE below
     * @return JsonResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayWalletPayinBankWire/{walletId}')]
    #[Rest\View]
    public function postUserMangoPayWalletPayinBankWire(
        Request $request,
        $userId,
        $walletId,
    ) {
        //NOTE - Although this request has walletId it is not needed as the userid would hold the walletId
        //walletId has been added to meet the route requirement from the 1020 code.

        $this->logger->debug(
            'postUserMangoPayWalletPayinBankWire parameters = ['
            . $request->getContent()
            . '].',
        );
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        $em = $this->doctrine->getManager();

        /** @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            /** @var PayIn $mangopayPayIn */
            $mangopayPayIn = $this->mangopayService->createMangopayWalletPayinBankWire(
                $currentUser,
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay createMangopayWalletPayinBankWire '
                    . $e->getMessage(),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_BANKWIRE_PAYIN_CREATE_FAILED,
                $e,
            );
        }

        //We don't save these details, they are just presented to the web front end

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'bank_account' => [
                    'type' => $mangopayPayIn->Type,
                    'owner_name' =>
                        $mangopayPayIn->PaymentDetails->BankAccount->OwnerName,
                    'IBAN' => $mangopayPayIn->PaymentDetails->BankAccount->Details->IBAN,
                    'BIC' => $mangopayPayIn->PaymentDetails->BankAccount->Details->BIC,
                    'wire_reference' => $mangopayPayIn->PaymentDetails->WireReference,
                ],
            ],
            'status' => 200,
        ]);
    }

    /**
     * Post mango pay pay in via web (does not store card details).  Shows a mangopay form to enter card details via callback
     * @param Request $request
     * @param User $userId
     * @return JsonResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayWalletPayin/{walletId}')]
    #[Rest\View]
    public function postUserMangopayWalletPayin(Request $request, $userId)
    {
        $this->logger->info('API REQUEST DATA=' . $request->getContent());
        $em = $this->doctrine->getManager();

        //The request body contains the data we need
        $data = json_decode($request->getContent(), false);

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            /** @var PayIn $mangopayPayIn */
            $mangopayPayIn = $this->mangopayService->cardWebPayIn($currentUser, $data);
        } catch (ApiException $api) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangopayWalletPayin '
                    . json_encode($api),
            );
            if ($api->getCode() === 600) {
                return new ErrorResponse(ErrorResponse::ERROR_TRANSACTION_AMOUNT_HIGHER_THAN_PERMITTED_AMOUNT);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_CARD_PAYIN_FAILED);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangopayWalletPayin '
                    . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_CARD_PAYIN_FAILED);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'RedirectURL' => $mangopayPayIn->ExecutionDetails->RedirectURL,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param User $userId
     * @param $type
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/users/{userId}/bankaccounts/{type}')]
    #[Rest\View]
    public function postUserMangoPayCreateBankAccount(Request $request, $userId, $type)
    {
        $this->logger->info(
            'postUserMangoPayCreateBankAccount API Request=' . $request->getContent(),
        );
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay

        try {
            /** @var BankAccount $mangopayBankAccount */
            $mangopayBankAccount = $this->mangopayService->createMangopayUserBankAccount(
                $currentUser,
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangoPayCreateBankAccount '
                    . $e->getMessage(),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_BANK_ACCOUNT_FAILED,
                $e,
            );
        }

        //We don't save these details, they are just presented to the web front end

        if ($data->type === 'IBAN') {
            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'bank_account' => [
                        'id' => $mangopayBankAccount->Id,
                        'type' => $mangopayBankAccount->Type,
                        'owner_name' => $mangopayBankAccount->OwnerName,
                        'IBAN' => $mangopayBankAccount->Details->IBAN,
                        'BIC' => $mangopayBankAccount->Details->BIC,
                        'created_at' => $mangopayBankAccount->CreationDate,
                    ],
                ],
                'status' => 200,
            ]);
        } elseif ($data->type === 'GB') {
            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'bank_account' => [
                        'id' => $mangopayBankAccount->Id,
                        'type' => $mangopayBankAccount->Type,
                        'owner_name' => $mangopayBankAccount->OwnerName,
                        'account_number' =>
                            $mangopayBankAccount->Details->AccountNumber,
                        'sort_code' => $mangopayBankAccount->Details->SortCode,
                        'created_at' => $mangopayBankAccount->CreationDate,
                    ],
                ],
                'status' => 200,
            ]);
        } else {
            throw new \Exception('Unknown Bank account type [' . $data->type . ']');
        }
    }

    /**
     * @param Request $request
     * @param $userId
     * @param $walletId
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayWalletPayoutBankWire/{walletId}')]
    #[Rest\View]
    public function postUserMangoPayWalletPayoutBankWire(
        Request $request,
        $userId,
        $walletId,
    ) {
        $this->logger->info(
            'postUserMangoPayWalletPayoutBankWire REQUEST DATA=['
            . $request->getContent()
            . ']',
        );

        //The request body contains the data we need
        $data = json_decode($request->getContent());

        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay

        try {
            /** @var PayOut $mangopayBankAccount */
            $mangopayBankAccount = $this->mangopayService->createMangopayWalletPayoutBankWire(
                $currentUser,
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangoPayWalletPayoutBankWire '
                    . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_PAYOUT_GENERIC);
        }

        //We don't save these details, they are just presented to the web front end

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'bank_account' => [
                    'type' => $mangopayBankAccount->Type,
                    'created_at' => $mangopayBankAccount->CreationDate,
                    'author_id' => $mangopayBankAccount->AuthorId,
                    'amount' => $mangopayBankAccount->CreditedFunds->Amount,
                    'fees' => $mangopayBankAccount->Fees->Amount,
                    'sort_code' =>
                        $mangopayBankAccount->MeanOfPaymentDetails->BankAccountId,
                ],
            ],
            'status' => 200,
        ]);
    }

    /**
     * Get a mangopay users bank accounts
     * @param Request $request
     * @param User $userId
     * @return JsonResponse
     */
    #[Rest\View]
    #[Get('%api_network_path%/users/{userId}/bankaccounts')]
    public function getUserMangopayBankAccounts(Request $request, $userId)
    {
        $this->logger->info(
            'In getUserMangopayBankAccounts REQUEST DATA=['
            . $request->getContent()
            . ']..empty request is correct here.',
        );

        //The request body contains the data we need
        $data = json_decode($request->getContent(), false);
        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            // Ensure we have nested arrays, not an array of objects
            $bankAccountList = json_decode(
                json_encode($this->mangopayService->getUserbankaccounts($currentUser)),
                true,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay getUserMangopayBankAccounts '
                    . $e->getMessage(),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_CARD_PAYIN_FAILED,
                $e,
            );
        }

        if (empty($bankAccountList)) {
            $this->logger->info(
                'No mangopay bank accounts found for user ['
                . $currentUser->getUserIdentifier()
                . ']',
            );
        }

        $resultArray = [];
        foreach ($bankAccountList as $bankaccount) {
            $result = Helper::handleMangopayBankAccounts($bankaccount);

            array_push($resultArray, $result);
            // $this->logger->warning($bankaccount->Id);
            // $this->logger->warning($bankaccount->UserId);
            $this->logger->info(
                'Found bank account for user ['
                . $currentUser->getUserIdentifier()
                . '], Id=['
                . $bankaccount['Id']
                . '], userId=['
                . $bankaccount['UserId']
                . ']',
            );
            $this->logger->info('Bank account response' . json_encode($result));
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'bank_accounts' => $resultArray,
            ],
            'status' => 200,
        ]);
    }

    /**
     * Get a mangopay users wallet transactions
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @param User $userId
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'page', requirements: '\d+', default: 1)]
    #[Rest\QueryParam(name: 'per_page', requirements: '\d+', default: 25)]
    #[Rest\QueryParam(name: 'start', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'end', requirements: '\d+', default: 0)]
    #[Rest\View]
    #[Get('%api_network_path%/users/{userId}/mangopayWallets/{walletId}/transactions')]
    public function getUserMangopayWalletTransactions(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        $userId,
    ) {
        //The request body contains the data we need
        $data = json_decode($request->getContent(), false);
        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        // $this->logger->info("Query params: " . json_encode($paramFetcher->all()));

        $page = $paramFetcher->get('page');
        $pageSize = $paramFetcher->get('per_page');
        $startDate = $paramFetcher->get('start');
        $endDate = $paramFetcher->get('end');

        $this->logger->info(
            'Query params accepted: '
            . $page
            . ' - '
            . $pageSize
            . ' - '
            . $startDate
            . ' - '
            . $endDate,
        );

        try {
            //This will return an array of transactions
            $transactionArray = $this->mangopayService->getUserMangoPayWalletTransactions(
                $currentUser,
                $page,
                $pageSize,
                $startDate,
                $endDate,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay getUserMangopayWalletTransactions '
                    . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_UNKNOWN, $e);
        }

        $this->logger->warning(json_encode($transactionArray));

        // $resultArray = [];
        // foreach ($transactionArray as $transaction) {
        //     $result = new \stdClass();

        //     $result->Id = $transaction->Id;
        //     $result->creation_date = $transaction->CreationDate;
        //     $result->author_id = $transaction->AuthorId;
        //     $result->credited_user_id = $transaction->CreditedUserId;
        //     $result->debited_funds = $transaction->DebitedFunds->Amount;
        //     $result->credited_funds = $transaction->CreditedFunds->Amount;
        //     $result->status = $transaction->Status;
        //     $result->type = $transaction->Type;
        //     $result->nature = $transaction->Nature;

        //     array_push($resultArray, $result);
        // }

        //Return the Mangopay transaction object as is instead of remapping and limited the fields

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'transactions' => $transactionArray,
            ],
            'status' => 200,
        ]);
    }

    /**
     * Get a mangopay users wallet last transaction
     * @param Request $request
     * @param User $userId
     * @return JsonResponse
     */
    #[Rest\View]
    #[Get(
        '%api_network_path%/users/{userId}/mangopayWallets/{walletId}/lasttransaction',
    )]
    public function getUserMangopayWalletLastTransaction(Request $request, $userId)
    {
        //The request body contains the data we need
        $data = json_decode($request->getContent(), false);
        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            //This will return an array of transactions
            $transactionArray =
                $this->mangopayService->getUserMangoPayWalletLastTransaction(
                    $currentUser,
                );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay getUserMangoPayWalletLastTransaction '
                    . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_UNKNOWN, $e);
        }

        $this->logger->warning(json_encode($transactionArray));

        // $resultArray = [];
        // foreach ($transactionArray as $transaction) {
        //     $result = new \stdClass();

        //     $result->Id = $transaction->Id;
        //     $result->creation_date = $transaction->CreationDate;
        //     $result->author_id = $transaction->AuthorId;
        //     $result->credited_user_id = $transaction->CreditedUserId;
        //     $result->debited_funds = $transaction->DebitedFunds->Amount;
        //     $result->credited_funds = $transaction->CreditedFunds->Amount;
        //     $result->status = $transaction->Status;
        //     $result->type = $transaction->Type;
        //     $result->nature = $transaction->Nature;
        //     $result->ResultMessage = $transaction->ResultMessage;

        //     array_push($resultArray, $result);
        // }

        //Return the Mangopay transaction object as is instead of remapping and limited the fields

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'transactions' => $transactionArray,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param User $userId
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/users/{userId}/mangopayKycCheck')]
    #[Rest\View]
    public function postMangoPayKycCheck(Request $request, $userId)
    {
        $this->logger->info($request->getContent());
        $em = $this->doctrine->getManager();

        /* @var User $currentUser */
        $currentUser = $em->getRepository(User::class)->find($userId);

        try {
            /** @var KycDocument $kycDocument */
            $kycDocument = $this->mangopayService->createKYCDocument($currentUser);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayKycCheck ' . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_KYC_CHECK_FAILED);
        }

        // Note data is empty here on purpose
        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'kyc_id' => $kycDocument->Id,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param $cardId
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/cards/{cardId}')]
    #[Rest\View]
    public function postMangoDeactivateCard(Request $request, $cardId)
    {
        $this->logger->info($request->getContent());
        //The request body contains the data we need
        $data = json_decode($request->getContent(), false);

        try {
            $reponse = $this->mangopayService->createKYCDocument($cardId);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayKycCheck ' . $e->getMessage(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_KYC_CHECK_FAILED);
        }

        // Note data is empty here on purpose
        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                // 'Message' => $reponse->Message
            ],
            'status' => 200,
        ]);
    }
}
