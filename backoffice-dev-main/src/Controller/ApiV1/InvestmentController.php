<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Dto\Payment\LinkedPaymentRequestDto;
use App\Dto\Sca\ScaOutcomeRequestDto;
use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\InvestmentAddFields;
use App\Entity\InvestmentDocuments;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Payout;
use App\Entity\PayoutAddFields;
use App\Entity\Transaction;
use App\Repository\InvestmentRepository;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\InvestmentDocumentManager;
use App\Service\Manager\InvestmentManager;
use App\Service\Manager\InvestmentManagerV2;
use App\Service\Manager\TransactionManager;
use App\Service\MangoPay;
use App\Service\MangopayScaService;
use App\Service\Util\Helper;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Delete as Delete;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

class InvestmentController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private InvestmentManager $investmentManager,
        private InvestmentRepository $investmentRepository,
    ) {}

    /**
     * @return JsonResponse
     */
    #[Get('/%api_network_path%/investments', name: 'api_get_investments')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(
        name: 'sort',
        requirements: '^([+-]?[a-zA-Z]+,?)*$',
        nullable: true,
        description: 'Comma separate list of sort criteria',
    )]
    #[Rest\QueryParam(
        name: 'id',
        requirements: '^\d+(,\d+)*$',
        nullable: true,
        description: 'Comma separate list of ids',
    )]
    #[Rest\QueryParam(name: 'status', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\View]
    public function getInvestments(ParamFetcherInterface $paramFetcher)
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Resource not found!');
        }

        $queryParams = $paramFetcher->all(true);
        $this->logger->info(
            'GET /investments with params ' . json_encode($queryParams),
        );

        $totalCount = $this->investmentRepository->count([]);
        $resultValues = $this->investmentManager->findByQuery(
            $queryParams,
            $this->isGranted('ROLE_ADMIN'),
        );

        if (empty($resultValues)) {
            throw $this->createNotFoundException('Resource not found!');
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'offset' => $queryParams['offset'],
                'limit' => $queryParams['limit'],
                'count' => $totalCount,
                'list' => $resultValues,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Investment $investment_id
     * @return JsonResponse
     */
    #[Get('/%api_network_path%/investments/{investment_id}')]
    #[Rest\View]
    public function getInvestment($investment_id)
    {
        /** @var Investment $result_inv */
        $result_inv = $this->checkInvestmentExists($investment_id);
        //check investment exisits
        if ($result_inv === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }

        $userId = $this->getUser()->getId();
        if (!$this->isGranted('ROLE_ADMIN')) {
            $singleResultValue = $this->investmentManager->findInvestmentById(
                $investment_id,
                $userId,
            );
        } else {
            $singleResultValue = $this->investmentManager->findOneById($investment_id);
        }

        if (!$singleResultValue || !$singleResultValue instanceof Investment) {
            throw $this->createNotFoundException('Resource not found!');
        }
        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'investment' => $singleResultValue,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 2)]
    #[Rest\View]
    #[Get(
        '/%api_network_path%/investments/{investment_id}/payouts',
        name: 'api_get_investment_payouts',
    )]
    public function getInvestmentPayouts(
        ParamFetcherInterface $paramFetcher,
        $investment_id,
    ) {
        $filterParam['offset'] = $paramFetcher->get('offset', 0);
        $filterParam['limit'] = $paramFetcher->get('limit', 10);

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS);
        }

        /** @var Investment $result_inv */
        $result_inv = $this->checkInvestmentExists($investment_id);
        if ($result_inv === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }

        $result_inv = $this->investmentManager->findOneById($investment_id);
        $payouts = $result_inv->getPayouts();

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'offset' => $filterParam['offset'],
                'limit' => $filterParam['limit'],
                'count' => count($payouts),
                'list' => $payouts->getValues(),
            ],
            'status' => 200,
        ]);
    }

    /**
     * Create a new Payout for an Investment
     *
     * @param Request $request
     * @param Investment $investment_id
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/investments/{investment_id}/payouts',
        name: 'api_post_investment_payout',
    )]
    #[Rest\View]
    public function addPayoutInvestment(Request $request, $investment_id)
    {
        $this->logger->info($request->getContent());
        try {
            //The request body contains the data we need
            $data = json_decode($request->getContent());

            // @var Offering $resultInvestment
            $investment =
                $this->investmentManager->checkInvestmentExists($investment_id);

            if (empty($this->getUser()->getInvestor())) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_IS_NOT_AN_INVESTOR);
            }
            if ($investment === false) {
                return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
            }
            if (empty($data->currency)) {
                return new ErrorResponse(ErrorResponse::ERROR_PAYOUT_CURRENCY_NOT_VALID);
            }
            if (empty($data->due_date)) {
                return new ErrorResponse(ErrorResponse::ERROR_PAYOUT_DUEDATE_NOT_VALID);
            }
            if (empty($data->payout_amount)) {
                return new ErrorResponse(ErrorResponse::ERROR_PAYOUT_AMMOUNT_NOT_VALID);
            }
            if (!isset($data->payout_type)) {
                return new ErrorResponse(ErrorResponse::ERROR_PAYOUT_TYPE_NOT_VALID);
            }

            if (isset($data->transferId)) {
                $data->transactionId = $data->transferId;
            }

            //create a payout schedule for the investment
            $payoutId = $this->createPayOut($data, $investment);
            if (!empty($payoutId)) {
                //means the investment passed validation and was submitted
                return new JsonResponse([
                    'outcome' => 'success',
                    'data' => [
                        'payout_id' => $payoutId,
                    ],
                    'status' => 200,
                ]);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
            }
        } catch (\Exception $ex) {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    //Create and return a payout
    protected function createPayOut($param, $investment)
    {
        try {
            $payout = new Payout();
            $payout->setPayoutAmount($param->payout_amount);
            $payout->setCurrency($param->currency);
            $payout->setPayoutType($param->payout_type);
            $param->due_date = new \DateTime($param->due_date);
            $payout->setDueDate($param->due_date);
            $payout->setInvestment($investment);

            $payout->setCreatedById($this->getUser()->getId());

            if (!empty($param->additional_type)) {
                $payout->setAdditionalType($param->additional_type);
            }

            if (!empty($param->custom->fee)) {
                $payout->setFee($param->custom->fee);
            }
            // Add field object creation
            if (!empty($param->add_field)) {
                foreach ($param->add_field as $singleParam) {
                    $singleField = $this->createNewFieldForPayout($singleParam);
                    $payout->addAddField($singleField);
                }
            }

            if (!empty($param->transactionId)) {
                $payout->setTransactionId($param->transactionId);
            }

            $em = $this->doctrine->getManager();
            /** @var \App\Repository\PayoutRepository */
            $repository = $em->getRepository(Payout::class);
            $repository->save($payout);
            $em->flush();

            $payoutId = $payout->getId();
            if (!empty($payoutId)) {
                return $payoutId;
            }
            return false;
        } catch (\Exception $ex) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                $ex->getMessage(),
            );
        }
    }

    /**
     * @param Request $request
     * @param $investmentId
     * @post("/%api_network_path%/investments/{investmentId}/documents", name="api_investment_postdocument")
     * @return JsonResponse
     */
    #[Rest\View]
    public function postInvestmentDocument(
        Request $request,
        DocumentManager $documentManager,
        int $investmentId,
    ) {
        $this->logger->info(Helper::cleanDocumentLogger($request->getContent()));
        /* @var Investment $result_inv */
        $result_inv = $this->checkInvestmentExists($investmentId);

        //check investment exists
        if ($result_inv === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }

        $singleInvestment = $this->investmentManager->findOneById($investmentId);

        //Checking Contact point or admin
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->getUser()->getId() == $singleInvestment->getAsset()->getcontactPoint()->getId()
        ) {
            //Getting post content
            $postRequest = $request->getContent();
            $paramArr = json_decode($postRequest);

            if (empty($paramArr)) {
                return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
            }

            if (empty($paramArr->file_name)) {
                return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_MISSING_FILE_NAME);
            }

            if (empty($paramArr->file_type)) {
                return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_MISSING_FILE_TYPE);
            }

            if (empty($paramArr->document_content)) {
                return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_MISSING_CONTENT);
            }

            //Creating the document
            $documentId = $this->createNewInvestmentDocument(
                $documentManager,
                $paramArr,
                $singleInvestment,
            );
            if (intval($documentId)) {
                return new SuccessResponse([
                    'document_id' => $documentId,
                ]);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
            }
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION);
        }
    }

    /**
     * for creating the document object and update the asset
     * @param $param
     * @param Investment $investments
     * @return bool|int
     */
    protected function createNewInvestmentDocument(
        DocumentManager $documentManager,
        $param,
        $investments,
    ) {
        try {
            /** @var Document $documentObj */
            $documentObj = $documentManager->buildDocument(
                $param,
                'private',
                'investment/' . $investments->getId(),
            );
            $investmentDocument = new InvestmentDocuments();
            $investmentDocument->setDocument($documentObj);
            $investments->addDocument($investmentDocument);

            $em = $this->doctrine->getManager();
            /** @var \App\Repository\InvestmentRepository */
            $repository = $em->getRepository(Investment::class);
            $repository->save($investments);
            $em->flush();

            $documentId = $documentObj->getId();

            return $documentId;
        } catch (\Exception $ex) {
            return false;
        }
    }

    #[Patch(
        '/%api_network_path%/investments/{investmentId}',
        name: 'api_patch_investments',
    )]
    #[Rest\View]
    public function patchInvestmentsAction(Request $request, $investmentId)
    {
        $this->logger->info(
            'investment id:' . $investmentId . ':' . $request->getContent(),
        );

        /* @var Investment $result_inv */
        $result_inv = $this->checkInvestmentExists($investmentId);
        if ($result_inv === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }
        $inv_status = $result_inv->getLifecycleStatus();
        // $this->logger->warning($inv_status);

        //Checking user or admin
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->getUser()->getId() == $result_inv->getUser()->getId()
        ) {
            //Getting patch content
            $data = json_decode($request->getContent());
            if (!empty($data)) {
                /** @var Investment $investment */
                $investment = $this->investmentManager->buildInvestment(
                    $data,
                    $result_inv,
                );
                $this->logger->debug($inv_status);

                $em = $this->doctrine->getManager();
                /** @var InvestmentRepository $inv_repo */
                $inv_repo = $em->getRepository(Investment::class);

                //special case if settling the investment
                // settleInvestment has been removed as of 2024 February Release
                // Although historically, it didn't really do anything as it was marked "TODO"
                // $this->investmentManager->settleInvestment($inv_status, $investment);

                $inv_repo->save($investment, true);
                return new SuccessResponse([
                    'investment_id' => $investment->getId(),
                ]);
            } else {
                return new ErrorResponse(
                    ErrorResponse::ERROR_INSUFFICIENT_PARAMS,
                    'tried to updated' . $investmentId,
                );
            }
        } else {
            return new ErrorResponse(
                ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                'patchInvestmentsAction id:' . $result_inv->getId(),
            );
        }
    }

    //Create add fields for PayoutAddFields
    protected function createNewFieldForPayout($param)
    {
        try {
            $addFields = new PayoutAddFields();

            if (!empty($param->field_key)) {
                $addFields->setFieldKey($param->field_key);
            }
            if (!empty($param->value)) {
                $addFields->setFieldValue($param->value);
            }
            return $addFields;
        } catch (\Doctrine\ORM\EntityNotFoundException $ex) {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    protected function createNewField($param)
    {
        try {
            $addFields = new InvestmentAddFields();

            if (!empty($param['field_key'])) {
                $addFields->setFieldKey($param['field_key']);
            }
            if (!empty($param['value'])) {
                $addFields->setFieldValue($param['value']);
            }
            return $addFields;
        } catch (\Exception $ex) {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    /**
     * Checks that an investments exists
     * @param $investment_id
     * @return mixed
     */
    private function checkInvestmentExists($investment_id)
    {
        /** @var Investment $resultInv */
        $resultInv = $this->investmentManager->findOneById($investment_id);

        //check we have an offering
        if (is_null($resultInv)) {
            return false;
        } else {
            return $resultInv;
        }
    }

    /**
     * Create a new Asset
     * @param Request $request
     * @return JsonResponse
     */
    #[Delete('/%api_network_path%/investments/{investment_id}')]
    #[Rest\View]
    public function deleteInvestment($investment_id)
    {
        /* @var Investment $investment */
        $investment = $this->checkInvestmentExists($investment_id);

        if ($investment === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }

        $investmentLifecycleStatus = $investment->getLifecycleStatus();

        //Checking life cycle status is empty
        if (empty($investmentLifecycleStatus)) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_STATE_IS_MISSING);
        }

        //Checking life cycle status is ssetteld
        if ($investmentLifecycleStatus == InvestmentLifecycle::STATE_SETTLED) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_IN_SETTLED_STATE);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $this->investmentManager->withdrawInvestment($investment);
        } elseif ($this->getUser()->getId() == $investment->getUser()->getId()) {
            $this->investmentManager->withdrawInvestment($investment);
        }

        //Checking latest State of investment after applying lifecycle
        $investment = $this->checkInvestmentExists($investment_id);

        if ($investment->getLifecycleStatus() == InvestmentLifecycle::STATE_WITHDRAWN) {
            return new JsonResponse([
                'outcome' => 'success',
                'data' => ['investment_id' => $investment->getId()],
                'status' => 200,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_STATE_CHANGE_NOT_POSSIBLE);
        }
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Investment $investment_id
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\View]
    #[Get(
        '/%api_network_path%/investments/{investment_id}/documents',
        name: 'api_get_investment_documents',
    )]
    public function getInvestmentDocuments(
        ParamFetcherInterface $paramFetcher,
        InvestmentDocumentManager $investmentDocumentManager,
        $investment_id,
    ) {
        $this->logger->info('getInvestmentDocuments called with investment id = '
        . $investment_id);

        /* @var Investment $resultInvestment */
        $resultInvestment = $this->checkInvestmentExists($investment_id);

        //check investment exists
        if ($resultInvestment === false) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_NOT_FOUND);
        }

        //Getting Filter and Offset
        $filterParam['offset'] = $paramFetcher->get('offset', 0);
        $filterParam['limit'] = $paramFetcher->get('limit', 10);

        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->getUser()->getId() === $resultInvestment->getUser()->getId()
        ) {
            $resultValues = $investmentDocumentManager->findDocumentsForInvestment(
                $filterParam['offset'],
                $filterParam['limit'],
                $investment_id,
            );
            $totalCount = count($resultValues);
        }

        if ($resultValues == null) {
            $this->logger->debug('No investment documents found for investmentId ['
            . $investment_id
            . '].  ');
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_DOCUMENT_NOT_FOUND);
        }
        $this->logger->info('Returning investment document for investment id = '
        . $investment_id);
        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'offset' => $filterParam['offset'],
                'limit' => $filterParam['limit'],
                'count' => $totalCount,
                'list' => $resultValues,
            ],
            'status' => 200,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Post(
        '/%api_network_path%/investments/{investmentId}/payments',
        name: 'api_create_investment_payment',
    )]
    public function createInvestmentPayment(
        #[MapEntity(id: 'investmentId')] Investment $investment,
        #[MapRequestPayload] LinkedPaymentRequestDto $dto,
        MangoPay $mangopayService,
        TransactionManager $transactionManager,
        SerializerInterface $serializer,
    ): JsonResponse {
        $this->logger->debug("APIv1 create investment #{$investment->getId()} payment");
        // Can only edit own investments
        if (
            $this->getUser()->getUserIdentifier() != $investment->getUser()->getUserIdentifier()
        ) {
            return new ErrorResponse(
                ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                null,
                true,
            );
        }
        // Investment must be in open state (or approved state?)
        if ($investment->getLifecycleStatus() != InvestmentLifecycle::STATE_OPEN) {
            return new ErrorResponse(
                ErrorResponse::ERROR_INVESTMENT_STATE_CHANGE_NOT_POSSIBLE,
                null,
                true,
            );
        }
        try {
            $mangopayTransfer = $mangopayService->createInvestmentTransfer(
                $investment,
                $dto->amount,
                $dto->sca,
            );
            $investment->setTransactionId($mangopayTransfer->Id);
            $transaction = $transactionManager->createInvestmentTransaction(
                $investment,
                $mangopayTransfer,
            );
            $this->doctrine->getManager()->persist($transaction);
            $this->doctrine->getManager()->flush();
        } catch (\Throwable $e) {
            if ($e->getCode() == 916) {
                $this->logger->error('Error occured in Mangopay createInvestmentPayment', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET,
                    $e->getMessage(),
                    true,
                );
            } else {
                $this->logger->error('Error occured in Mangopay createInvestmentPayment', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_TRANSFER_FAILED,
                    $e->getMessage(),
                    true,
                );
            }
        }
        // $jsonContent = $serializer->serialize(
        //     $mangopayTransfer,
        //     'json',
        // );
        // return JsonResponse::fromJsonString($jsonContent);
        // Keep response structure from src/Controller/ApiV1/MangoPayController.php:postUserMangoPayTransfer()
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
                'transaction_id' => $transaction?->getId(),
                'investment_id' => $investment?->getId(),
            ],
            'status' => 200,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Post(
        '/%api_network_path%/investments/{investmentId}/payment-outcome',
        name: 'api_create_investment_payment_outcome',
    )]
    public function submitInvestmentPaymentOutcome(
        #[MapEntity(id: 'investmentId')] Investment $investment,
        #[MapRequestPayload] ScaOutcomeRequestDto $dto,
        InvestmentManagerV2 $investmentManagerV2,
        MangopayScaService $mangopayScaService,
    ): JsonResponse {
        $this->logger->debug(
            "APIv1 submit investment #{$investment->getId()} payment outcome",
            [
                $dto->success,
                $dto->type,
            ],
        );
        // Can only edit own investments
        if (
            $this->getUser()->getUserIdentifier() != $investment->getUser()->getUserIdentifier()
        ) {
            return new ErrorResponse(
                ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                null,
                true,
            );
        }

        $success = $dto->success;
        if ($dto->verify) {
            $success = $mangopayScaService->isTransferSucceeded($investment->getTransactionId());
        }

        $investmentManagerV2->processPaymentOutcome($investment, $success, $dto->type);
        $this->doctrine->getManager()->flush();

        if ($success && $dto->type != 'prefunding') {
            $investmentManagerV2->sendInvestmentCreatedMail($investment);
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'investment' => $investment,
                'transfer_status' => $transfer?->Status ?? null,
                'payment_outcome' => $success,
            ],
            'status' => 200,
        ]);
    }
}
