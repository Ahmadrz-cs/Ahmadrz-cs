<?php

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Entity\Address;
use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\Investor;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserDocument;
use App\Entity\UserLog;
use App\Exception\ApiException;
use App\Service\Manager\UserManager as ManagerUserManager;
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
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
    ) {}

    /**
     *
     * @param Request $request
     * @param integer $user_id (The id of the User)
     * @return JsonResponse
     */
    #[Rest\View]
    #[Get('/%api_network_path%/users/{user_id}')]
    public function getSingleUser(Request $request, int $user_id)
    {
        $this->logger->info($request->getContent() . $user_id);
        /** @var \App\Entity\User */
        $currentUser = $this->getUser();

        // $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN') || $user_id == $currentUser->getId()) {
            $userRepo = $this->doctrine->getRepository(User::class);
            $user = $userRepo->findOneBy(['id' => $user_id]);

            if (!$user || !$user instanceof User) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
            }
            return new SuccessResponse([
                'user' => $user,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_ENTITLEMENTS_FOR_ACTION);
        }
    }

    // /**
    //  * @param Request $request
    //  * @param String $id
    //  * @Rest\View(statusCode = 201)
    //  * @return \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
    //  */
    // public function postUserFilesAction(Request $request, $id)
    // {
    //     $this->logger->info( Helper::cleanDocumentLogger( $request->getContent()));
    //     $id = (int)$id;
    //     if ($this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() === $id) {
    //         $userDocument = new UserDocument();
    //         $document = new Document();
    //         $userDocument->setDocument($document)
    //             ->setUser($this->getUser());
    //         $this->getUser()->addDocument($userDocument);
    //         $form = $this->createFormBuilder($document, [
    //             'csrf_protection' => false,
    //             'allow_extra_fields' => true,
    //         ])
    //             ->add('file', FileType::class)
    //             ->add('name')
    //             ->add('type')
    //             ->getForm();
    //         $form->handleRequest($request);
    //         if ($form->isValid()) {
    //             $this->doctrine->getManager()->persist($this->getUser());
    //             $log = new UserLog();
    //             $log->setUser($this->getUser())
    //                 ->setType(UserLog::TYPE_USER)
    //                 ->setEvent('user.file_upload')
    //                 ->setMessage('You uploaded the file "' . $userDocument->getDocument()->getName() . '" on %timestamp%');
    //             $this->doctrine->getManager()->persist($log);
    //             $this->doctrine->getManager()->flush();
    //             return $userDocument;
    //         } else {
    //             return $form->getErrors(true);
    //         }
    //     }
    // }
    /**
     * @param ParamFetcherInterface $paramFetcher
     * @return array
     */
    #[Get('%api_network_path%/users', name: 'api_get_users')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+', default: 0)]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+', default: 10)]
    #[Rest\QueryParam(
        name: 'sort',
        requirements: '^([+-]?[a-zA-Z]+,?)*$',
        nullable: true,
    )]
    #[Rest\QueryParam(name: 'id', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\QueryParam(name: 'status', requirements: '^\d+(,\d+)*$', nullable: true)]
    #[Rest\View]
    public function getUsers(
        ParamFetcherInterface $paramFetcher,
        ManagerUserManager $userManager,
    ) {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createNotFoundException('Resource not found!');
        }

        $queryParams = $paramFetcher->all(true);
        $this->logger->info('GET /offerings with params ' . json_encode($queryParams));

        $resultValues = $userManager->findByQuery(
            $queryParams,
            $this->isGranted('ROLE_ADMIN'),
        );

        $em = $this->doctrine->getManager();
        /** @var \App\Repository\UserRepository */
        $repository = $em->getRepository(User::class);
        $userCount = $repository->count([]);

        if (!empty($resultValues)) {
            return new SuccessResponse([
                'offset' => $queryParams['offset'],
                'limit' => $queryParams['limit'],
                'count' => $userCount,
                'list' => $resultValues,
            ]);
        } else {
            throw $this->createNotFoundException('Resource not found');
        }
    }

    /**
     * @param Request $request
     * @param $userId
     * @return ErrorResponse|JsonResponse
     */
    #[Patch('/%api_network_path%/users/{userId}')]
    public function patchUsersAction(
        Request $request,
        ManagerUserManager $userManager,
        int $userId,
    ) {
        $this->logger->info('user id:' . $userId . ':' . $request->getContent());
        $userRepo = $this->doctrine->getRepository(User::class);
        $singleUser = $userRepo->findOneBy(['id' => $userId]);

        if ($singleUser === null) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
        }

        //Getting patch content
        $postRequest = $request->getContent();
        $paramArr = json_decode($postRequest);

        if (empty($paramArr)) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        //Checking Contact point or admin
        //only an admin or the user can update the user with the same id
        if ($this->isGranted('ROLE_ADMIN') || $this->getUser()->getId() == $userId) {
            $user = $userManager->buildUser($paramArr, $singleUser);
            $this->doctrine->getManager()->flush();
            $this->logger->info('User Patched successfully: ' . $user->getId());

            if (!empty($userId)) {
                return new SuccessResponse([
                    'user_id' => $user->getId(),
                ]);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
            }
        } else {
            $this->logger->error("Attempt to patch user that wasn't the admin or user");
            return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
        }
    }
}
