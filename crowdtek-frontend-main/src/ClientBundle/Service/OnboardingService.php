<?php

namespace ClientBundle\Service;

use AppBundle\Entity\AssessmentResponse;
use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\OnboardingProfile;
use AppBundle\Entity\Question;
use AppBundle\Entity\QuestionChoice;
use AppBundle\Entity\UserAssessment;
use AppBundle\Entity\UserCategorisation;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class OnboardingService
{
    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function needsCheckup(array $userInfo): bool
    {
        // Only users who have finished onboarding need checkups
        if (
            ($userInfo['has_been_approved']
                && $userInfo['registration_complete'])
            || $userInfo['ob_step'] == 5
        ) {
            if (array_key_exists('onboarding_profile', $userInfo)) {
                $obp = $userInfo['onboarding_profile'];
            } else {
                $obp = [];
            }
            $obp = $this->denormalizer->denormalize(
                $obp,
                OnboardingProfile::class,
            );
            // $this->logger->debug("Next step", [$this->getNextStep($obp)]);
            return $this->getNextStep($obp) != 'checkup_completion';
        }
        return false;
    }

    /**
     * Takes an onboarding profile and returns the route of the next step
     */
    public function getNextStep(OnboardingProfile $onboardingProfile): string
    {
        // $this->logger->debug("onboarding profile object", [$onboardingProfile]);
        if (empty($onboardingProfile->categoryReviewedAt)) {
            $yearsSinceCategoryReview = 1;
        } else {
            $lastReview = $onboardingProfile->categoryReviewedAt;
            $interval = $lastReview->diff(new \DateTime());
            $yearsSinceCategoryReview = $interval->y;
            $this->logger->debug("Years since last review ", [$yearsSinceCategoryReview]);
        }
        if (
            !in_array($onboardingProfile->category, [
                UserCategory::Restricted,
                UserCategory::Sophisticated,
                UserCategory::HighNetWorth,
            ]) || $yearsSinceCategoryReview >= 1
        ) {
            return 'checkup_categorisation';
        }
        if (!$onboardingProfile->assessmentPassed) {
            if ($this->canTakeAssessment($onboardingProfile)) {
                return 'checkup_assessment';
            } else {
                // Not allowed to retake yet
                return 'checkup_assessment_fail';
            }
        }
        if (!$onboardingProfile->cooloffAccepted || !$onboardingProfile->riskWarningAccepted) {
            return 'checkup_risk';
        }
        return 'checkup_completion';
    }

    public function canTakeAssessment(OnboardingProfile $onboardingProfile): bool
    {
        if ($onboardingProfile->assessmentPassed) {
            // if you've already passed, you can't retake the assessment
            return false;
        }
        if ($onboardingProfile->assessmentAttempts < 2) {
            // If fewer than 2 attempt so far, can instantly retake
            return true;
        }
        // Any more attempts, then a cooldown of a week is in place
        $lastAttempt = $onboardingProfile->assessmentAttemptedAt;
        if ($lastAttempt) {
            $interval = $lastAttempt->diff(new \DateTime());
            $daysSinceLastAttempt = $interval->d;
            if ($daysSinceLastAttempt < 7) {
                return false;
            }
        }
        // If no previous attempt logged, then can retake immediately
        // Or last attempt was over a week ago
        return true;
    }

    public function getOnboardingProfileFromSession(): OnboardingProfile
    {
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        $obpArray = [];
        if (!is_null($userInfo) && array_key_exists('onboarding_profile', $userInfo)) {
            $obpArray = $userInfo['onboarding_profile'];
        }
        return $this->denormalizer->denormalize(
            $obpArray,
            OnboardingProfile::class,
        );
    }

    public function isAllowedToInvest(OnboardingProfile $onboardingProfile): bool
    {
        if (empty($onboardingProfile->categoryReviewedAt)) {
            $yearsSinceCategoryReview = 1;
        } else {
            $lastReview = $onboardingProfile->categoryReviewedAt;
            $interval = $lastReview->diff(new \DateTime());
            $yearsSinceCategoryReview = $interval->y;
            // $this->logger->debug("Years since last review ", [$yearsSinceCategoryReview]);
        }
        return $onboardingProfile->cooloffAccepted === true
            && $onboardingProfile->riskWarningAccepted === true
            && $onboardingProfile->assessmentPassed === true
            && $yearsSinceCategoryReview < 1
            && in_array($onboardingProfile->category, [
                UserCategory::Restricted,
                UserCategory::Sophisticated,
                UserCategory::HighNetWorth,
            ]);
    }

    public function updateOnboardingProfile(OnboardingProfile $onboardingProfile): array
    {
        $requestBody = $this->normalizer->normalize(
            $onboardingProfile,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );
        $this->logger->debug("Update onboarding profile with ", $requestBody);
        $response = $this->client->onboardingProfile()->update([
            'json' => $requestBody
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to load questionnaire');
        }
        return $this->client->getContent($response);
    }

    public function addUserCategorisation(UserCategorisation $userCategorisation): array
    {
        $requestBody = $this->normalizer->normalize(
            $userCategorisation,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );
        $this->logger->debug("Adding user categorisation ", $requestBody);
        $response = $this->client->userCategorisation()->create([
            'json' => $requestBody
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to load questionnaire');
        }
        return $this->client->getContent($response);
    }

    public function addUserAssessment(UserAssessment $userAssessment): array
    {
        // Save the user assessment that is being submitted as a record of the most recent attempt
        $this->requestStack->getSession()->set('userAssessment', $userAssessment);
        $requestBody = $this->normalizer->normalize(
            $userAssessment,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );
        $this->logger->debug("Adding user assessment ", $requestBody);
        $response = $this->client->userAssessment()->create([
            'json' => $requestBody
        ]);
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to load questionnaire');
        }
        return $this->client->getContent($response);
    }

    public function getQuestionnaire(): array
    {
        $this->logger->debug("Getting new questionnaire");
        $response = $this->client->generatedAssessment()->retrieve();
        if (200 !== $response->getStatusCode()) {
            throw new NotFoundHttpException('Unable to load questionnaire');
        }
        return $this->client->getContent($response);
    }

    public function prepareQuestionnaire(array $apiQuestionnaire): UserAssessment
    {
        // API returns something like
        // [
        //     {
        //         "id": 1,
        //         "questionType": "appropriateness",
        //         "section": 1,
        //         "content": "Est similique culpa dolorem dolor perspiciatis sit nostrum.",
        //         "active": true,
        //         "locked": false,
        //         "choices": [
        //             {
        //                 "id": 1,
        //                 "question": 1,
        //                 "content": "Eum et nihil ut.",
        //                 "active": true,
        //                 "correct": true
        //             }
        //         ]
        //     }
        // ]
        // Need to denormalize into our object structure to work better with Symfony forms

        $userAssessment = new UserAssessment();
        foreach ($apiQuestionnaire as $apiQuestion) {
            $qcs = [];
            foreach ($apiQuestion['choices'] as $apiChoice) {
                $qc = new QuestionChoice(
                    id: $apiChoice['id'],
                    content: $apiChoice['content'],
                    correct: $apiChoice['correct'],
                );
                $qcs[] = $qc;
                // $this->logger->debug("Choice", [$qc->id, $qc->content, $qc->correct]);
            }
            // Sort choices to they are always in order of ID
            usort($qcs, function ($x, $y) {
                return $x->id <=> $y->id;
            });
            $q = new Question(
                id: $apiQuestion['id'],
                content: $apiQuestion['content'],
                choices: $qcs,
            );
            $assessmentReponse = new AssessmentResponse();
            $assessmentReponse->question = $q;
            $userAssessment->responses[] = $assessmentReponse;
        }
        return $userAssessment;
    }

    public function getCurrentUserAssessment(): UserAssessment
    {
        $userAssessment = $this->requestStack->getSession()->get('userAssessment');
        // Reuse an existing user assessment if it hasn't already been completed
        if (!is_null($userAssessment) && !$userAssessment->complete) {
            return $userAssessment;
        }
        // Otherwise, prepare a new user assessment and save to session
        $questionnaire = $this->getQuestionnaire();
        $userAssessment = $this->prepareQuestionnaire($questionnaire);
        $this->requestStack->getSession()->set('userAssessment', $userAssessment);
        return $userAssessment;
    }
}
