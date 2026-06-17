<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 01/12/16
 * Time: 13:49
 */

namespace App\Controller\ApiV1;

use App\Controller\ApiV1\Response\ErrorResponse;
use App\Controller\ApiV1\Response\SuccessResponse;
use App\Entity\Asset;
use App\Entity\BaseEntity;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\Offering;
use App\Entity\User;
use App\Entity\Wallet;
use App\Security\EmailVerifier;
use App\Service\Manager\AssetManager;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\OfferingManager;
use App\Service\Manager\UserManager as ManagerUserManager;
use App\Service\Util\PasswordStrengthValidator;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Request\ParamFetcherInterface;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ExpiredResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class PublicController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private string $defaultCcy,
        private ?string $apiEmailCheck,
    ) {}

    /**
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Get('%api_network_public_path%/offerings')]
    #[Get('%api_network_public_path%/featuredOfferings')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+')]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+')]
    #[Rest\View]
    public function getPublicOfferings(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        OfferingManager $offeringManager,
        DocumentManager $documentManager,
    ) {
        $this->logger->info($request->getContent());

        $offset = $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit');

        /** @var Offering[] $resultValues */
        $resultValues = $offeringManager->findAllValue($offset, $limit, [
            'isFeatured' => 1,
        ]);

        $totalCount = count($resultValues);
        if (!empty($resultValues)) {
            $documentManager->generatePublicCdnUrls($resultValues);

            /*public view of offering should be returned
             * we need to call the publicView on asset*/
            /** @var Offering $singleoffering */
            foreach ($resultValues as $singleoffering) {
                $finalResults[] = $singleoffering->publicView();
                unset($singleasset);
            }

            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'count' => $totalCount,
                    'list' => $finalResults,
                ],
                'status' => 200,
            ]);
        } else {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }
    }

    /**
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    #[Get('%api_network_public_path%/offerings/{id}')]
    #[Rest\View]
    public function getPublicOffering(
        Request $request,
        OfferingManager $offeringManager,
        DocumentManager $documentManager,
        int $id,
    ) {
        $this->logger->info($request->getContent() . ':offering_id:' . $id);

        /** @var Offering[] $resultValues */
        $resultValues = $offeringManager->findAllValue(null, null, [
            'id' => $id,
            'isFeatured' => 1,
        ]);
        if (empty($resultValues)) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }

        /** @var Offering $singleResultValue */
        $singleResultValue = $resultValues[0];
        if (!$singleResultValue || !$singleResultValue instanceof Offering) {
            return new ErrorResponse(ErrorResponse::ERROR_OFFERING_NOT_FOUND);
        }
        $documentManager->generatePublicCdnUrls([$singleResultValue])[0];

        return new JsonResponse([
            'outcome' => 'success',
            'data' => ['offering' => $singleResultValue->publicView()],
            'status' => 200,
        ]);
    }

    /**
     *
     *
     * @param ParamFetcherInterface $paramFetcher
     * @param Request $request
     * @return JsonResponse
     */
    #[Get('%api_network_public_path%/assets')]
    #[Rest\QueryParam(name: 'offset', requirements: '\d+')]
    #[Rest\QueryParam(name: 'limit', requirements: '\d+')]
    #[Rest\View]
    public function getPublicAssets(
        Request $request,
        ParamFetcherInterface $paramFetcher,
        AssetManager $assetManager,
        DocumentManager $documentManager,
    ) {
        $this->logger->info($request->getContent());

        $offset = $paramFetcher->get('offset');
        $limit = $paramFetcher->get('limit');
        $resultValues = $assetManager->findAllValue($offset, $limit, [
            'visibility' => 2,
        ]);
        $totalCount = $assetManager->findAllCount([
            'visibility' => BaseEntity::VISIBILITY_ALL,
        ]);

        if (!empty($resultValues)) {
            $documentManager->generatePublicCdnUrls($resultValues);

            //public view of asset should be returned
            //we need to call the publicView on asset
            /** @var Asset $singleasset */
            foreach ($resultValues as $singleasset) {
                $finalResults[] = $singleasset->publicView();
                unset($singleasset);
            }

            return new JsonResponse([
                'outcome' => 'success',
                'data' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'count' => $totalCount[0][1],
                    'list' => $finalResults,
                ],
                'status' => 200,
            ]);
        } else {
            throw $this->createNotFoundException('Resource not found!');
        }
    }

    /**
     *
     * @param Request $request
     * @param asset_id
     * @return JsonResponse
     */
    #[Get('%api_network_public_path%/assets/{asset_id}')]
    #[Rest\View]
    public function getPublicAsset(
        Request $request,
        DocumentManager $documentManager,
        AssetManager $assetManager,
        int $asset_id,
    ) {
        $this->logger->info($request->getContent() . ':asset_id:' . $asset_id);

        /** @var Asset $asset */
        $asset = $assetManager->findOneById($asset_id);

        if (empty($asset)) {
            return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
        }

        //if one of the offerings is featured then return it
        /** @var Offering $singleoffering */
        foreach ($asset->getOfferings() as $singleoffering) {
            if ($singleoffering->getIsFeatured() === true) {
                $documentManager->generatePublicCdnUrls([$asset])[0];
                return new JsonResponse([
                    'outcome' => 'success',
                    'data' => ['organization' => $asset->publicView()],
                    'status' => 200,
                ]);
            }
        }
        return new ErrorResponse(ErrorResponse::ERROR_ASSET_NOT_FOUND);
    }

    /**
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[Post('%api_network_public_path%/users', name: 'api_create_new_client')]
    #[Rest\View]
    public function createNewClient(
        Request $request,
        ManagerUserManager $userManager,
        EmailVerifier $emailVerifier,
        UserPasswordHasherInterface $userPasswordHasher,
    ) {
        $this->logger->info('New User Registration');

        try {
            //The request body contains the data we need
            $data = json_decode($request->getContent());

            if (empty($data->url)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_MISSING_VERIFY_URL);
            }

            if (empty($data->email)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_MISSING_EMAIL);
            }

            $em = $this->doctrine->getManager();
            $userRepo = $this->doctrine->getRepository(User::class);
            $emailCheck = $userRepo->findOneBy(['email' => $data->email]);

            if (!empty($emailCheck)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_EMAIL_ALREADY_EXISTS);
            }

            if (empty($data->username)) {
                $data->username = $data->email;
            }

            $userNameCheck = $userRepo->findOneBy(['username' => $data->username]);

            if (!empty($userNameCheck)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_USERNAME_ALREADY_EXISTS);
            }

            if (empty($data->password)) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_MISSING_PASSWORD);
            }

            //validate the password strength
            $passwordStrengthValidator = new PasswordStrengthValidator();
            $valid_resp = $passwordStrengthValidator->validate($data->password);
            if (!empty($valid_resp)) {
                return new ErrorResponse(
                    ErrorResponse::ERROR_USER_PASSWORD_STRENGTH,
                    $valid_resp,
                );
            }

            $this->logger->info('New User: Email=>' . $data->email);

            /** @var User $user */
            $user = new User();

            // Encode(hash) the plain password.
            $encodedPassword = $userPasswordHasher->hashPassword(
                $user,
                $data->password,
            );
            $user->setEmail($data->email);
            $user->setUsername($data->username);
            $user->setPassword($encodedPassword);
            $user->setVisibility(BaseEntity::VISIBILITY_AUTO);
            $user->setEnabled(true);
            $wallet = new Wallet();
            $wallet->setCurrency($this->defaultCcy);
            $user->setWallet($wallet);

            $this->logger->info($user);

            $user = $userManager->buildUser($data, $user);

            //User couldn't be created probably a param issue, send back a generic failure response
            if (empty($user)) {
                return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
            }

            //create a secret key
            $secret = bin2hex(random_bytes(10));

            $user->setConfirmationToken($secret)->setPasswordRequestedAt(
                new \DateTime(),
            );

            $em->persist($user);
            $em->flush();

            //create a secret token to go in the email
            $this->logger->info('User Registered :' . $user->getId());

            $url = $data->url;

            // if (array_key_exists('query', parse_url($url))) {
            //     // it already has some query string
            //     $url .= '&user_id=' . $user->getId() . '&secret=' . $secret;
            // } else {
            //     $url .= '?user_id=' . $user->getId() . '&secret=' . $secret;
            // }

            //$userManager->sendRegistrationMail($user, $url);
            $emailVerifier->sendEmailConfirmation($url, $user);

            //set the ob_step to signed up
            $user->setOBStep(OB_STEP_CONSTANT::STEP1_INT);
            $em->flush();

            $userId = $user->getId();

            if (!empty($userId)) {
                return new SuccessResponse([
                    'user_id' => $userId,
                ]);
            } else {
                return new ErrorResponse(ErrorResponse::ERROR_SYSTEM_ERROR);
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            $this->logger->info($request->getContent());
            return new ErrorResponse(
                ErrorResponse::ERROR_SYSTEM_ERROR,
                $ex->getMessage(),
            );
        }
    }

    /**
     *
     * Forgot Password Action
     *
     *
     * @return JsonResponse
     */
    #[Post('%api_network_public_path%/forgotPassword')]
    public function forgotPasswordAction(
        Request $request,
        ManagerUserManager $userManager,
        ResetPasswordHelperInterface $resetPasswordHelper,
    ) {
        $this->logger->info($request->getContent());

        $url = $request->get('url');
        $email = $request->get('email');

        if (null === $url) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
        if (null === $email) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        $user = $this->doctrine
            ->getRepository(User::class)
            ->findOneBy([
                'email' => $email,
            ]);

        if (!$user) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
        }

        try {
            $resetToken = $resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->error(
                'Problem generating password reset token: ' . $e->getReason(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
        }

        $uri =
            $url
            . '?'
            . http_build_query([
                'token' => $resetToken->getToken(),
            ]);

        $user->setPasswordRequestedAt(new \DateTime());
        $userManager->sendForgotPasswordMail($user, $uri);
        $this->doctrine->getManager()->flush();

        return new SuccessResponse([
            'user_id' => $user->getId(),
        ]);
    }

    /**
     *
     * Forgot Password
     *
     * @param $request
     * @return JsonResponse
     */
    #[Post('%api_network_public_path%/resetPassword')]
    public function resetPassword(
        Request $request,
        ManagerUserManager $userManager,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $userPasswordHasher,
    ) {
        $token = $request->get('token');
        $password = $request->get('password');
        $passwordConfirm = $request->get('password_confirm');

        if (null === $token) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
        if (null === $password) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
        if (null === $passwordConfirm) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        if ($password !== $passwordConfirm) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_PASSWORD_DONT_MATCH);
        }

        try {
            $user = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->error(
                'There was a problem validating reset request ' . $e->getReason(),
            );

            if ($e instanceof ExpiredResetPasswordTokenException) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_TOKEN_EXPIRED);
            }

            if ($e instanceof InvalidResetPasswordTokenException) {
                return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
            }

            return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
        }

        // validate the password strength
        $passwordStrengthValidator = new PasswordStrengthValidator();
        $valid_resp = $passwordStrengthValidator->validate($password);
        if (!empty($valid_resp)) {
            return new ErrorResponse(
                ErrorResponse::ERROR_USER_PASSWORD_STRENGTH,
                $valid_resp,
            );
        }

        // A password reset token should be used only once, remove it.
        $resetPasswordHelper->removeResetRequest($token);

        // Encode(hash) the plain password, and set it.
        $encodedPassword = $userPasswordHasher->hashPassword($user, $password);

        $user->setPassword($encodedPassword);
        $this->doctrine->getManager()->flush();
        $userManager->sendPasswordChangeConfirmationMail($user);

        return new SuccessResponse([
            'user_id' => $user->getId(),
        ]);
    }

    private function isTokenValid(User $user)
    {
        $now = new \DateTime();

        $interval = $now->diff($user->getPasswordRequestedAt(), true);

        if ($interval->d >= 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * Verify Email Action
     *
     *
     */
    #[Get('%api_network_public_path%/verifyEmail', name: 'verify_email')]
    public function verifyEmailAction(Request $request, EmailVerifier $emailVerifier)
    {
        $id = $request->get('id');
        $signedUrl = $request->get('signedUrl');

        if (null === $id) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
        if (null === $signedUrl) {
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }

        $userRepo = $this->doctrine->getRepository(User::class);
        $user = $userRepo->find($id);

        // Ensure the user exists in persistence
        if (null === $user) {
            return new ErrorResponse(ErrorResponse::ERROR_USER_NOT_FOUND);
        }

        try {
            $emailVerifier->handleEmailConfirmation($signedUrl, $user);

            // $this->logger->info("User #{$user->getId()} email verification processed");
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->logger->error(
                "User #{$user->getId()} email verification error: "
                    . $exception->getReason(),
            );
            return new ErrorResponse(ErrorResponse::ERROR_USER_UPDATE_FAILED);
        }

        return new SuccessResponse([
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse|ErrorResponse
     */
    #[GET('%api_network_public_path%/checkEmailAddress', name: 'check_email_address')]
    #[Rest\View]
    public function checkEmailAddress(Request $request)
    {
        $this->logger->info('========= IN checkEmailAddress ========= ');

        $key = $this->apiEmailCheck;
        $secret = hash('sha256', $key);

        $header = $request->headers->all();
        $data = json_decode($request->getContent());

        if (isset($header['auth'][0]) && isset($data->email)) {
            $secretReceived = $header['auth'][0];
            if ($secret === $secretReceived) {
                $foundEmail = $this->doctrine
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $data->email]);

                if (is_null($foundEmail)) {
                    $this->logger->info('Email Address not found');
                    return new JsonResponse([
                        'outcome' => 'success',
                        'status' => 200,
                    ]);
                } else {
                    $this->logger->error('Email Address already exists');
                    return new ErrorResponse(ErrorResponse::ERROR_USER_EMAIL_ALREADY_EXISTS);
                }
            }
        } else {
            $this->logger->error('Missing parameters');
            return new ErrorResponse(ErrorResponse::ERROR_INSUFFICIENT_PARAMS);
        }
    }
}
