<?php

namespace App\Controller\ApiV1;

use App\Entity\UserAssessment;
use App\Repository\UserRepository;
use App\Service\ApiV1OnboardingService;
use App\Service\AssessmentService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Patch;
use FOS\RestBundle\Controller\Annotations\Post;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIv1 controller for PS22/10 related onboarding flow
 *
 * - Creating [POST] UserCategorisation
 * - Creating [POST] UserAssessment
 * - Creating [POST] AssessmentResponse
 *
 * - NOT IMPLEMENTED - Updating [PATCH/PUT] UserAssessment - to allow updates to trigger grading
 *
 * - NOT IMPLEMENTED - Getting [GET] UserAssessment (most recent) - to allow resumption
 * - Getting [GET] Questions - for assessment - comes with possible choices
 *   - This will be a super-coupled API for the assessment to keep it focused for now
 */
class SelfOnboardingController extends AbstractFOSRestController
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {}

    #[Get(
        '/%api_network_path%/self/onboarding/profile',
        name: 'api_get_onboarding_profile',
    )]
    public function getOnboardingProfile(
        Request $request,
        ApiV1OnboardingService $apiV1OnboardingService,
    ) {
        /**
         * @var \App\Entity\User
         */
        $user = $this->getUser();
        return new JsonResponse($user->getOnboardingProfile());
    }

    #[Patch(
        '/%api_network_path%/self/onboarding/profile',
        name: 'api_patch_onboarding_profile',
    )]
    public function patchOnboardingProfile(
        Request $request,
        ApiV1OnboardingService $apiV1OnboardingService,
    ) {
        /**
         * @var \App\Entity\User
         */
        $user = $this->getUser();
        $requestBody = json_decode($request->getContent(), true);
        $onboardingProfile = $apiV1OnboardingService->updateOnboardingProfile(
            $user,
            $requestBody,
        );
        $this->entityManager->persist($onboardingProfile);
        $this->entityManager->flush();

        $this->logger->debug('Update onboarding profile for user', [
            $user->getId(),
            $requestBody,
        ]);
        // $this->logger->debug("User assessment", [$userAssessment]);

        // return new Response(null, Response::HTTP_OK);
        return new JsonResponse($user->getOnboardingProfile());
    }

    #[Rest\View]
    #[Post(
        '/%api_network_path%/self/onboarding/assessment',
        name: 'api_post_onboarding_assessment',
    )]
    public function postUserAssessment(
        Request $request,
        ApiV1OnboardingService $apiV1OnboardingService,
    ) {
        /**
         * @var \App\Entity\User
         */
        $user = $this->getUser();
        $requestBody = json_decode($request->getContent(), true);
        $userAssessment = $apiV1OnboardingService->processAllInOneAssessment(
            $user,
            $requestBody,
        );
        $this->entityManager->persist($user->getOnboardingProfile());
        $this->entityManager->persist($userAssessment);
        $this->entityManager->flush();

        $this->logger->debug('User assessment for user', [
            $user->getId(),
            $requestBody,
        ]);
        // $this->logger->debug("User assessment", [$userAssessment]);

        // return new Response(null, Response::HTTP_NO_CONTENT);
        return new JsonResponse($userAssessment, Response::HTTP_CREATED);
    }

    #[Rest\View]
    #[Post(
        '/%api_network_path%/self/onboarding/categorisation',
        name: 'api_post_onboarding_categorisation',
    )]
    public function postUserCategorisation(
        Request $request,
        ApiV1OnboardingService $apiV1OnboardingService,
    ) {
        /**
         * @var \App\Entity\User
         */
        $user = $this->getUser();
        $requestBody = json_decode($request->getContent(), true);
        $userCategorisation = $apiV1OnboardingService->processAllInOneCategorisation(
            $user,
            $requestBody,
        );
        $apiV1OnboardingService->setLegacyCategorisation($user, $userCategorisation);
        $this->entityManager->persist($user->getOnboardingProfile());
        $this->entityManager->persist($userCategorisation);
        $this->entityManager->flush();

        $this->logger->debug('User categorisation for user', [
            $user->getId(),
            $requestBody,
        ]);
        // $this->logger->debug("User assessment", [$userAssessment]);

        // return new Response(null, Response::HTTP_NO_CONTENT);
        return new JsonResponse($userCategorisation, Response::HTTP_CREATED);
    }

    #[Rest\View]
    #[Get(
        '/%api_network_path%/self/onboarding/generated-assessment',
        name: 'api_get_onboarding_generate_assessment',
    )]
    public function getGeneratedAssessment(AssessmentService $assessmentService)
    {
        /**
         * @var \App\Entity\User
         */
        $user = $this->getUser();
        // $this->logger->debug('Generating assessment for user', [$user->getUserIdentifier()]);
        $completedAttempts = $user
            ->getOnboardingProfile()
            ->getAssessments()
            ->filter(fn(UserAssessment $a) => $a?->isComplete())
            ->count();
        $questions = $assessmentService->generateAssessment(max(
            1,
            $completedAttempts + 1,
        ));
        // $this->logger->debug('Questionnaire length', [count($questions)]);

        return new JsonResponse($questions);
    }
}
