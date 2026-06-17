<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 30/11/16
 * Time: 14:09
 */

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Dto\BankAccountRegistrationDto;
use App\Dto\CardPayinDTO;
use App\Dto\KycReviewRequestDto;
use App\Dto\SalesforceSyncRequestDto;
use App\Dto\ScaStatusRequestDto;
use App\Entity\DirectDebit;
use App\Entity\Document;
use App\Entity\Enum\ScaStatus;
use App\Entity\Enum\WalletUserVersion;
use App\Entity\KycReview;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use App\Entity\UserLog;
use App\Exception\ApiException;
use App\Repository\PayoutRepository;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\ContegoService;
use App\Service\MailerService;
use App\Service\Manager\AssetManager;
use App\Service\Manager\DirectDebitManager;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\UserDocumentManager;
use App\Service\Manager\UserManager;
use App\Service\MangoPay;
use App\Service\MangopayWalletService;
use App\Service\Util\Helper;
use App\Service\Util\PasswordStrengthValidator;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use MangoPay\BankAccount;
use MangoPay\CardRegistration;
use MangoPay\KycDocument;
use MangoPay\UserNaturalSca;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Exception\LogicException;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;

class SelfController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private WorkflowInterface $userStateMachine,
        private UserRepository $userRepository,
    ) {}

    /**
     * @return JsonResponse
     */
    #[Get('%api_network_path%/self')]
    #[Rest\View]
    public function getSelfAction()
    {
        $this->logger->info('Get self');

        /** @var User $logged_user */
        $logged_user = $this->getUser();
        return new SuccessResponse([
            'user' => $logged_user->jsonInternal(),
        ]);
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Get('%api_network_path%/self/investments')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+')]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+')]
    #[Rest\View]
    public function getSelfInvestmentsAction(
        Request $request,
        ParamFetcherInterface $paramFetcher,
    ) {
        $this->logger->info($request->getContent());

        // get the user logged in
        /** @var User $logged_user */
        $logged_user = $this->getUser();

        $offset = $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit');
        $investments = $logged_user->getInvestments()->getValues();
        $filteredInvestments = [];
        foreach ($investments as $investment) {
            if (in_array($investment->getLifecycleStatus(), [
                InvestmentLifecycle::STATE_APPROVED,
                InvestmentLifecycle::STATE_SETTLED,
            ])) {
                $filteredInvestments[] = $investment;
            }
        }
        return new SuccessResponse([
            'offset' => $offset,
            'limit' => $limit,
            'count' => count($filteredInvestments),
            'list' => $filteredInvestments,
        ]);
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Get('%api_network_path%/self/offerings')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+')]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+')]
    #[Rest\View]
    public function getSelfOfferingsAction(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        OfferingManager $offeringManager,
    ) {
        $this->logger->info('Get self offerings' . $request->getContent());

        /** @var User $logged_user */
        $logged_user = $this->getUser();

        $offset = $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit');

        $userOfferings = $offeringManager->findAllValue(
            $offset,
            $limit,
            ['createdById' => $logged_user->getId()],
        );
        $filteredOfferings = [];
        foreach ($userOfferings as $offering) {
            if (in_array($offering->getLifecycleStatus(), [
                OfferingLifecycle::STATE_SUBMITTED,
                OfferingLifecycle::STATE_APPROVED,
                OfferingLifecycle::STATE_PUBLISHED,
            ])) {
                $filteredOfferings[] = $offering;
            }
        }
        return new SuccessResponse([
            'offset' => $offset,
            'limit' => $limit,
            'count' => count($filteredOfferings),
            'list' => $filteredOfferings,
        ]);
    }

    /**
     * @param Request $request
     * @return  JsonResponse
     */
    #[Post('%api_network_path%/self/resendVerificationEmail')]
    #[Rest\View]
    public function getSelfResendVerificationEmail(
        Request $request,
        EmailVerifier $emailVerifier,
    ) {
        // updated as part of fos user bundle deprecation
        $this->logger->info('getSelfResendVerificationEmail');
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        if (empty($data->url)) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_MISSING_VERIFY_URL);
        }

        // get the user logged in
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getStatus()->getIsEmailValidated() === true) {
            return new ErrorResponse(
                ErrorResponse::ERROR_USER_ALREADY_VERIFIED_EMAIL,
                'User Id:' . $user->getId(),
            );
        } else {
            try {
                $emailVerifier->sendEmailConfirmation($data->url, $user);
            } catch (\Exception $e) {
                $this->logger->error(
                    'Exception thrown resending verification email: '
                        . $e->getMessage(),
                );
                return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
            }

            return new SuccessResponse([
                'user_id' => $user->getId(),
            ]);
        }
    }

    /**
     * @param Request $request
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    #[Get('/self/assets')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', nullable: true, default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', nullable: true, default: 2)]
    #[Rest\View]
    public function getSelfAssets(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        AssetManager $assetManager,
    ) {
        $this->logger->info($request->getContent());

        $filterParam['offset'] = $request->get('offset') == null
            ? 0
            : $request->get('offset');
        $filterParam['limit'] = $request->get('limit') == null
            ? 2
            : $request->get('limit');

        $resultValues = $assetManager->findAllSelfAsset($filterParam);
        $totalCount = $assetManager->findAllSelfAssetCount();

        if (!empty($resultValues)) {
            return new SuccessResponse([
                'offset' => $filterParam['offset'],
                'limit' => $filterParam['limit'],
                'count' => $totalCount[0][1],
                'list' => $resultValues,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }
    }

    /**
     * @param Request $request
     * @param ParamFetcherInterface $paramFetcher
     * @return array
     */
    #[Get('%api_network_path%/self/users')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', nullable: true, default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', nullable: true, default: 2)]
    #[Rest\View]
    public function getUsersAction(
        Request $request,
        ParamFetcherInterface $paramFetcher,
    ) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION);
        }
        $offset = $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit');

        $em = $this->doctrine->getManager();
        /** @var \App\Repository\UserRepository */
        $repository = $em->getRepository(User::class);
        $userCount = $repository->count([]);
        $users = $repository->findBy([], [], $limit, $offset);
        if (!empty($users)) {
            return new SuccessResponse([
                'offset' => $offset,
                'limit' => $limit,
                'count' => $userCount,
                'list' => $users,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
        }
    }

    /**
     * @param Request $request
     * @return  JsonResponse
     */
    #[Patch('%api_network_path%/self')]
    #[Rest\View]
    public function patchSelfUsersAction(
        Request $request,
        UserManager $userManager,
        MailerService $mailerService,
    ) {
        $this->logger->info($request->getContent());

        //Checking Contact point or admin
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();
        $userRepo = $this->doctrine->getRepository(User::class);
        $singleUser = $userRepo->findOneBy(['id' => $userId]);
        if ($singleUser === null) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
        }

        // Get words of own (top yielder application)
        $topYielderOrigWords = $singleUser->getInvestor()->getWordsOfOwn();

        //Getting patch content
        $postRequest = $request->getContent();
        $paramArr = json_decode($postRequest);
        if (empty($paramArr)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        $user = $userManager->buildUser($paramArr, $singleUser);
        $this->doctrine->getManager()->flush();

        // Check if words of own (top yielder application) has changed
        $topYielderNewWords = $user->getInvestor()->getWordsOfOwn();
        if ($topYielderNewWords) {
            if ($topYielderOrigWords != $topYielderNewWords) {
                // dispatch a email confirming we have received the application
                $mailerService->sendMail($user, MailerService::TYPE_VIP_APPLICATION, [
                    'user' => $user,
                ]);
            }
        }

        /*        try{
         * $gdpr_accepted = $paramArr->gdpr_accepted;
         * if (!empty($gdpr_accepted) && $gdpr_accepted == 0){
         *
         * if ($user->isGDPRAccepted() == 0) {
         * $this->getUserManager()->sendGDPR_RejectMail($user);
         * }
         * }
         * }catch (Exception $e) {
         * echo 'Caught exception: ',  $e->getMessage(), "\n";
         * }*/

        //lets review the users attributes and determine the right state-- including //special case for GDPR
        $userManager->manageUserState($user);
        $this->logger->info('User Patched successfully: ' . $user->getId());

        if (!empty($userId)) {
            return new SuccessResponse([
                'user_id' => $userId,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
        }
    }

    //Create add fields FOR ASSET
    protected function createNewField($param)
    {
        try {
            $addFields = new UserCustomFields();

            if (!empty($param['field_key'])) {
                $addFields->setFieldKey($param['field_key']);
            }
            if (!empty($param['value'])) {
                $addFields->setFieldValue($param['value']);
            }
            return $addFields;
        } catch (\Doctrine\ORM\EntityNotFoundException $ex) {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Post('/%api_network_path%/self/documents')]
    #[Rest\View]
    public function postSelfDocument(Request $request, DocumentManager $documentManager)
    {
        $this->logger->info(Helper::cleanDocumentLogger($request->getContent()));

        if (empty($this->getUser())) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_LOGGED_IN);
        }

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
        $documentId = $this->createNewUserDocument($documentManager, $paramArr);

        if (intval($documentId)) {
            return new SuccessResponse([
                'document_id' => $documentId,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
        }
    }

    //for creating the document object and update the user
    protected function createNewUserDocument(DocumentManager $documentManager, $param)
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            /** @var Document $documentObj */
            $documentObj = $documentManager->buildDocument(
                $param,
                'private',
                'user/' . $user->getId(),
            );
            $userDocument = new UserDocument();
            $userDocument->setCreatedById($user->getId());
            $userDocument->setDocument($documentObj);
            $user->addDocument($userDocument);

            //For database operations where asset will save along with address
            $em = $this->doctrine->getManager();
            /** @var \App\Repository\UserRepository */
            $repository = $em->getRepository(User::class);
            $repository->save($user);
            $em->flush();

            $documentId = $documentObj->getId();
            return $documentId;
        } catch (\Exception $e) {
            $this->logger->error('Unable to create document: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Post('%api_network_path%/self/mangopayCardRegister')]
    #[Rest\View]
    public function postMangoPayCardRegister(
        Request $request,
        MangoPay $mangopayService,
    ) {
        $this->logger->info($request->getContent());
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay
        //$clean_data['currency'] = $data->currency;

        try {
            /** @var CardRegistration $mangopayCardRegister */
            $mangopayCardRegister = $mangopayService->cardRegistration(
                $this->getUser(),
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayCardRegister ' . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_REGISTER_CARD_FAILED,
                $e,
            );
        }
        return new SuccessResponse([
            'card_registration' => [
                'id' => $mangopayCardRegister->Id,
                'access_key' => $mangopayCardRegister->AccessKey,
                'preregistration_data' => $mangopayCardRegister->PreregistrationData,
                'card_registration_url' => $mangopayCardRegister->CardRegistrationURL,
            ],
        ]);
    }

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Get('/%api_network_path%/self/documents')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+')]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+')]
    #[Rest\View]
    public function getSelfDocuments(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        UserDocumentManager $userDocumentManager,
    ) {
        $this->logger->info($request->getContent());

        $offset = $paramFetcher->get('offset') == null
            ? 0
            : $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit') == null ? 0 : $paramFetcher->get('limit');

        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $resultValues = $userDocumentManager->findDocumentsForUser(
            $offset,
            $limit,
            $userId,
        );
        $totalCount = count($resultValues);

        if (!$resultValues) {
            return new ErrorResponse(ErrorResponse::ERROR_DOCUMENT_NOT_FOUND);
        }
        return new SuccessResponse([
            'offset' => $offset,
            'limit' => $limit,
            'count' => $totalCount,
            'list' => $resultValues,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ApiException
     */
    #[Post('%api_network_path%/self/mangopayCards')]
    #[Rest\View]
    public function postMangoPayCardCreate(Request $request, MangoPay $mangopayService)
    {
        $this->logger->info($request->getContent());
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay

        try {
            /** @var CardRegistration $mangopayCardRegister */
            $mangopayCardRegister = $mangopayService->cardCreate(
                $this->getUser(),
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayCardCreate ' . json_encode($e),
            );
            //throw new ApiException(ApiException::getErrorMessage(102));
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_CREATE_CARD_FAILED,
                $e,
            );
        }

        /* @var User $currentUser */
        $currentUser = $this->getUser();
        //@TODO we need to store the mangopay cardId at some point ?

        if ($mangopayCardRegister->Status == 'VALIDATED') {
            // This is the status we need for a card to be sucessfully regeistered
        } else {
            throw new ApiException(102);
        }
        return new SuccessResponse([
            'card_id' => $mangopayCardRegister->CardId,
        ]);
    }

    /**
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/mangopayCards/{card_id}/payin')]
    #[Rest\View]
    public function postMangoPayCardPayIn(
        MangoPay $mangopayService,
        string $card_id,
        #[MapRequestPayload(acceptFormat: ['json'])] CardPayinDTO $cardPayinDTO,
    ) {
        $user = $this->userRepository->find($cardPayinDTO->getUserId());
        if (!$user) {
            throw new NotFoundHttpException(sprintf(
                'User with id ' . $cardPayinDTO->getUserId() . ' does not exist',
            ));
        }
        try {
            $mangopayCardPayin = $mangopayService->cardPayIn(
                $user,
                $card_id,
                $cardPayinDTO,
            );
            return new SuccessResponse([
                'SecureModeRedirectURL' =>
                    $mangopayCardPayin->ExecutionDetails->SecureModeRedirectURL,
                'Id' => $mangopayCardPayin->Id,
                'Status' => $mangopayCardPayin->Status,
            ]);
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayCardPayIn ' . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_CREATE_CARD_PAYIN_FAILED,
                $e,
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/mangopayKycCheck')]
    #[Rest\View]
    public function postMangoPayKycCheck(Request $request, MangoPay $mangopayService)
    {
        $this->logger->info($request->getContent());
        //@TODO check lifecycle status
        //@TODO may want to validate the data we are passing to mangopay

        try {
            /** @var KycDocument $kycDocument */
            $kycDocument = $mangopayService->createKYCDocument($this->getUser());
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postMangoPayKycCheck ' . json_encode($e),
            );
            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_KYC_CHECK_FAILED);
        }
        return new SuccessResponse([
            'kyc_id' => $kycDocument->Id,
        ]);
    }

    /**
     * @param Request $request
     * @param ParamFetcherInterface $paramFetcher
     * @return JsonResponse
     */
    #[GET('/%api_network_path%/self/payouts', name: 'api_get_self_payouts')]
    #[Rest\QueryParam(name: 'page', requirements: '\d+', default: 1)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\View]
    public function getPayouts(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        PayoutRepository $payoutRepository,
    ) {
        $this->logger->info($request->getContent());

        $offset = $paramFetcher->get('page');
        $limit = $paramFetcher->get('limit');
        $payouts = [];
        $invPayouts = [];

        /** @var User $user */
        $user = $this->getUser();
        if ($user) {
            $investments = $user->getInvestments();
            if ($investments) {
                foreach ($investments as $investment) {
                    $invPayouts[] = $investment->getId();
                }
            }
            $payouts = $payoutRepository->findByInvestmentIdOrCreditedUser(
                $offset,
                $limit,
                $invPayouts,
                $user,
            );
        }

        if (!empty($payouts)) {
            return new SuccessResponse([
                'page' => $offset,
                'limit' => $limit,
                'count' => $payouts->getNbResults(),
                'list' => $payouts,
            ]);
        } else {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    /**
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/markEmailVerified')]
    #[Rest\View]
    public function setMarkEmailVerified(UserManager $userManager)
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->logger->info($user->getId());

        try {
            // @var Workflow $workflow
            $workflow = $this->userStateMachine;
            if ($workflow->can($user, UserLifecycle::TRANSITION_EMAIL_VERIFICATION)) {
                $workflow->apply($user, UserLifecycle::TRANSITION_EMAIL_VERIFICATION);

                // Apply the lifecycle change
                $userManager->verifyEmail($user);
                return new SuccessResponse([
                    'user_id' => $user->getId(),
                ]);
            } else {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_STATE_CHANGE_NOT_POSSIBLE,
                    UserLifecycle::TRANSITION_EMAIL_VERIFICATION,
                );
            }
        } catch (LogicException $e) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                UserLifecycle::TRANSITION_EMAIL_VERIFICATION,
            );
        }
    }

    /**
     * @return JsonResponse
     */
    #[Post('%api_network_path%/self/markRegistrationComplete')]
    #[Rest\View]
    public function setMarkRegistrationComplete()
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->logger->info($user->getId());
        try {
            // @var Workflow $workflow
            $workflow = $this->userStateMachine;
            if ($workflow->can(
                $user,
                UserLifecycle::TRANSITION_REGISTRATION_COMPLETE,
            )) {
                $workflow->apply(
                    $user,
                    UserLifecycle::TRANSITION_REGISTRATION_COMPLETE,
                );

                //Save the state
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();

                return new SuccessResponse([
                    'user_id' => $user->getId(),
                ]);
            } else {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_STATE_CHANGE_NOT_POSSIBLE,
                    UserLifecycle::TRANSITION_REGISTRATION_COMPLETE,
                );
            }
        } catch (LogicException $e) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                UserLifecycle::TRANSITION_REGISTRATION_COMPLETE,
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/blockUser')]
    #[Rest\View]
    public function setblockUser(Request $request)
    {
        $this->logger->info($request->getContent());
        try {
            /** @var User $user */
            $user = $this->getUser();

            // @var Workflow $workflow
            $workflow = $this->userStateMachine;

            if ($workflow->can($user, UserLifecycle::TRANSITION_APPROVE_TO_BLOCK)) {
                $workflow->apply($user, UserLifecycle::TRANSITION_APPROVE_TO_BLOCK);

                //The request body contains the data we need
                $data = json_decode($request->getContent());

                if (!empty($data->description)) {
                    $user->setLifecycleStatusComment($data->description);
                }
                $user->setEnabled(false);
                //Save the state
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();

                return new SuccessResponse([
                    'user_id' => $user->getId(),
                ]);
            } else {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_STATE_CHANGE_NOT_POSSIBLE,
                    UserLifecycle::TRANSITION_APPROVE_TO_BLOCK,
                );
            }
        } catch (LogicException $e) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                UserLifecycle::TRANSITION_APPROVE_TO_BLOCK,
            );
        }
    }

    /**
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/approveUser')]
    #[Rest\View]
    public function setApproveUser()
    {
        try {
            /** @var User $user */
            $user = $this->getUser();

            // @var Workflow $workflow
            $workflow = $this->userStateMachine;
            if ($workflow->can($user, UserLifecycle::TRANSITION_APPROVE)) {
                $workflow->apply($user, UserLifecycle::TRANSITION_APPROVE);

                //Save the state
                $em = $this->doctrine->getManager();
                $em->persist($user);
                $em->flush();

                return new SuccessResponse([
                    'user_id' => $user->getId(),
                ]);
            } else {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_STATE_CHANGE_NOT_POSSIBLE,
                    UserLifecycle::TRANSITION_APPROVE,
                );
            }
        } catch (LogicException $e) {
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                UserLifecycle::TRANSITION_APPROVE,
            );
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/changePassword', name: 'change_user_password')]
    #[Rest\View]
    public function changePassword(
        Request $request,
        ValidatorInterface $validator,
        UserManager $userManager,
        UserPasswordHasherInterface $userPasswordHasher,
    ) {
        $this->logger->info($request->getContent());

        try {
            //The request body contains the data we need
            $data = json_decode($request->getContent());

            if (empty($data->current_password)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_CURRENT_PASSWORD_MISSING);
            }
            if (empty($data->new_password)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_NEW_PASSWORD_MISSING);
            }
            if (empty($data->new_password_confirm)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_CONFIRM_PASSWORD_MISSING);
            }
            if ($data->new_password != $data->new_password_confirm) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_CONFIRM_PASSWORD_NOT_MACHING);
            }
            if ($data->new_password == $data->current_password) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_PASSWORD_MACHING_WITH_CURRENT_PASSWORD);
            }

            //validate the password strength
            $passwordStrengthValidator = new PasswordStrengthValidator();
            $valid_resp = $passwordStrengthValidator->validate($data->new_password);
            if (!empty($valid_resp)) {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_PASSWORD_STRENGTH,
                    $valid_resp,
                );
            }
            $valid = $validator->validate($data->current_password, new UserPassword());
            if ($valid->count()) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_CURRENT_PASSWORD_INVALID);
            }

            $new_password = trim($data->new_password);
            // $getEncodedPassword = $this->getEncodedPassword($new_password);
            /** @var User $user */
            $user = $this->getUser();

            // Encode(hash) the plain password.
            $encodedPassword = $userPasswordHasher->hashPassword($user, $new_password);

            $user->setPassword($encodedPassword);
            $this->doctrine->getManager()->flush();
            $userManager->sendPasswordChangeConfirmationMail($user);

            return new SuccessResponse([
                'user_id' => $user->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Could not change password: ' . $e->getMessage());
            return new ErrorResponse(ErrorResponse::ERROR_USER_PASSWORD_CAN_NOT_CHANGE);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/contegoCheck')]
    #[Rest\View]
    public function getSelfContegoCheck(
        Request $request,
        ContegoService $contegoService,
    ) {
        /** @var User $user */
        $user = $this->getUser();
        //@TODO check lifecycle status
        $this->logger->info(
            'Doing API request [/self/contegoCheck] ... for user ['
            . $user->getEmail()
            . ']',
        );

        try {
            $this->logger->info('doing service ... ');
            $contegoCheck = $contegoService->createUserKYC($user);
        } catch (\Exception $e) {
            $this->logger->error(
                '...... Contego request FAILED [' . $e->getMessage() . ']',
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_CONTEGO_USER_CHECK_FAILED,
                $e->getMessage(),
            );
        }
        $this->logger->info(
            '... Contego response is  [' . json_encode($contegoCheck) . ']',
        );
        return new SuccessResponse([
            'ContegoScore' => [
                'score' => $contegoCheck['data']['ContegoScore']['score'],
                'rag' => $contegoCheck['data']['ContegoScore']['rag'],
                'alerts' => [
                    $contegoCheck['data']['ContegoScore']['alerts'],
                ],
            ],
        ]);
    }

    /**
     * Check users local (crowdtek) contego score
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/contego')]
    #[Rest\View]
    public function getSelfContego(Request $request)
    {
        /** @var User $user */
        $user = $this->getUser();
        //@TODO check lifecycle status
        $this->logger->info(
            'Doing API request [/self/contego] ... for user ['
            . $user->getEmail()
            . ']',
        );

        if (is_null($user->getContegoScore())) {
            //If the user doesn't have any contego data then return outcome as error

            $this->logger->info(
                '...' . $user->getEmail() . ', has NO contego data yet!',
            );
            return new JsonResponse([
                'outcome' => 'error',
                'data' => [],
                'status' => 200,
            ]);
        } else {
            $this->logger->info(
                '...'
                . $user->getEmail()
                . ', has contego data already, lets send it back!',
            );
            return new SuccessResponse([
                'ContegoScore' => [
                    'score' => $user->getContegoScore()->getKycScore(),
                    'rag' => $user->getContegoScore()->getRAG(),
                    //Doesn't seem like we do anything with alerts at the front end, so just sending empty array
                    'alerts' => [],
                ],
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/contegoCheckCompany')]
    #[Rest\View]
    public function getSelfContegoCheckCompany(
        Request $request,
        ContegoService $contegoService,
    ) {
        //@TODO check lifecycle status
        try {
            /* @var KycDocument $kycDocument */
            $contegoCheck = $contegoService->createOrganisationKYC($this->getUser());
        } catch (\Exception $e) {
            // We were unable to sucessfully execute the mangopay call, lets return an api exception
            //var_dump($e->getMessage()); // @TODO include message from mangopay failure into ErrorResponse??
            $this->logger->error(
                '...... Contego request FAILED [' . $e->getMessage() . ']',
            );
            return new ErrorResponse(ErrorResponse::ERROR_CONTEGO_USER_CHECK_FAILED);
        }
        return new SuccessResponse([
            'ContegoScore' => [
                'score' => $contegoCheck['data']['ContegoScore']['score'],
                'rag' => $contegoCheck['data']['ContegoScore']['rag'],
                'alerts' => [
                    $contegoCheck['data']['ContegoScore']['alerts'],
                ],
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/contegoCheckPersonDoc')]
    #[Rest\View]
    public function getContegoCheckPersonDoc(
        Request $request,
        ContegoService $contegoService,
    ) {
        //@TODO check lifecycle status
        try {
            $contegoCheck = $contegoService->createUserKYCWithDoc($this->getUser());
        } catch (\Exception $e) {
            $this->logger->error(
                '...... Contego request FAILED [' . $e->getMessage() . ']',
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_CONTEGO_USER_CHECK_FAILED,
                $e->getMessage(),
            );
        }
        $this->logger->info(
            '... Contego response is  [' . json_encode($contegoCheck) . ']',
        );
        return new SuccessResponse([
            'ContegoScore' => [
                'score' => $contegoCheck['data']['ContegoScore']['score'],
                'rag' => $contegoCheck['data']['ContegoScore']['rag'],
                'alerts' => [
                    $contegoCheck['data']['ContegoScore']['alerts'],
                ],
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/mangopay/directDebitSetup')]
    #[Rest\View]
    public function postUserMangoDirectDebitSetup(
        Request $request,
        MangoPay $mangopayService,
        DirectDebitManager $directDebitManager,
    ) {
        $this->logger->info(
            'postUserMangoPayDirectDebitCreateBankAccount REQUEST DATA=['
            . $request->getContent()
            . ']',
        );

        //The request body contains the data we need
        $data = json_decode($request->getContent());
        /** @var User $user */
        $user = $this->getUser();
        try {
            /** @var Mandate $mangopayMandate */
            $mangopayMandate = $mangopayService->createMangopayMandate(
                $data,
                $user,
                $data->accountId,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangoPayDirectDebitCreateMandate '
                    . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_DIRECT_DEBIT_MANDATE_FAILED,
                $e,
            );
        }

        $directDebit = new DirectDebit();
        $creationDate = new \DateTime();
        $creationDate->setTimestamp($mangopayMandate->CreationDate);

        if ($data->bankAccountType === 'uk bank account') {
            $type = 'GB';
        } elseif ($data->bankAccountType === 'eu bank account') {
            $type = 'EU';
        }

        $directDebit->setUser($user);
        $directDebit->setMangopayBankaccountId($data->accountId);
        $directDebit->setmangopayMandateId($mangopayMandate->Id);
        $directDebit->setCreateDate($creationDate);
        $directDebit->setAccountType($type);
        $directDebit->setDirectDebitActive(true);
        $directDebit->setCurrency('GBP');
        $directDebit->setAmount($data->amount);
        $directDebit->setMandateUrl($mangopayMandate->DocumentURL);

        $this->doctrine->getManager()->persist($directDebit);
        $this->doctrine->getManager()->flush();

        $amount = number_format(((float) $data->amount / 100) + 0.60, 2, '.', '');

        $directDebitManager->sendDirectDebitSetupMail(
            $user,
            $mangopayMandate->RedirectURL,
            $data->nextPaymentDate,
            $amount,
        );
        return new SuccessResponse([
            'confirm_mandate_url' => $mangopayMandate->RedirectURL,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/directDebitCheck')]
    #[Rest\View]
    public function getDirectDebitCheck(Request $request, MangoPay $mangopayService)
    {
        /** @var User $user */
        $user = $this->getUser();

        $directDebit = $this->doctrine
            ->getRepository(DirectDebit::class)
            ->findOneBy(['user' => $user]);

        if (!$directDebit) {
            $this->logger->error('Error no direct debit');
            return new ErrorResponse(ErrorResponse::ERROR_USER_HAS_NO_DIRECT_DEBIT);
        }

        try {
            /** @var Mandate $mangopayMandate */
            $mangopayMandate = $mangopayService->getMandate($directDebit->getMangopayMandateId());
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay getMandate' . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_DIRECT_DEBIT_GET_MANDATE_FAILED,
                $e,
            );
        }

        if ($mangopayMandate->ResultCode === '001807') {
            $this->logger->info(
                'Deleting Direct Debit entity as mandate expired due to no confirmation. Mandate ID: ['
                . $directDebit->getMangopayMandateId()
                . '] User ID: ['
                . $user->getId()
                . '] Username: ['
                . $user->getUserIdentifier()
                . '] ',
            );

            //delete Direct Debit entinty as mandate has expired
            $entityManager = $this->doctrine->getManager();
            $entityManager->remove($directDebit);
            $entityManager->flush();

            return new ErrorResponse(ErrorResponse::ERROR_MANGOPAY_DIRECT_DEBIT_MANDATE_EXPIRED);
        }

        /** @var \MangoPay\BankAccount */
        $bankAccount = $mangopayService->getBankAccount(
            $user,
            $mangopayMandate->BankAccountId,
        );
        //The type can either be IBAN or GB
        if ($bankAccount->Type === 'IBAN') {
            $accountIban = $bankAccount->Details->IBAN;
            $sortBic = $bankAccount->Details->BIC;
        } elseif ($bankAccount->Type === 'GB') {
            $accountIban = $bankAccount->Details->AccountNumber;
            $sortBic = $bankAccount->Details->SortCode;
        }
        return new SuccessResponse([
            'DirectDebit' => [
                'status' => $mangopayMandate->Status,
                'status_code' => $mangopayMandate->ResultCode,
                'mandate_confirm' => $mangopayMandate->RedirectURL,
                'active' => $directDebit->getDirectDebitActive(),
                'amount' => $directDebit->getAmount(),
                'date_created' => $directDebit->getCreateDate(),
                'mandate_url' => $directDebit->getMandateUrl(),
                'account_iban' => $accountIban,
                'sort_bic' => $sortBic,
                'lastSettlementDate' => $directDebit->getLastSettlementDate(),
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Post('%api_network_path%/self/mangopay/bankaccounts')]
    #[Rest\View]
    public function postUserMangoPayCreateBankAccount(
        Request $request,
        MangoPay $mangopayService,
    ) {
        $this->logger->info(
            '========= IN postUserMangoPayCreateBankAccount  ========= ',
        );
        $this->logger->info(
            'postUserMangoPayCreateBankAccount REQUEST DATA=['
            . $request->getContent()
            . ']',
        );
        //The request body contains the data we need
        $data = json_decode($request->getContent());

        try {
            /** @var BankAccount $mangopayBankAccount */
            $mangopayBankAccount = $mangopayService->createMangopayUserBankAccount(
                $this->getUser(),
                $data,
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay postUserMangoPayCreateBankAccount '
                    . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_BANK_ACCOUNT_FAILED,
                $e,
            );
        }

        //We don't save these details, they are just presented to the web front end
        return new SuccessResponse([
            'bank_account' => [
                'id' => $mangopayBankAccount->Id,
                'type' => $mangopayBankAccount->Type,
                'owner_name' => $mangopayBankAccount->OwnerName,
                'account_number' => $mangopayBankAccount->Details->AccountNumber,
                'sort_code' => $mangopayBankAccount->Details->SortCode,
                'created_at' => $mangopayBankAccount->CreationDate,
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[POST('%api_network_path%/self/cancelDirectDebit')]
    #[Rest\View]
    public function cancelDirectDebit(
        Request $request,
        MangoPay $mangopayService,
        DirectDebitManager $directDebitManager,
    ) {
        $this->logger->info('========= IN cancelDirectDebit  ========= ');

        /** @var User $user */
        $user = $this->getUser();

        $directDebit = $this->doctrine
            ->getRepository(DirectDebit::class)
            ->findOneBy(['user' => $user]);

        try {
            /** @var Mandate $mangopayMandate */
            $mangopayMandate = $mangopayService->getMandate($directDebit->getMangopayMandateId());
        } catch (\Exception $e) {
            $this->logger->error(
                'Error occured in Mangopay getMandate' . json_encode($e),
            );
            return new ErrorResponse(
                ErrorResponse::ERROR_MANGOPAY_DIRECT_DEBIT_GET_MANDATE_FAILED,
                $e,
            );
        }

        if (
            $mangopayMandate->Status === 'SUBMITTED'
            || $mangopayMandate->Status === 'ACTIVE'
        ) {
            try {
                $mangopayService->cancelMandate($directDebit->getMangopayMandateId());
            } catch (\Exception $e) {
                $this->logger->error(
                    'Error occured in Mangopay cancelMandate' . json_encode($e),
                );
                return new ErrorResponse(
                    ErrorResponse::ERROR_MANGOPAY_REMOVING_DIRECT_DEBIT_MANDATE_FAILED,
                    $e,
                );
            }
        }

        $this->logger->info(
            'Deleting Direct Debit entity as Direct Debit cancellation requested by user. Mandate ID: ['
            . $directDebit->getMangopayMandateId()
            . '] User ID: ['
            . $user->getId()
            . '] Username: ['
            . $user->getUserIdentifier()
            . '] ',
        );

        //delete Direct Debit entity as requested
        $entityManager = $this->doctrine->getManager();
        $entityManager->remove($directDebit);
        $entityManager->flush();

        $directDebitManager->sendDirectDebitCancellationEmail($user);

        return new JsonResponse([
            'outcome' => 'success',
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[POST('%api_network_path%/self/updateDirectDebitAmount')]
    #[Rest\View]
    public function updateDirectDebitAmount(
        Request $request,
        DirectDebitManager $directDebitManager,
    ) {
        $this->logger->info('========= IN updateDirectDebitAmount ========= ');

        /** @var User $user */
        $user = $this->getUser();

        $directDebit = $this->doctrine
            ->getRepository(DirectDebit::class)
            ->findOneBy(['user' => $user]);

        $data = json_decode($request->getContent());

        if (is_null($directDebit)) {
            $this->logger->error('No Direct Debit found to update');
            return new ErrorResponse(ErrorResponse::ERROR_USER_HAS_NO_DIRECT_DEBIT);
        }

        $directDebit->setAmount($data->amount);
        $this->doctrine->getManager()->flush();

        $formatAmount = number_format(((float) $data->amount / 100) + 0.60, 2, '.', '');

        $directDebitManager->sendDirectDebitAmountChangeEmail($user, $formatAmount);

        return new JsonResponse([
            'outcome' => 'success',
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[POST('%api_network_path%/self/updateDirectDebitStatus')]
    #[Rest\View]
    public function updateDirectDebitStatus(Request $request)
    {
        $this->logger->info('========= IN updateDirectDebitStatus ========= ');

        /** @var User $user */
        $user = $this->getUser();

        $directDebit = $this->doctrine
            ->getRepository(DirectDebit::class)
            ->findOneBy(['user' => $user]);

        $data = json_decode($request->getContent());

        $this->logger->info(
            '========= Direct debit data  ========= ' . json_encode($data),
        );

        if (is_null($directDebit)) {
            $this->logger->error('No Direct Debit found to update');
            return new ErrorResponse(ErrorResponse::ERROR_USER_HAS_NO_DIRECT_DEBIT);
        }

        $directDebit->setDirectDebitActive($data->active);
        $this->doctrine->getManager()->flush();

        return new JsonResponse([
            'outcome' => 'success',
            'status' => 200,
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[Get('%api_network_path%/self/checkComplianceStatus')]
    #[Rest\View]
    public function getSelfcheckComplianceStatus(
        Request $request,
        MangoPay $mangopayService,
        ContegoService $contegoService,
    ) {
        // get the user logged in
        /** @var User $user */
        $user = $this->getUser();

        $this->logger->info(
            'Doing API request [/self/checkComplianceStatus] ... for user ['
            . $user->getEmail()
            . ']',
        );

        $compStatus = 'add_funds';
        $mp_status = '';
        $contego_status = '';
        $payin_total = 0;
        $invest_total = '';

        //        try {

        //$this->logger->info($user->getMangoPayUserId());
        /** @var UserNaturalSca $mp_response */
        $mp_response = $mangopayService->getUser($user->getMangoPayUserId());
        $mp_status = $mp_response->KYCLevel;

        $contego_status = $user->getContegoScore()->getRAG();

        return new SuccessResponse([
            'complianceStatus' => $compStatus,
            'details' => [
                'mp_status' => $mp_status,
                'contego_status' => $contego_status,
                'payin_total' => $payin_total,
                'invest_total' => $invest_total,
            ],
            'user' => [
                'has_been_approved' => $user->getStatus()->getIsApproved(),
                'has_been_blocked' => $user->getStatus()->getIsBlocked(),
                'registration_complete' => $user->getStatus()->getIsRegCompleted(),
                'term_service_accepted' => $user->isTermServiceAccepted(),
                'gdpr_accepted' => $user->isGDPRAccepted(),
                'ob_step' => $user->getOBStep(),
            ],
        ]);

        /* commented out and will be fully implemented in next release
         * /// get the payin
         * $payIns = $mangopayService->getUserMangoPayWalletPayIn($user);
         * //sum all the payins
         * foreach ($payIns as $p)
         * {
         * $payin_total = $payin_total + $p->CreditedFunds->Amount;
         * }
         *
         * $this->logger->error('Total payins amount: ' . $payin_total);
         *
         * //example transaction
         * //{"AuthorId":"22457557","CreditedUserId":"22457557",
         * //"DebitedFunds":{"Currency":"GBP","Amount":2500},
         * //"CreditedFunds":{"Currency":"GBP","Amount":2500},
         * //"Fees":{"Currency":"GBP","Amount":0},
         * //"Status":"SUCCEEDED","ResultCode":"000000","ResultMessage":"Success",
         * //"ExecutionDate":1539985220,"Type":"PAYIN","Nature":"REGULAR","DebitedWalletId":null,
         * //"CreditedWalletId":"22457558","Id":"56402159","Tag":null,"CreationDate":1539985203}
         *
         * $compStatus = $this->determineComplianceStatus($mp_status, $contego_status, $payin_total,$invest_total);
         */
    }

    #[Post('/%api_network_path%/self/sca/enroll', name: 'api_post_self_sca_enroll')]
    public function postSelfScaEnroll(
        MangopayWalletService $mangopayWalletService,
        SerializerInterface $serializer,
    ): ErrorResponse|JsonResponse {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Requesting Mangopay user SCA enrollment', [$user->getId()]);
        if (!$user->isAMangoPayUser()) {
            return new ErrorResponse(
                errorCode: ErrorResponse::ERROR_MANGOPAY_USER_MISSING_ID,
                useHttpStatus: true,
            );
        }
        try {
            $scaEnrollmentInfo = $mangopayWalletService->enrollUserSca($user->getMangoPayUserId());
            $user->setScaStatus(ScaStatus::Pending);
            $user->setScaEnrolledAt(new \DateTime());
            $this->doctrine->getManager()->flush();
        } catch (\Throwable $th) {
            $this->logger->error(
                "Error when requesting Mangopay user SCA enrollment for user {$user->getId()}.",
                [$th->getMessage()],
            );
            return new ErrorResponse(
                errorCode: ErrorResponse::ERROR_MANGOPAY_UNKNOWN,
                useHttpStatus: true,
            );
        }
        $jsonContent = $serializer->serialize($scaEnrollmentInfo, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    /**
     * Should technically make this part of patchSelf as scaStatus is part of user
     * But as with any spaghetti APIv1 stuff, better to append rather than modify
     */
    #[Patch('/%api_network_path%/self/sca/status', name: 'api_patch_self_sca_status')]
    public function patchSelfScaStatus(
        #[MapRequestPayload(acceptFormat: ['json'])] ScaStatusRequestDto $dto,
        SerializerInterface $serializer,
    ): ErrorResponse|JsonResponse {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('Updating user SCA status', [$user->getId()]);
        $user->setScaStatus($dto->status);
        // If ScaStatus is marked as active, upgrade the WalletUserVersion
        // This indicates that at some point, Sca enrollment was completed
        // Even if it has since been downgraded
        // WalletUserVersion should never be downgraded
        if (
            $dto->status === ScaStatus::Active
            && $user->getWalletUserVersion()->value
                < WalletUserVersion::UserScaEnrollment->value
        ) {
            $user->setWalletUserVersion(WalletUserVersion::UserScaEnrollment);
        }
        $this->doctrine->getManager()->flush();

        // In practice, the old frontend will be called getSelf for the full user data
        // But this response is useful for testing
        $jsonContent = $serializer->serialize([
            'scaStatus' => $user->getScaStatus(),
            'scaEnrolledAt' => $user->getScaEnrolledAt(),
        ], 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    #[Patch(
        '/%api_network_path%/self/kyc-reviews/{id}',
        name: 'api_patch_self_kyc_review',
    )]
    public function patchSelfKycReview(
        #[MapRequestPayload(acceptFormat: ['json'])] KycReviewRequestDto $dto,
        KycReview $kycReview,
        SerializerInterface $serializer,
    ): ErrorResponse|JsonResponse {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('User updating kyc review', [
            $user->getId(),
            $kycReview->getId(),
        ]);

        // User must be the subject of the KYC review
        if ($user->getId() !== $kycReview->getSubject()->getId()) {
            return new ErrorResponse(
                errorCode: ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION,
                useHttpStatus: true,
            );
        }
        $kycReview->setStatus($dto->status);
        $this->doctrine->getManager()->flush();

        $jsonContent = $serializer->serialize($kycReview, 'json');
        return JsonResponse::fromJsonString($jsonContent);
    }

    #[POST(
        '/%api_network_path%/self/salesforce-sync',
        name: 'api_post_self_salesforce_sync',
    )]
    public function postSelfSalesforceSync(
        #[MapRequestPayload(acceptFormat: ['json'])] SalesforceSyncRequestDto $dto,
        NormalizerInterface $normalizer,
        UserManager $userManagerLegacy,
    ): Response {
        /**
         * @var \App\Entity\User $user
         */
        $user = $this->getUser();
        $this->logger->debug('API User requesting salesforce sync', [$user->getId()]);

        $extraFields = $normalizer->normalize($dto->extraFields, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
        // $this->logger->debug("Extra fields to sync", $extraFields ?? []);

        $userManagerLegacy->syncWithSalesforce(
            user: $user,
            createIfMissing: $dto->createIfMissing,
            extraFields: $extraFields ?? [],
        );
        $this->doctrine->getManager()->flush();
        return new Response();
    }

    private function determineComplianceStatus(
        $mp_status,
        $contego_status,
        $payin_total,
        $invest_total,
    ) {
        return 'add_funds';
    }
}
