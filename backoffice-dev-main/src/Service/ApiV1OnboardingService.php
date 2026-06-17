<?php

namespace App\Service;

use App\Entity\AssessmentResponse;
use App\Entity\Enum\QuestionType;
use App\Entity\Enum\UserCategory;
use App\Entity\Investor;
use App\Entity\OnboardingProfile;
use App\Entity\User;
use App\Entity\UserAssessment;
use App\Entity\UserCategorisation;
use App\Repository\QuestionChoiceRepository;
use App\Repository\QuestionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Service for PS22/10 onboarding implemented in src/Controller/ApiV1/SelfOnboardingController.php
 *
 * This provides an APIv1 style interface where you pass raw request bodies to methods in this service
 *
 * It is intended as a stopgap to get an MVP working rather than faffing around with serializers and DTOs
 */
class ApiV1OnboardingService
{
    public function __construct(
        private LoggerInterface $logger,
        private QuestionRepository $questionRepository,
        private QuestionChoiceRepository $questionChoiceRepository,
        private AssessmentService $assessmentService,
    ) {}

    /**
     * Realistically, this should only be used for updating
     * - cooloffAccepted
     * - riskWarningAccepted
     *
     * Other onboarding profile fields should be handled by other means.
     * Although, this method does support overriding everything
     */
    public function updateOnboardingProfile(
        User $user,
        array $requestBody,
    ): OnboardingProfile {
        $obp = $user->getOnboardingProfile();

        if (array_key_exists('cooloffAccepted', $requestBody)) {
            $obp->setCooloffAccepted($requestBody['cooloffAccepted']);
        }
        if (array_key_exists('riskWarningAccepted', $requestBody)) {
            $obp->setRiskWarningAccepted($requestBody['riskWarningAccepted']);
        }
        if (array_key_exists('assessmentPassed', $requestBody)) {
            $obp->setAssessmentPassed($requestBody['assessmentPassed']);
        }
        if (array_key_exists('category', $requestBody)) {
            $obp->setCategory(UserCategory::from($requestBody['category']));
        }
        if (array_key_exists('cooloffEnd', $requestBody)) {
            $obp->setCooloffEnd(new \DateTime($requestBody['cooloffEnd']));
        }
        if (array_key_exists('categoryReviewedAt', $requestBody)) {
            $obp->setCategoryReviewedAt(
                new \DateTime($requestBody['categoryReviewedAt']),
            );
        }
        return $obp;
    }

    /**
     * This is for processing requests that create a UserAssessment with all relevant responses
     * and the complete toggle is enabled
     *
     * If the assessment is complete, mark it and update the onboarding profile as needed
     */
    public function processAllInOneAssessment(
        User $user,
        array $requestBody,
    ): UserAssessment {
        $userAssessment = new UserAssessment(QuestionType::Appropriateness);
        $user->getOnboardingProfile()->addAssessment($userAssessment);
        if (array_key_exists('notes', $requestBody)) {
            $userAssessment->setNotes((string) $requestBody['notes']);
        }
        if (
            array_key_exists('expiry', $requestBody) && !is_null($requestBody['expiry'])
        ) {
            $userAssessment->setExpiry(new \DateTime($requestBody['expiry']));
        }
        foreach ($requestBody['responses'] as $r) {
            if (array_key_exists('question', $r) && array_key_exists('choice', $r)) {
                $question = $this->questionRepository->find($r['question']);
                $choice = $this->questionChoiceRepository->find($r['choice']);
                // $this->logger->debug('question', [$question->getId()]);
                // $this->logger->debug('choice', [$choice->getId()]);
                if ($question && $choice) {
                    $assessmentReponse = new AssessmentResponse();
                    $assessmentReponse->setQuestion($question);
                    $assessmentReponse->setChoice($choice);
                    $userAssessment->addResponse($assessmentReponse);
                }
            }
        }
        if (array_key_exists('complete', $requestBody)) {
            $userAssessment->setComplete((bool) $requestBody['complete']);
            if ($userAssessment->isComplete()) {
                $this->assessmentService->markAssessment($userAssessment);
            }
        }
        return $userAssessment;
    }

    /**
     * This is for processing requests that create a UserCategorisation and updates the onboarding profile
     */
    public function processAllInOneCategorisation(
        User $user,
        array $requestBody,
    ): UserCategorisation {
        // $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();

        $userCategorisation = new UserCategorisation();
        $user->getOnboardingProfile()->addCategorisation($userCategorisation);

        $userCategorisation->setCategory(UserCategory::from($propertyAccessor->getValue(
            $requestBody,
            '[category]',
        )));
        $userCategorisation->setDetails($propertyAccessor->getValue(
            $requestBody,
            '[details]',
        ));

        $user->getOnboardingProfile()->setCategory($userCategorisation->getCategory());
        $user->getOnboardingProfile()->setCategoryReviewedAt(new \DateTime());
        return $userCategorisation;
    }

    public function setLegacyCategorisation(
        User $user,
        UserCategorisation $userCategorisation,
    ): Investor {
        $investorProfile = $user->getInvestor();
        switch ($userCategorisation->getCategory()) {
            case UserCategory::Restricted:
                $investorProfile->setCxbRestrictedUser(true);
                $investorProfile->setCxbSophisticatedInvestor(false);
                $investorProfile->setCxbWorthInvestor(false);
                break;
            case UserCategory::Sophisticated:
                $investorProfile->setCxbRestrictedUser(false);
                $investorProfile->setCxbSophisticatedInvestor(true);
                $investorProfile->setCxbWorthInvestor(false);
                break;
            case UserCategory::HighNetWorth:
                $investorProfile->setCxbRestrictedUser(false);
                $investorProfile->setCxbSophisticatedInvestor(false);
                $investorProfile->setCxbWorthInvestor(true);
                break;

            default:
                // Do nothing by default
                break;
        }
        return $investorProfile;
    }
}
