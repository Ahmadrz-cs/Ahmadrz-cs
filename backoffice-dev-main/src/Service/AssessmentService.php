<?php

namespace App\Service;

use App\Entity\Enum\QuestionArea;
use App\Entity\Enum\QuestionType;
use App\Entity\Question;
use App\Entity\UserAssessment;
use App\Repository\QuestionRepository;
use Psr\Log\LoggerInterface;

class AssessmentService
{
    // https://handbook.fca.org.uk/handbook/cobs10/cobs10s8
    public const ASSESSMENT_AREAS = [
        QuestionArea::ContractualNature,
        QuestionArea::FinancialLoss,
        QuestionArea::IssuerFailureLoss,
        QuestionArea::RegulatedActivity,
        QuestionArea::FscsProtection,
        QuestionArea::Illiquidity,
        QuestionArea::IssuerFailureAdmin,
        QuestionArea::IssuerRole,
        QuestionArea::Diversification,
        QuestionArea::ShareDividend,
        QuestionArea::ShareDilution,
        QuestionArea::ShareRights,
        QuestionArea::FirmContractualRelation,
        QuestionArea::FirmRole,
        QuestionArea::FirmFailureAdmin,
    ];

    public function __construct(
        private LoggerInterface $logger,
        private QuestionRepository $questionRepository,
    ) {}

    public function markAssessment(
        UserAssessment $assessment,
        int $wrongAnswersAllowed = 0,
    ): UserAssessment {
        if (!$assessment->isComplete()) {
            return $assessment;
        }
        $wrongAnswers = 0;
        $assessment->setPassed(true);
        foreach ($assessment->getResponses() as $response) {
            if (!$response->getChoice()->isCorrect()) {
                $wrongAnswers += 1;
            }
            if ($wrongAnswers > $wrongAnswersAllowed) {
                $assessment->setPassed(false);
                break;
            }
        }
        $assessment->getProfile()->setAssessmentPassed($assessment->isPassed());
        return $assessment;
    }

    /**
     * Creates a list of questions that can be use for an appropriateness assessment. This is a deterministic (not randomised) generator.
     *
     * Set corresponds to the Nth question in a given QuestionArea. If that QuestionArea has run out of questions for the desired set, the last one will be used.
     *
     * Setting a non-zero length will ALWAYS shuffle the questions regardless of whether you set alwaysShuffle or not
     * @return array<int, Question>
     */
    public function generateAssessment(
        int $set = 1,
        int $length = 0,
        bool $alwaysShuffle = false,
    ): array {
        $questions = [];
        // Get rid of any question areas that are empty
        $questionsByArea = array_filter(
            $this->findAndgroupQuestionsByArea(),
            fn(array $questionsInArea) => !empty($questionsInArea),
        );
        $areas = array_keys($questionsByArea);
        if ($alwaysShuffle) {
            shuffle($areas);
        }
        /**
         * If non-zero length is given:
         * - Always shuffle the possible assessment areas irrespective of whether shuffle
         * is enabled (to avoid leaving some QuestionAreas never appearing)
         * - Then truncate the areas assessed
         */
        if ($length > 0) {
            // Don't need to shuffle twice
            if (!$alwaysShuffle) {
                shuffle($areas);
            }
            $areas = array_slice($areas, 0, $length);

            // if (!$alwaysShuffle) {
            //     sort($areas);
            // }
        }
        // Loop through the areas and get a relevant question
        foreach ($areas as $area) {
            $index = min($set, count($questionsByArea[$area]));
            // $this->logger->debug("Using set {$index}");
            if ($index > 0) {
                $questions[] = $questionsByArea[$area][$index - 1];
            }
        }
        return $questions;
    }

    /**
     * Key of returned array is the value of the QuestionArea enum
     * @return array<int, array<int, Question>>
     */
    public function findAndgroupQuestionsByArea(?bool $activeOnly = true): array
    {
        // Cannot use enums as array keys, so we'll use the value instead
        $areas = array_map(fn(QuestionArea $qa) => $qa->value, self::ASSESSMENT_AREAS);
        $questionsByArea = array_fill_keys($areas, []);
        $criteria = [
            'questionType' => QuestionType::Appropriateness,
            'section' => $areas,
        ];
        if (!is_null($activeOnly)) {
            $criteria['active'] = $activeOnly;
        }
        // $allActiveQuestions = $this->questionRepository->findBy($criteria);
        // Pre-join QuestionChoice and AsessmentResponse tables to reduce DB queries
        $allActiveQuestions = $this->questionRepository->findAllQuestionsByArea(
            QuestionType::Appropriateness,
            $areas,
            $activeOnly,
        );
        // Group questions in their sections
        foreach ($allActiveQuestions as $q) {
            if (in_array($q->getSection()->value, $areas)) {
                $questionsByArea[$q->getSection()->value][] = $q;
            }
        }
        return $questionsByArea;
    }

    public function getQuestionChoiceCounts(Question $question): array
    {
        $choiceCounts = [];
        foreach ($question->getChoices() as $choice) {
            $choiceCounts[$choice->getId()] = 0;
        }
        foreach ($question->getResponses() as $response) {
            $choiceCounts[$response->getChoice()->getId()] += 1;
        }
        return $choiceCounts;
    }

    public function getQuestionAreaMap(): array
    {
        $keys = array_map(fn(QuestionArea $qa) => $qa->value, self::ASSESSMENT_AREAS);
        return array_combine($keys, self::ASSESSMENT_AREAS);
    }

    public function getNextQuestion(UserAssessment $assessment): ?Question
    {
        // Find question areas that have not been answered yet
        $areas = self::ASSESSMENT_AREAS;
        foreach ($assessment->getResponses() as $response) {
            $index = array_search($response->getQuestion()->getSection(), $areas);
            unset($areas[$index]);
        }
        // Find a question from any of the unanswered areas
        return $this->questionRepository->findOneBy([
            'questionType' => QuestionType::Appropriateness,
            'section' => $areas,
            'active' => true,
        ]);
    }

    public function findQuestionFromArea(QuestionArea $area): ?Question
    {
        return $this->questionRepository->findOneBy([
            'questionType' => QuestionType::Appropriateness,
            'section' => $area,
            'active' => true,
        ]);
    }

    public function isAssessmentQuestionTypeValid(UserAssessment $assessment): bool
    {
        if ($assessment->getQuestionType() === null) {
            return true;
        }
        foreach ($assessment->getResponses() as $response) {
            if (
                $response->getQuestion()->getQuestionType() != $assessment->getQuestionType()
            ) {
                return false;
            }
        }
        return true;
    }

    public function filterAssessmentInvalidQuestionType(UserAssessment $assessment): UserAssessment
    {
        if ($assessment->getQuestionType() === null) {
            return $assessment;
        }
        foreach ($assessment->getResponses() as $response) {
            if (
                $response->getQuestion()->getQuestionType() != $assessment->getQuestionType()
            ) {
                $assessment->removeResponse($response);
            }
        }
        return $assessment;
    }
}
