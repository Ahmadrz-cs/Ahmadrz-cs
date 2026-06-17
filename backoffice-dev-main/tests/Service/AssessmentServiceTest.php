<?php

namespace App\Tests\Service;

use App\Entity\AssessmentResponse;
use App\Entity\Enum\QuestionType;
use App\Entity\Question;
use App\Entity\QuestionChoice;
use App\Entity\UserAssessment;
use App\Service\AssessmentService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use ValueError;

final class AssessmentServiceTest extends KernelTestCase
{
    private AssessmentService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(AssessmentService::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('assessmentQuestionTypeProvider')]
    public function testIsAssessmentQuestionTypeValid(
        bool $expected,
        UserAssessment $assessment,
    ): void {
        $actual = $this->service->isAssessmentQuestionTypeValid($assessment);
        $this->assertSame($expected, $actual);
    }

    public static function assessmentQuestionTypeProvider(): \Generator
    {
        $qApp1 = new Question(QuestionType::Appropriateness);
        $qApp1Choice = new QuestionChoice();
        $qApp1Choice->setQuestion($qApp1);
        $qApp1Res = new AssessmentResponse($qApp1, $qApp1Choice);

        $qApp2 = new Question(QuestionType::Appropriateness);
        $qApp2Choice = new QuestionChoice();
        $qApp2Choice->setQuestion($qApp1);
        $qApp2Res = new AssessmentResponse($qApp2, $qApp2Choice);

        $qAml1 = new Question(QuestionType::Aml);
        $qAml1Choice = new QuestionChoice();
        $qAml1Choice->setQuestion($qApp1);
        $qAml1Res = new AssessmentResponse($qAml1, $qAml1Choice);

        $norestriction = new UserAssessment();
        $norestriction->addResponse($qApp1Res);
        $norestriction->addResponse($qApp2Res);
        $norestriction->addResponse($qAml1Res);

        $restrictedAppropriate = new UserAssessment(QuestionType::Appropriateness);
        $restrictedAppropriate->addResponse($qApp1Res);
        $restrictedAppropriate->addResponse($qApp2Res);

        $restrictedAppropriateMix = new UserAssessment(QuestionType::Appropriateness);
        $restrictedAppropriateMix->addResponse($qApp1Res);
        $restrictedAppropriateMix->addResponse($qApp2Res);
        $restrictedAppropriateMix->addResponse($qAml1Res);

        yield 'Empty assessment' => [true, new UserAssessment()];
        yield 'No question type specified' => [true, $norestriction];
        yield 'Appropriate restricted only' => [true, $restrictedAppropriate];
        yield 'Appropriate restricted mix' => [false, $restrictedAppropriateMix];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'assessmentQuestionTypeFilterProvider',
    )]
    public function testFilterAssessmentInvalidQuestionType(
        array $expectedTypes,
        int $expectedCount,
        UserAssessment $assessment,
    ): void {
        $actual = $this->service->filterAssessmentInvalidQuestionType($assessment);
        foreach ($actual->getResponses() as $response) {
            $this->assertContains(
                $response->getQuestion()->getQuestionType(),
                $expectedTypes,
            );
        }
        $this->assertCount($expectedCount, $actual->getResponses());
    }

    public static function assessmentQuestionTypeFilterProvider(): \Generator
    {
        $qApp1 = new Question();
        $qApp1->setQuestionType(QuestionType::Appropriateness);
        $qApp1Choice = new QuestionChoice();
        $qApp1Choice->setQuestion($qApp1);
        $qApp1Res = new AssessmentResponse($qApp1, $qApp1Choice);

        $qApp2 = new Question();
        $qApp2->setQuestionType(QuestionType::Appropriateness);
        $qApp2Choice = new QuestionChoice();
        $qApp2Choice->setQuestion($qApp1);
        $qApp2Res = new AssessmentResponse($qApp2, $qApp2Choice);

        $qAml1 = new Question();
        $qAml1->setQuestionType(QuestionType::Aml);
        $qAml1Choice = new QuestionChoice();
        $qAml1Choice->setQuestion($qApp1);
        $qAml1Res = new AssessmentResponse($qAml1, $qAml1Choice);

        $norestriction = new UserAssessment();
        $norestriction->addResponse($qApp1Res);
        $norestriction->addResponse($qApp2Res);
        $norestriction->addResponse($qAml1Res);

        $restrictedAppropriate = new UserAssessment(QuestionType::Appropriateness);
        $restrictedAppropriate->addResponse($qApp1Res);
        $restrictedAppropriate->addResponse($qApp2Res);

        $restrictedAppropriateMix = new UserAssessment(QuestionType::Appropriateness);
        $restrictedAppropriateMix->addResponse($qApp1Res);
        $restrictedAppropriateMix->addResponse($qApp2Res);
        $restrictedAppropriateMix->addResponse($qAml1Res);

        yield 'Empty assessment' => [[], 0, new UserAssessment()];
        yield 'No question type specified' => [
            [QuestionType::Appropriateness, QuestionType::Aml],
            3,
            $norestriction,
        ];
        yield 'Appropriate restricted only' => [
            [QuestionType::Appropriateness],
            2,
            $restrictedAppropriate,
        ];
        yield 'Appropriate restricted mix' => [
            [QuestionType::Appropriateness],
            2,
            $restrictedAppropriateMix,
        ];
    }
}
