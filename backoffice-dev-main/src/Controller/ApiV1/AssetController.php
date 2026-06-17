<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Entity\Asset;
use App\Entity\AssetDocuments;
use App\Entity\Document;
use App\Entity\TRANS_TYPE_CONSTANT;
use App\Service\Manager\AssetDocumentManager;
use App\Service\Manager\AssetManager;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\TransactionManager;
use App\Service\Manager\UserManager;
use App\Service\MangoPay;
use App\Service\Util\Helper;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get as Get;
use FOS\RestBundle\Controller\Annotations\Patch as Patch;
use FOS\RestBundle\Controller\Annotations\Post as Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use MangoPay\Libraries\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AssetController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private AssetManager $assetManager,
    ) {}

    /**
     * @param Request $request
     * @param ParamFetcherInterface $paramFetcher
     * @param Asset $asset_id
     * @return JsonResponse
     *
     */
    #[Get('/%api_network_path%/organizations/{asset_id}', name: 'api_get_organization')]
    #[Get('/%api_network_path%/assets/{asset_id}', name: 'api_get_asset')]
    #[Rest\QueryParam(name: 'light')]
    #[Rest\View]
    public function getAsset(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        DocumentManager $documentManager,
        int $asset_id,
    ) {
        $this->logger->info($request->getContent());

        if ($this->isGranted('ROLE_ADMIN') === true) {
            $resultAsset = $this->assetManager->findOneById($asset_id);
        } else {
            $resultAsset = $this->assetManager->findAssetById($asset_id);
        }

        if (!$resultAsset || !$resultAsset instanceof Asset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        if (
            !empty($paramFetcher->get('light'))
            && $paramFetcher->get('light') == 'true'
        ) {
            $this->logger->debug('return light dataset');
            $resultAsset = $resultAsset->lightView();
        } else {
            $resultAsset = $documentManager->generatePublicCdnUrls([$resultAsset])[0];
        }
        return new SuccessResponse([
            'organization' => $resultAsset,
        ]);
    }

    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(
        name: 'sort',
        requirements: '^([+-]?[a-zA-Z]+,?)*$',
        nullable: true,
    )]
    #[Rest\QueryParam(name: 'id', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'status', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'type', requirements: '^\w+(,\w+)*$', nullable: true)]
    #[Rest\View]
    #[Get('/%api_network_path%/assets', name: 'api_get_assets')]
    #[Get('/%api_network_path%/organizations', name: 'api_get_organizations')]
    public function getAssets(
        ParamFetcherInterface $paramFetcher,
        DocumentManager $documentManager,
    ): JsonResponse {
        $queryParams = $paramFetcher->all(true);
        $this->logger->info('GET /assets with params ' . json_encode($queryParams));

        /**
         * If more granular permissions need to be applied
         * set the 2nd parameter for findAssets() to true
         * then do filtering manually after
         */
        $resultValues = $this->assetManager->findByQuery(
            $queryParams,
            $this->isGranted('ROLE_ADMIN'),
        );
        $resultValues = $documentManager->generatePublicCdnUrls($resultValues);

        return new SuccessResponse([
            'offset' => $queryParams['offset'],
            'limit' => $queryParams['limit'],
            'count' => count($resultValues),
            'list' => $resultValues,
        ]);
    }

    /**
     * Create a new Asset
     * @param Request $request
     * @return JsonResponse
     */
    #[Post('/%api_network_path%/assets', name: 'api_post_asset')]
    #[Post('/%api_network_path%/organizations', name: 'api_post_organization')]
    #[Rest\View]
    public function createAsset(Request $request)
    {
        $this->logger->info($request->getContent());

        $data = json_decode($request->getContent());
        if (empty($data)) {
            return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
        }
        if (empty($data->display_name)) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_MISSING_DISPLAY_NAME);
        }

        $build_asset = $this->assetManager->buildAsset($data);

        //Asset couldn't be created probably a param issue, send back a generic failure response
        if (empty($build_asset)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        //For database operations where asset will save along with address
        $em = $this->doctrine->getManager();
        /** @var \App\Repository\AssetRepository */
        $repository = $em->getRepository(Asset::class);
        $repository->save($build_asset);
        $em->flush();

        $assetId = $build_asset->getId();

        if (empty($assetId)) {
            return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
        }
        return new SuccessResponse([
            'organization_id' => $assetId,
        ]);
    }

    /**
     * @param Request $request
     * @param Asset $assetId
     * @return JsonResponse
     */
    #[Patch(
        '/%api_network_path%/organizations/{assetId}',
        name: 'api_patch_organization',
    )]
    #[Patch('/%api_network_path%/assets/{assetId}', name: 'api_patch_asset')]
    #[Rest\View]
    public function updateAsset(Request $request, int $assetId)
    {
        $this->logger->info('Asset:' . $assetId . ':' . $request->getContent());

        //The request body contains the data we need
        $data = json_decode($request->getContent());
        if (empty($data)) {
            return new ErrorResponse(ErrorResponse::ERROR_MISSING_REQUEST_DATA);
        }

        $resultAsset = $this->assetManager->findOneById($assetId);
        if (!$resultAsset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        /** @var Asset $build_asset */
        $build_asset = $this->assetManager->buildAsset($data, $resultAsset);

        //Asset couldn't be created probably a param issue, send back a generic failure response
        if (empty($build_asset)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        //For database operations where asset will save along with address
        $em = $this->doctrine->getManager();
        /** @var \App\Repository\AssetRepository */
        $repository = $em->getRepository(Asset::class);
        $repository->save($build_asset);
        $assetId = $build_asset->getId();
        $em->flush();

        if (empty($assetId)) {
            return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
        }
        return new SuccessResponse([
            'organization_id' => $resultAsset->getId(),
        ]);
    }

    /**
     * @param Asset $asset_id
     * @return JsonResponse
     */
    #[Get(
        '/%api_network_path%/organizations/{asset_id}/offerings',
        name: 'api_get_organizations_offerings',
    )]
    #[Get(
        '/%api_network_path%/assets/{asset_id}/offerings',
        name: 'api_get_assets_offerings',
    )]
    #[Rest\View]
    public function getAssetsOfferings(OfferingManager $offeringManager, int $asset_id)
    {
        $this->logger->info('Get offerings for asset: ' . $asset_id);

        /* @var Asset $resultAsset */
        // $resultAsset = $this->assetManager->findOneById($asset_id);

        /** @var \Doctrine\Common\Collections\Collection $resultOffering */
        $resultOfferings = $offeringManager->findAllOfferingForNonAdmin(
            ['asset' => $asset_id],
            [],
            1000,
            0,
        );

        return new SuccessResponse([
            'list' => $resultOfferings,
        ]);
    }

    /**
     * @param Request $request
     * @param Asset $assetId
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/organizations/{assetId}/mangopayWallet',
        name: 'api_post_organization_mangppaywallets',
    )]
    #[Post(
        '/%api_network_path%/assets/{assetId}/mangopayWallet',
        name: 'api_post_asset_mangppaywallets',
    )]
    #[Rest\View]
    public function createAssetMangoPayWallet(
        Request $request,
        MangoPay $mangopayService,
        int $assetId,
    ) {
        $this->logger->info($assetId);
        $this->logger->info($request->getContent());

        $requestData = json_decode($request->getContent());

        $em = $this->doctrine->getManager();

        /* @var Asset $resultAsset */
        $resultAsset = $this->assetManager->findOneById($assetId);

        //Verify the asset exists
        if (!$resultAsset) {
            throw new Exception('Unable to find asset with id [' . $assetId . ']');
        }

        $contactPointUser = $resultAsset->getContactPoint();

        /* @todo move below to manager */
        try {
            //See if we passed an additional_wallet parameter in the request
            if (isset($requestData->additional_wallet)) {
                // We need to create a wallet for the point of contact, tagging it with the asset id (and Actual Wallet)
                $this->logger->info('... Found additional_wallet in request as ['
                . $requestData->additional_wallet
                . ']');
                $additionalWallet = $requestData->additional_wallet;

                //The contact point is the user who created the asset from the front end
                //We need to create a wallet for this user and tag it with the assetId

                /** @var \MangoPay\Wallet $mangoPayUserWallet */
                $mangoPayUserWallet = $mangopayService->createUserWallet(
                    $contactPointUser,
                    $resultAsset->getId() . ' Actual Wallet',
                );
                $resultAsset->setAdditionalWallet($mangoPayUserWallet->Id);
            } else {
                $this->logger->info('... No additional_wallet in request');

                /** @var \MangoPay\Wallet $mangoPayUserWallet */
                $mangoPayUserWallet = $mangopayService->createUserWallet(
                    $contactPointUser,
                    $resultAsset->getId() . ' Holding Wallet',
                );
                $resultAsset->setMangoPayWalletId($mangoPayUserWallet->Id);
            }
        } catch (Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay createUserWallet ' . json_encode($e),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_CREATE_USER_WALLET_FAILED);
        }

        //Commented out due to using natural user wallets for asset instead of legal user
        //Does this asset have a  mangopayId ?
        //if (!$resultAsset->isAMangoPayAsset()) {
        //    // Asset does not have mangopay account, let's  create one
        //    $this->registerAssetMangoPay($assetId);
        //}

        //Does this asset have a  mangopayId ?
        //if (!$resultAsset->isAMangoPayAsset()) {
        //    return new JsonResponse([
        //        'outcome' => 'fail',
        //        'data' => [
        //            'user_message' => 'Asset does not have a MangoPay account!',
        //            'developer_message' => 'Asset does not have a Mangopay account, mangoPayUserId is not set',
        //       ],
        //       'status' => 4000,
        //   ]);
        // }

        // If the asset has a mangopayid we can create an wallet for this asset
        // $mangoPayUserWallet = $mangopayService->createAssetWallet($resultAsset);

        //$resultAsset->setMangoPayWalletId($mangoPayUserWallet->Id);

        $em->flush();

        return new SuccessResponse([
            'mangopay_wallet_id' => $mangoPayUserWallet->Id,
        ]);
    }

    /**
     * @param Asset $assetId
     * @return JsonResponse
     */
    #[Rest\View]
    #[Get(
        '/%api_network_path%/organizations/{assetId}/mangopayWallets',
        name: 'api_get_organization_mangppaywallets',
    )]
    #[Get(
        '/%api_network_path%/assets/{assetId}/mangopayWallets',
        name: 'api_get_asset_mangppaywallets',
    )]
    public function getAssetMangoPayWallet(MangoPay $mangopayService, int $assetId)
    {
        $em = $this->doctrine->getManager();

        /** @var Asset $resultAsset */
        $resultAsset = $this->assetManager->findOneById($assetId);

        //Get the user associated with this asset
        /** @var \App\Entity\User $assetUser */
        $assetUser = $resultAsset->getContactPoint();

        //@TODO check lifecycle status

        //Does this asset have a  mangopayId ?
        if (!$resultAsset->isAMangoPayAsset()) {
            return new JsonResponse([
                'outcome' => 'fail',
                'data' => [
                    'user_message' => 'Asset does not have a MangoPay account!',
                    'developer_message' => 'Asset does not have a Mangopay account, mangoPayUserId is not set',
                ],
                'status' => 400,
            ]);
        }

        // If the asset has a mangopayid we can create an wallet for this asset
        /** @var \MangoPay\Wallet */
        $mangoPayUserWallet = $mangopayService->createAssetWallet($resultAsset);
        $resultAsset->setMangoPayWalletId($mangoPayUserWallet->Id);
        $em->flush();

        return new SuccessResponse([
            'mangopay_wallet_id' => $mangoPayUserWallet->Id,
        ]);
    }

    /**
     * @return JsonResponse
     */
    #[Post(
        '/%api_network_path%/assets/{assetId}/documents',
        name: 'api_post_asset_document',
    )]
    #[Post(
        '/%api_network_path%/organizations/{assetId}/documents',
        name: 'api_post_asset_organization_document',
    )]
    #[Rest\View]
    public function postAssetsDocument(
        Request $request,
        DocumentManager $documentManager,
        int $assetId,
    ) {
        $this->logger->info(Helper::cleanDocumentLogger($request->getContent()));

        /* @var Asset $singleAsset */
        $singleAsset = $this->assetManager->findOneById($assetId);
        if (!$singleAsset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        //Checking Contact point or admin
        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->getUser()->getId() == $singleAsset->getContactPoint()->getId()
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
            $documentId = $this->createNewAssetDocument(
                $documentManager,
                $paramArr,
                $singleAsset,
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
     * for creating the document object and update the asset7
     * @param $param
     * @param Asset $asset
     * @return bool
     */
    protected function createNewAssetDocument(
        DocumentManager $documentManager,
        $param,
        Asset $asset,
    ) {
        try {
            /** @var Document $documentObj */
            $documentObj = $documentManager->buildDocument(
                $param,
                'public',
                'asset/' . $asset->getId(),
            );
            $assetDocument = new AssetDocuments();
            $assetDocument->setCreatedById($this->getUser()->getId());
            $assetDocument->setDocument($documentObj);
            $asset->addDocument($assetDocument);

            //For database operations where asset will save along with address
            $em = $this->doctrine->getManager();
            /** @var \App\Repository\AssetRepository */
            $repository = $em->getRepository(Asset::class);
            $repository->save($asset);
            $documentId = $documentObj->getId();
            $em->flush();

            return $documentId;
        } catch (\Exception $ex) {
            return false;
        }
    }

    /**
     * @param Asset $assetId
     * @return JsonResponse
     */
    #[Post('/%api_network_path%/self/assets/{assetId}/mangopayRegister')]
    #[Post('/%api_network_path%/self/organizations/{assetId}/mangopayRegister')]
    #[Rest\View]
    public function registerAssetMangoPay(MangoPay $mangopayService, int $assetId)
    {
        $this->logger->info('In registerAssetMangoPay');

        $em = $this->doctrine->getManager();

        /** @var Asset $resultAsset */
        $resultAsset = $this->assetManager->findOneById($assetId);

        //Get the user associated with this asset
        /** @var \App\Entity\User $assetUser */
        $assetUser = $resultAsset->getContactPoint();

        try {
            $mangoPayUserUser = $mangopayService->createLegalUser(
                $resultAsset,
                $assetUser,
            );
        } catch (Exception $e) {
            $this->logger->error(
                'Error occured while trying to register an asset with mangopay '
                    . json_encode($e),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_RESGISTER_ASSET_FAILED);
        }

        $resultAsset->setMangoPayUserId($mangoPayUserUser->Id);
        $em->flush();

        return new SuccessResponse([
            'mangopay_id' => $mangoPayUserUser->Id,
        ]);
    }

    /**
     * @param Asset $asset_id
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\View]
    #[Get(
        '/%api_network_path%/assets/{asset_id}/documents',
        name: 'api_get_asset_documents',
    )]
    #[Get(
        '/%api_network_path%/organizations/{asset_id}/documents',
        name: 'api_get_organizations_documents',
    )]
    public function getAssetDocuments(
        Request $request,
        AssetDocumentManager $assetDocumentManager,
        ParamFetcherInterface $paramFetcher,
        int $asset_id,
    ) {
        $this->logger->info($request->getContent());
        $resultAsset = $this->assetManager->findOneById($asset_id);
        if (!$resultAsset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        //Getting Filter and Offset
        $filterParam['offset'] = $paramFetcher->get('offset', 0);
        $filterParam['limit'] = $paramFetcher->get('limit', 10);

        //if ($this->isGranted('ROLE_ADMIN') === true) {
        $resultValues = $assetDocumentManager->findDocumentsForAsset(
            $filterParam['offset'],
            $filterParam['limit'],
            $asset_id,
        );
        $totalCount = count($resultValues);

        //}

        if (!$resultValues) {
            return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_NOT_FOUND);
        }
        return new SuccessResponse([
            'offset' => $filterParam['offset'],
            'limit' => $filterParam['limit'],
            'count' => $totalCount,
            'list' => $resultValues,
        ]);
    }

    /**
     * @param Request $request
     * @param $asset_id
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/organizations/{asset_id}/mangopayRepayment')]
    #[Rest\View]
    public function postMangopayRepayment(
        Request $request,
        MangoPay $mangopayService,
        TransactionManager $transactionManager,
        int $asset_id,
    ) {
        $this->logger->info($request->getContent());
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        $resultAsset = $this->assetManager->findOneById($asset_id);
        if (!$resultAsset) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        //{"amount":512.5,"fee_amount":1250,"user_wallet_id":"22102008","org_wallet_id":"22660652"}
        //validate the request
        if (empty($data->amount)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
        if (empty($data->user_wallet_id)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        $userRepo = $this->doctrine->getRepository(User::class);
        $em = $this->doctrine->getManager();

        //see issue 943 for a full explanation
        $debit_wallet = $data->org_wallet_id;
        $credit_wallet = $data->user_wallet_id;

        //special case swapping the wallets to change the flow of funds from the wallet to the admin user
        $data->user_wallet_id = $debit_wallet;
        $data->org_wallet_id = $credit_wallet;

        $this->logger->debug('lookup using wallet id:' . $debit_wallet);
        //need to determine which admin user owns the debit wallet account
        /** @var \MangoPay\Wallet */
        $mangopay_wallet = $mangopayService->getWallet($debit_wallet);

        $this->logger->debug('lookup using owner id:' . $mangopay_wallet->Owners[0]);
        $currentUser = $userRepo->findOneBy([
            'mangoPayUserId' => $mangopay_wallet->Owners[0],
        ]);

        if (empty($currentUser)) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                'Could not determine the Owner of the Debit Wallet',
            );
        }

        $this->logger->debug(
            'Author user id:' . $currentUser->getId() . ' : '
                . $currentUser->getUserIdentifier(),
        );

        try {
            /** @var \MangoPay\Transfer $mangopayTransfer */
            $mangopayTransfer = $mangopayService->createTransfer($currentUser, $data);
            $this->logger->info(new JsonResponse($mangopayTransfer));
            $this->logger->info('Creating a transaction for transfer');
            //create a transaction // do this in the transaction manager
            $internal_trans_id = $transactionManager->createTransaction(
                $data,
                $mangopayTransfer,
                TRANS_TYPE_CONSTANT::TRANS_DIV,
                $currentUser,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangopayRepayment ' . json_encode($e),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_TRANSFER_FAILED, $e);
        }
        return new SuccessResponse([
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
            ],
            'internal_transaction_id' => $internal_trans_id,
        ]);
    }
}
