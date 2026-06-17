<?php

namespace App\Controller\ApiV2;

use App\Dto\BankwirePayinDTO;
use App\Dto\DocumentPostDTO;
use App\Dto\UserPatchDTO;
use App\Dto\UserPostDTO;
use App\Service\Manager\UserManagerV2;
use App\Service\Pagination\PaginatedCollection;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\RestBundle\View\View;
use Nelmio\ApiDocBundle\Annotation as Doc;
use OpenApi\Annotations as OA;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class UserController extends AbstractFOSRestController
{
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_ADMIN") and is_granted("ROLE_OAUTH2_USER:READ")',
        ),
    )]
    #[Route(path: '/users', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group. Avaliable groups: minimum, standard',
    )]
    #[Rest\QueryParam(
        name: 'page',
        requirements: '\d+',
        default: 1,
        description: 'Page number of the repsonse.',
    )]
    #[Rest\QueryParam(
        name: 'limit',
        requirements: '\d+',
        default: 10,
        description: 'Number of items returned in the response',
    )]
    public function getUsers(
        ParamFetcherInterface $paramFetcher,
        UserManagerV2 $userManager,
    ): Response {
        $context = new Context();
        $context->addGroups([$paramFetcher->get('view'), 'user', 'pagination']);

        $pagerfanta = $userManager->getUsers(
            $paramFetcher->get('page'),
            $paramFetcher->get('limit'),
        );

        $view = View::create()
            ->setData(new PaginatedCollection($pagerfanta))
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Post(
     *      summary="Create a new user",
     *      description="Create a new user",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=UserPostDTO::class)
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=201,
     *     description="Create a new User",
     *     @Doc\Model(type=User::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users', methods: ['POST'])]
    public function postUsers(
        UserManagerV2 $userManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])] UserPostDTO $userPostDTO,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard', 'user']);

        $user = $userManager->addUser($userPostDTO);
        $view = View::create()
            ->setData($user)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Return the User associated with the userId",
     *     @Doc\Model(type=User::class, groups={"standard", "user"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted(
        new Expression(
            'is_granted("ROLE_USER") and is_granted("ROLE_OAUTH2_USER:READ")',
        ),
    )]
    #[Route(path: '/users/{userId}', methods: ['GET'])]
    #[Rest\QueryParam(
        name: 'view',
        requirements: '(standard|admin)',
        nullable: true,
        default: 'standard',
        description: 'Serilization group. Avaliable groups: standard',
    )]
    public function getUserAction(
        ParamFetcherInterface $paramFetcher,
        UserManagerV2 $userManager,
        int $userId,
    ): Response {
        $context = new Context();

        if ($this->isGranted('ROLE_ADMIN')) {
            $context->addGroups([$paramFetcher->get('view'), 'user']);
        } else {
            $context->addGroups(['standard', 'user']);
        }

        $user = $userManager->getUser($userId);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('User with id '
            . $userId
            . ' does not exist'));
        }

        $view = View::create()->setData($user)->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Patch(
     *      summary="Update an existing User",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *               @OA\Property(property="email", type="string"),
     *               @OA\Property(property="title", type="string"),
     *               @OA\Property(property="firstName", type="string"),
     *               @OA\Property(property="lastName", type="string"),
     *               @OA\Property(property="dateOfBirth", type="string", format="date-time"),
     *               @OA\Property(property="nationality", type="string"),
     *               @OA\Property(property="address", type="object", ref=@Doc\Model(type="App\Dto\AddressDTO")),
     *               @OA\Property(property="countryOfResidence", type="string"),
     *               @OA\Property(property="phone", type="string"),
     *               @OA\Property(property="mobilePhone", type="string"),
     *               @OA\Property(property="marketingPreference", type="string"),
     *               @OA\Property(property="typeOfInvestor", type="string")
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Update a User",
     *     @Doc\Model(type=User::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}', methods: ['PATCH'])]
    public function patchUser(
        UserManagerV2 $userManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])] UserPatchDTO $userPatchDTO,
        int $userId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard', 'user']);

        $user = $userManager->updateUser($userId, $userPatchDTO);
        if (null === $user) {
            throw new NotFoundHttpException('User with id: '
            . $userId
            . ' does not exist.');
        }

        $view = View::create()->setData($user)->setContext($context);

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/password', methods: ['PUT'])]
    public function putUserPassword(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/password', methods: ['PATCH'])]
    public function patchUserPassword(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Post(
     *      summary="Verify a users email address - Admin only",
     *      description="Verify a users email address - Admin only"
     * )
     *
     * @OA\Response(
     *     response=200,
     *     description="Confirmation of the verified email"
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     */
    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/email-verification', methods: ['POST'])]
    public function postUserEmailverification(
        UserManagerV2 $userManager,
        int $userId,
    ): Response {
        $userManager->verifyUserEmail($userId);

        $view = View::create()->setData([
            'message' => 'Email successfully verified for user with id: ' . $userId,
        ]);

        return $this->handleView($view);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/documents', methods: ['GET'])]
    public function getUserDocuments(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Post(
     *      summary="Create a new user document",
     *      description="Create a new user document",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=DocumentPostDTO::class)
     *          )
     *      )
     * )
     *
     * @OA\Response(
     *     response=201,
     *     description="Create a new user document",
     *     @Doc\Model(type=UserDocument::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/documents', methods: ['POST'])]
    public function postUserDocuments(
        UserManagerV2 $userManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        DocumentPostDTO $documentDTO,
        int $userId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $userDocument = $userManager->addDocument($userId, $documentDTO);
        $view = View::create()
            ->setData($userDocument)
            ->setStatusCode(Response::HTTP_CREATED)
            ->setContext($context);

        return $this->handleView($view);
    }

    /**
     * @OA\Post(
     *      summary="Create a bankwire payin",
     *      description="Create a bankwire payin",
     *      @OA\RequestBody(
     *          description="JSON Payload",
     *          required=true,
     *          @OA\Schema(
     *              type="object",
     *              ref=@Doc\Model(type=App\Dto\BankwirePayinDTO::class)
     *          )
     *      )
     * )
     * @OA\Response(
     *     response=201,
     *     description="The bankwire payin details",
     *     @Doc\Model(type=App\Dto\BankwireDetails::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/payin', methods: ['POST'])]
    public function postUserBankwirePayin(
        UserManagerV2 $userManager,
        #[MapRequestPayload(acceptFormat: ['json', 'xml'])]
        BankwirePayinDTO $bankwirePayinDTO,
        int $userId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $payinDetails = $userManager->addBankwirePayin($userId, $bankwirePayinDTO);

        if ($payinDetails) {
            $view = View::create()
                ->setData($payinDetails)
                ->setStatusCode(Response::HTTP_CREATED)
                ->setContext($context);

            return $this->handleView($view);
        }

        throw new NotFoundHttpException(sprintf('User with id '
        . $userId
        . ' does not exist'));
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/documents/{userDocId}', methods: ['GET'])]
    public function getUserDocument(int $userId, int $userDocId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/documents/{userDocId}', methods: ['PATCH'])]
    public function patchUserDocument(int $userId, int $userDocId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/documents/{userDocId}', methods: ['DELETE'])]
    public function deleteUserDocument(int $userId, int $userDocId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/investments', methods: ['GET'])]
    public function getUserInvestments(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/offerings', methods: ['GET'])]
    public function getUserOfferings(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/payouts', methods: ['GET'])]
    public function getUserPayouts(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Details of the user e-wallet",
     *     @Doc\Model(type=App\Dto\Wallet::class, groups={"standard"})
     * )
     * @Doc\Security(name="PasswordOAuth2")
     * @OA\Tag(name="V2 User")
     *
     */
    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/wallets/{walletId}', methods: ['GET'])]
    public function getUserWallets(
        UserManagerV2 $userManager,
        int $userId,
        string $walletId,
    ): Response {
        $context = new Context();
        $context->addGroups(['standard']);

        $wallet = $userManager->getUserWallet($userId, $walletId);

        if ($wallet) {
            $view = View::create()
                ->setData($wallet)
                ->setStatusCode(Response::HTTP_OK)
                ->setContext($context);

            return $this->handleView($view);
        }

        throw new NotFoundHttpException(sprintf('User with id '
        . $userId
        . ' does not exist'));
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/wallets/{walletId}', methods: ['GET'])]
    public function getUserWallet(int $userId, string $walletId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/wallets/{walletId}/transactions', methods: ['GET'])]
    public function getUserWalletTransactions(int $userId, int $walletId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/wallets/{walletId}/deposits', methods: ['POST'])]
    public function postUserWalletDeposit(int $userId, int $walletId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/wallets/{walletId}/withdrawals', methods: ['POST'])]
    public function postUserWalletWithdrawal(int $userId, int $walletId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/bank-accounts', methods: ['GET'])]
    public function getUserBankAccounts(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/bank-accounts', methods: ['POST'])]
    public function postUserBankAccounts(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/bank-accounts', methods: ['DELETE'])]
    public function deleteUserBankAccounts(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    //GET, PUT, POST, PATCH, DELETE /users/:userId/direct-debit
    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/direct-debit', methods: ['GET'])]
    public function getUserDirectDebit(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/direct-debit', methods: ['PUT'])]
    public function putUserDirectDebit(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/direct-debit', methods: ['POST'])]
    public function postUserDirectDebit(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/direct-debit', methods: ['PATCH'])]
    public function patchUserDirectDebit(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/direct-debit', methods: ['DELETE'])]
    public function deleteUserDirectDebit(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:READ')]
    #[Route(path: '/users/{userId}/reports', methods: ['GET'])]
    public function getUserReports(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[IsGranted('ROLE_OAUTH2_USER:WRITE')]
    #[Route(path: '/users/{userId}/kyc-check', methods: ['POST'])]
    public function postUserKyc(int $userId): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    #[Route(path: '/users/recovery', methods: ['POST'])]
    public function postUserRecovery(): Response
    {
        return new JsonResponse(null, Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
