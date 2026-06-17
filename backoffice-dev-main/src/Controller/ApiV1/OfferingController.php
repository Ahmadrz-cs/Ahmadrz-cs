<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Dto\Payment\LinkedPaymentRequestDto;
use App\Dto\Sca\ScaOutcomeRequestDto;
use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Entity\OfferingAddFields;
use App\Entity\OfferingDocuments;
use App\Repository\InvestmentRepository;
use App\Service\Manager\AssetManager;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\InvestmentManager;
use App\Service\Manager\OfferingDocumentManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\OfferingManagerV2;
use App\Service\Manager\TransactionManager;
use App\Service\MangoPay;
use App\Service\MangopayScaService;
use App\Service\Util\Helper;
use Bramus\Ansi\ControlFunctions\Base;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Patch as Patch;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OfferingController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private OfferingManager $offeringManager,
    ) {}

    /**
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(
        name: 'sort',
        requirements: '^([+-]?[a-zA-Z]+,?)*$',
        nullable: true,
    )]
    #[Rest\QueryParam(name: 'id', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'status', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'term', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\View]
    #[Get('/%api_network_path%/offerings', name: 'api_get_offerings')]
    public function getOfferings(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        DocumentManager $documentManager,
    ) {
        //example http://localhost/v1/yielders/offerings?filter%5Blife_cycle_stage%5D%5Boperator%5D=in&filter%5Blife_cycle_stage%5D%5Bvalue%5D=4"

        $queryParams = $paramFetcher->all(true);
        $this->logger->info('GET /offerings with params ' . json_encode($queryParams));

        $resultValues = $this->offeringManager->findByQuery(
            $queryParams,
            $this->isGranted('ROLE_ADMIN'),
        );
        $this->logger->info('Return count: ' . count($resultValues));

        $totalCount = count($resultValues);
        $resultValues = $documentManager->generatePublicCdnUrls($resultValues);

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
     * @param ParamFetcherInterface $paramFetcher
     * @param Offering $offering_id
     * @param Request $request
     * @return JsonResponse
     */
    #[Rest\View]
    #[Get('/%api_network_path%/offerings/{offering_id}', name: 'api_get_offering')]
    public function getOffering(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        DocumentManager $documentManager,
        int $offering_id,
    ) {
        $this->logger->info($request->getContent());

        /** @var Offering $resultOffering */
        $resultOffering = $this->offeringManager->findOneById($offering_id);
        if (!$resultOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        if ($this->isGranted('ROLE_ADMIN') === true) {
            $singleResultValue = $this->offeringManager->findOneById($offering_id);
        } else {
            $singleResultValue = $this->offeringManager->findOfferingById($offering_id);
        }
        $singleResultValue =
            $this->offeringManager->filterForUserType($singleResultValue);
        if (!$singleResultValue || !$singleResultValue instanceof Offering) {
            return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR, $offering_id);
        }
        $singleResultValue = $documentManager->generatePublicCdnUrls([
            $singleResultValue,
        ])[0];
        return new JsonResponse([
            'outcome' => 'success',
            'data' => ['offering' => $singleResultValue],
            'status' => 200,
        ]);
    }

    /**
     * Create a new Investment for an Offering
     *
     * @param Request $request
     * @param Offering $offering_id
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/offerings/{offering_id}/investments',
        name: 'api_post_offering_investment',
    )]
    #[Rest\View]
    public function addInvestmentOffering(
        Request $request,
        InvestmentManager $investmentManager,
        TransactionManager $transactionManager,
        int $offering_id,
    ) {
        $this->logger->info($request->getContent());

        $data = json_decode($request->getContent());
        if (empty($data)) {
            return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
        }

        /** @var Offering $resultOffering */
        $resultOffering = $this->offeringManager->findOneById($offering_id);
        if (!$resultOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }
        if (empty($data->investment_amount)) {
            return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_MISSING_AMOUNT);
        }

        //check if investement has exceeded investment limit set on the offering
        //issue #987
        $max_invest = $resultOffering->getMaxCommitUser();
        $this->logger->info('Investment Limit :' . $max_invest);
        if ($max_invest > 0 && isset($max_invest)) {
            if ($data->investment_amount > $resultOffering->getMaxCommitUser()) {
                return new ErrorResponse(ErrorResponse::ERROR_INVESTMENT_VALUE_LIMIT);
            }
        }
        /** @var Investment $investment */
        $investment = $investmentManager->buildInvestment($data);

        /**
         * Sanity check and resolution
         * - if the provided investmentValue != share_amount * share_price
         *   - use the share_amount * share_price value as the investmentValue
         *   - log a warning to say that there was a deviation
         * NOTE - at present, the front does the warning on deivation too, but takes NO action
         * Action is being taken here, instead - responsibility of CMS to record correct/valid values
         *
         * Intended to fix minor differences, not major deviations
         */
        $sharesBought = $investment->getShareAmount();
        $sharePricePaid = $investment->getOrgPricePerShare();
        if (empty($investment->getOrgPricePerShare())) {
            $this->logger->warning(
                'Share price not provided by API call, verifying investmentValue using fallback.',
            );
            $sharePricePaid = $resultOffering->getAsset()->getPricePerShare();
        }
        if (empty($investment->getShareAmount())) {
            $this->logger->warning(
                'Share amount not provided by API call, verifying investmentValue using fallback.',
            );
            $sharesBought = round($investment->getInvestmentValue() / $sharePricePaid);
        }
        $calcInvValue = $sharesBought * $sharePricePaid;
        if ($investment->getInvestmentValue() != $calcInvValue) {
            $this->logger->warning(
                'Investment value mismatch. Received: '
                . $investment->getInvestmentValue()
                . '; Calculated: '
                . $calcInvValue,
            );
            $investmentValueChange = true;
            $investment->setInvestmentValue($calcInvValue);
        }

        $investment->setOffering($resultOffering);
        $investment->setUser($this->getUser());
        $em = $this->doctrine->getManager();
        /** @var InvestmentRepository $inv_repo */
        $inv_repo = $em->getRepository(Investment::class);
        $result = $inv_repo->save($investment, true);

        $investmentManager->sendInvestmentCreationMail($investment);

        if (isset($data->info)) {
            if (isset($data->info->transaction_id)) {
                //update the transaction with investment id
                $transactionManager->updateInvestmentIdonTransaction(
                    $data->info->transaction_id,
                    $investment->getId(),
                );
            }
        }

        if (isset($investmentValueChange) && $investmentValueChange) {
            $this->logger->warning(
                'Investment value mismatched and updated to: '
                    . $calcInvValue
                    . ' for investment id: '
                    . $investment->getId(),
            );
        }

        //Investment Manager to validate and submit a investment
        //        $result = $investmentManager->submitInvestment($investment);

        //  if ($result === true) {
        //means the investment passed validation and was submitted
        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'investment_id' => $investment->getId(),
            ],
            'status' => 200,
        ]);

        //} else {
        //  return $result;
        //}
    }

    /**
     * @param Request $request
     * @param integer $offering_id
     * @return JsonResponse
     */
    #[Get(
        '/%api_network_path%/offerings/{offering_id}/investments',
        name: 'api_get_offering_investments',
    )]
    #[Rest\View]
    #[Rest\QueryParam(name: 'visibility', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'term', requirements: '\d+')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    public function getOfferingInvestments(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        $offering_id,
    ) {
        $this->logger->info($request->getContent());

        $filterParam['visibility'] = $paramFetcher->get('visibility', 1);
        $filterParam['term'] = $paramFetcher->get('term');
        $filterParam['offset'] = $paramFetcher->get('offset');
        $filterParam['limit'] = $paramFetcher->get('limit');

        /** @var Offering $resultOffering */
        $resultOffering = $this->offeringManager->findOneById($offering_id);
        if (!$resultOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        //filtering should be done in the manager
        /** @var Investment */
        $offering_invests = $this->offeringManager->findInvestmentByOffering(
            $offering_id,
            $filterParam,
        );

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'offset' => $filterParam['offset'],
                'limit' => $filterParam['limit'],
                'count' => count($offering_invests),
                'list' => $offering_invests,
            ],
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @param Offering $offeringId
     * @return JsonResponse
     */
    #[Patch('/%api_network_path%/offerings/{offeringId}', name: 'api_patch_offering')]
    #[Rest\View]
    public function patchOffering(Request $request, $offeringId)
    {
        $this->logger->info($request->getContent());

        $singleOffering = $this->offeringManager->findOneById($offeringId);
        if (!$singleOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        if (
            $singleOffering->getId()
            && (
                $this->isGranted('ROLE_ADMIN')
                || $this->getUser()->getId() == $singleOffering->getasset()->getcontactPoint()->getId()
            )
        ) {
            $postRrequest = json_decode($request->getContent());
            if (!empty($postRrequest)) {
                $offeringId = $this->createNewOffering(
                    'update',
                    $postRrequest,
                    '',
                    $singleOffering,
                );
                return new JsonResponse([
                    'outcome' => 'success',
                    'data' => ['offering_id' => $offeringId],
                    'status' => 200,
                ]);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_OFFERING_EMPTY_FIELD);
            }
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }
    }

    /**
     * @param Request $request
     * @param $assetId
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/assets/{assetId}/offerings',
        name: 'api_post_asset_offerings',
    )]
    #[Post(
        '/%api_network_path%/organizations/{assetId}/offerings',
        name: 'api_post_organization_offerings',
    )]
    #[Rest\View]
    public function postAssetOfferings(
        Request $request,
        AssetManager $assetManager,
        int $assetId,
    ) {
        $this->logger->info($request->getContent());

        $singleAsset = $assetManager->findOneById($assetId);
        if (!$singleAsset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        if ($singleAsset->getId()) {
            $data = $request->getContent();
            $paramArr = json_decode($data);
            if (empty($data)) {
                return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
            }
            if (empty($paramArr->name)) {
                return new ErrorResponse(ErrorResponse::ERROR_OFFERING_MISSING_NAME);
            }
            if (empty($paramArr->funding_goal)) {
                return new ErrorResponse(ErrorResponse::ERROR_OFFERING_MISSING_FUNDING_GOAL);
            }

            /** @var Offering $build_offering */
            $build_offering = $this->offeringManager->buildOffering($paramArr);

            //Offering couldn't be created probably a param issue, send back a generic failure response
            if (empty($build_offering)) {
                return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
            }

            $build_offering->setAsset($singleAsset);

            //For database operations where asset will save along with address
            $em = $this->doctrine->getManager();
            /** @var \App\Repository\OfferingRepository */
            $repository = $em->getRepository(Offering::class);
            $repository->save($build_offering);
            $em->flush();

            $offeringId = $build_offering->getId();

            //check if offering created is by admin or a regular user send mail accordingly

            if (in_array($build_offering->getLifecycleStatus(), [
                OfferingLifecycle::STATE_SUBMITTED,
                OfferingLifecycle::STATE_APPROVED,
            ])) {
                $user = $this->getUser();
                if ($this->isGranted('ROLE_ADMIN') === true) {
                    $this->offeringManager->sendNewOfferingCreationMail(
                        $build_offering,
                    );
                } else {
                    $this->offeringManager->sendRelistOfferingCreationMail(
                        $build_offering,
                        $user,
                    );
                }
            }

            if (empty($offeringId)) {
                return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
            }

            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'offering_id' => $offeringId,
                ],
                'status' => 200,
            ]);
        }
    }

    #[IsGranted('ROLE_USER')]
    #[Post(
        '/%api_network_path%/offerings/{offeringId}/payments',
        name: 'api_create_offering_payment',
    )]
    public function createOfferingPayment(
        #[MapEntity(id: 'offeringId')] Offering $offering,
        #[MapRequestPayload] LinkedPaymentRequestDto $dto,
        MangoPay $mangopayService,
    ): JsonResponse {
        $this->logger->debug("APIv1 create offering #{$offering->getId()} fee payment");
        if ($offering->getSellInvestment() === null) {
            $this->logger->warning('Attempted to pay fee for non-relisted offering');
            return new ErrorResponse(
                ErrorResponse::ERROR_OFFERING_MISSING_INV_ID,
                null,
                true,
            );
        }
        // Can only edit own offerings
        if (
            $this->getUser()->getUserIdentifier() != $offering->getSellInvestment()->getUser()->getUserIdentifier()
        ) {
            $this->logger->warning("Attempted to pay fee for another user's offering");
            return new ErrorResponse(
                ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                null,
                true,
            );
        }
        if ($offering->getLifecycleStatus() != OfferingLifecycle::STATE_DRAFT) {
            $this->logger->warning('Attempted to pay fee for non-draft offering');
            return new ErrorResponse(
                ErrorResponse::ERROR_OFFERING_STATE_CHANGE_NOT_POSSIBLE,
                null,
                true,
            );
        }
        try {
            $mangopayTransfer = $mangopayService->createRelistingFeeTransfer(
                $offering,
                $dto->amount,
                $dto->sca,
            );
            $offering->setTransactionId($mangopayTransfer->Id);
            // $transaction = $transactionManager->createInvestmentTransaction($investment, $mangopayTransfer);
            // $this->doctrine->getManager()->persist($transaction);
            $this->doctrine->getManager()->flush();
        } catch (\Throwable $e) {
            if ($e->getCode() == 916) {
                $this->logger->error('Error occured in Mangopay createRelistingFeeTransfer', [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_INSUFFICIENT_FUNDS_IN_WALLET,
                    $e->getMessage(),
                    true,
                );
            } else {
                $this->logger->error('Error occured in Mangopay createRelistingFeeTransfer', [
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
                // 'transaction_id' => $transaction?->getId(),
                'offering_id' => $offering?->getId(),
            ],
            'status' => 200,
        ]);
    }

    #[IsGranted('ROLE_USER')]
    #[Post(
        '/%api_network_path%/offerings/{offeringId}/payment-outcome',
        name: 'api_create_offering_payment_outcome',
    )]
    public function submitOfferingPaymentOutcome(
        #[MapEntity(id: 'offeringId')] Offering $offering,
        #[MapRequestPayload] ScaOutcomeRequestDto $dto,
        OfferingManagerV2 $offeringManagerV2,
        MangopayScaService $mangopayScaService,
    ): JsonResponse {
        $this->logger->debug(
            "APIv1 submit offering #{$offering->getId()} payment outcome",
            [
                $dto->success,
                $dto->type,
            ],
        );
        if (
            !$this->isGranted('ROLE_ADMIN')
            && $offering->getSellInvestment() === null
        ) {
            $this->logger->warning('Attempted to pay fee for non-relisted offering');
            return new ErrorResponse(
                ErrorResponse::ERROR_OFFERING_MISSING_INV_ID,
                null,
                true,
            );
        }
        // Can only edit own offerings
        if (
            !$this->isGranted('ROLE_ADMIN')
            && $this->getUser()->getUserIdentifier() != $offering->getSellInvestment()->getUser()->getUserIdentifier()
        ) {
            $this->logger->warning("Attempted to pay fee for another user's offering");
            return new ErrorResponse(
                ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                null,
                true,
            );
        }

        $success = $dto->success;
        if ($dto->verify) {
            $success = $mangopayScaService->isTransferSucceeded($offering->getTransactionId());
        }

        $offeringManagerV2->processPaymentOutcome($offering, $success);
        $this->doctrine->getManager()->flush();

        if ($success) {
            // if ($this->isGranted('ROLE_ADMIN') === true) {
            //     $this->offeringManager->sendNewOfferingCreationMail($offering);
            // } else {

            // }

            // May want to in future check if $dto->type == "relisting"
            $this->offeringManager->sendRelistOfferingCreationMail(
                $offering,
                $this->getUser(),
            );
        }

        return new JsonResponse([
            'outcome' => 'success',
            'data' => [
                'offering' => $offering,
                'transfer_status' => $transfer?->Status ?? null,
                'payment_outcome' => $success,
            ],
            'status' => 200,
        ]);
    }

    protected function createNewOffering($type, $param, $asset, $singleOffering = '')
    {
        try {
            if ($type == 'create') {
                /** @var Offering */
                $Offering = new Offering();

                $Offering->setName($param->name);
                $Offering->setFundingGoal($param->funding_goal);
                $Offering->setAsset($asset);
            } else {
                /** @var Offering */
                $Offering = $singleOffering;

                if (!empty($param->name)) {
                    $Offering->setName($param->name);
                }
                if (!empty($param->is_secondary_offering)) {
                    $Offering->setIsSecondaryMrkt($param->is_secondary_offering);
                }
                if (!empty($param->life_cycle_stage)) {
                    $Offering->setLifecycleStatus($param->life_cycle_stage);
                }
                // Add field object creation
                if (!empty($param->add_field)) {
                    foreach ($param->add_field as $singleParam) {
                        $singleField = $this->createNewField($singleParam);
                        $Offering->addAddField($singleField);
                    }
                }
                if (!empty($param->valuation)) {
                    $Offering->setValuation($param->valuation);
                }
                if (!empty($param->equity_offered)) {
                    $Offering->setEquityOffered($param->equity_offered);
                }
                if (!empty($param->num_of_shares)) {
                    $Offering->setNoOfShares($param->num_of_shares);
                }
                if (!empty($param->price_per_shares)) {
                    $Offering->setPricePerShare($param->price_per_shares);
                }
            }
            if (!empty($param->net_rent_projected)) {
                $Offering->setNetRentProjected($param->net_rent_projected);
            }
            if (!empty($param->gross_project_return)) {
                $Offering->setGrossProjectReturn($param->gross_project_return);
            }
            if (!empty($param->open_date)) {
                $Offering->setOpenDate(new \DateTime($param->open_date));
            }
            if (!empty($param->close_date)) {
                $Offering->setCloseDate(new \DateTime($param->close_date));
            }
            if (!empty($param->min_commit_user)) {
                $Offering->setMinCommitUser($param->min_commit_user);
            }
            if (!empty($param->max_commit_user)) {
                $Offering->setMaxCommitUser($param->max_commit_user);
            }
            if (!empty($param->max_overfunding_amount)) {
                $Offering->setMaxOverFunding($param->max_overfunding_amount);
            }
            if (!empty($param->category)) {
                $Offering->setCategory($param->category);
            }
            if (!empty($param->visibility)) {
                $Offering->setVisibility($param->visibility);
            }

            $em = $this->doctrine->getManager();
            /** @var \App\Repository\OfferingRepository */
            $repository = $em->getRepository(Offering::class);
            $repository->save($Offering);
            $em->flush();

            $offeringId = $Offering->getId();
            $this->offeringManager->sendNewOfferingCreationMail($Offering);

            return $offeringId;
        } catch (\Exception $ex) {
            //return 'Duplicate Error';
            $this->logger->error($ex->getMessage());

            // dump($ex);
            // die;
            //TODO have to implement proper error message
        }
    }

    //Create add fields FOR ASSET
    protected function createNewField($param)
    {
        try {
            $addFields = new OfferingAddFields();

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

    /**
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/offerings/{offering_id}/documents',
        name: 'api_post_offering_document',
    )]
    #[Rest\View]
    public function postOfferingDocument(
        Request $request,
        DocumentManager $documentManager,
        int $offering_id,
    ) {
        $this->logger->info(Helper::cleanDocumentLogger($request->getContent()));
        /** @var Offering $singleOffering */
        $singleOffering = $this->offeringManager->findOneById($offering_id);
        if (!$singleOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        //Checking Contact point or admin
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->getUser()->getId() == $singleOffering->getAsset()->getContactPoint()->getId()
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
            $documentId = $this->createNewOfferingDocument(
                $documentManager,
                $paramArr,
                $singleOffering,
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
     * @param $param
     * @param Offering $offerings
     * @return int
     */
    protected function createNewOfferingDocument(
        DocumentManager $documentManager,
        $param,
        $offerings,
    ) {
        try {
            /** @var Document $documentObj */
            $documentObj = $documentManager->buildDocument(
                $param,
                'public',
                'offering/' . $offerings->getId(),
            );
            $offDocument = new OfferingDocuments();
            $offDocument->setCreatedById($this->getUser()->getId());
            $offDocument->setDocument($documentObj);
            $offerings->addDocument($offDocument);

            //For database operations where asset will save along with address
            $em = $this->doctrine->getManager();
            /** @var \App\Repository\OfferingRepository */
            $repository = $em->getRepository(Offering::class);
            $repository->save($offerings);
            $em->flush();

            $documentId = $documentObj->getId();
            return $documentId;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Offering $offering_id
     * @param Request $request
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\View]
    #[Get(
        '/%api_network_path%/offerings/{offering_id}/documents',
        name: 'api_get_offering_documents',
    )]
    public function getOfferingDocuments(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        OfferingDocumentManager $offeringDocumentManager,
        $offering_id,
    ) {
        $this->logger->info($request->getContent());

        /** @var Offering $resultOffering */
        $resultOffering = $this->offeringManager->findOneById($offering_id);
        if (!$resultOffering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        //Getting Filter and Offset
        $filterParam['offset'] = $paramFetcher->get('offset', 0);
        $filterParam['limit'] = $paramFetcher->get('limit', 10);

        $resultValues = $offeringDocumentManager->findDocumentsForOffering(
            $filterParam['offset'],
            $filterParam['limit'],
            $offering_id,
        );
        $totalCount = count($resultValues);
        if (!$resultValues) {
            $this->logger->warning('No documents found');
        }
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
}
