<?php

namespace App\Tests\Service;

use App\Entity\Enum\UserCategory;
use App\Entity\Investor;
use App\Entity\OnboardingProfile;
use App\Entity\Question;
use App\Entity\QuestionChoice;
use App\Entity\User;
use App\Entity\UserCategorisation;
use App\Repository\QuestionChoiceRepository;
use App\Repository\QuestionRepository;
use App\Service\ApiV1OnboardingService;
use App\Service\AssessmentService;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use ValueError;

final class ApiV1OnboardingServiceTest extends KernelTestCase
{
    private ApiV1OnboardingService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(ApiV1OnboardingService::class);
    }

    public static function updateOnboardingProfileProvider(): \Generator
    {
        $obp = new OnboardingProfile();
        // Set the cooloff end initially, otherwise it'll set one at runtime
        // Which we don't want for tests
        $obp->setCooloffEnd(new \DateTime('2022-08-15'));
        $obp->setCooloffAccepted(false);
        yield 'single field overwrite' => [
            $obp,
            ['cooloffEnd' => '2024-08-01'],
            [
                'cooloffEnd' => '2024-08-01',
                'cooloffAccepted' => false,
                'riskWarningAccepted' => null,
                'category' => null,
                'categoryReviewedAt' => null,
                'assessmentPassed' => null,
            ],
        ];
        yield 'all fields overwrite' => [
            $obp,
            [
                'cooloffEnd' => '2024-06-24',
                'cooloffAccepted' => null, // should be possible to set nullable fields back to null
                'riskWarningAccepted' => true,
                'category' => 'hnw',
                'categoryReviewedAt' => '2024-06-30',
                'assessmentPassed' => true,
            ],
            [
                'cooloffEnd' => '2024-06-24',
                'cooloffAccepted' => null,
                'riskWarningAccepted' => true,
                'category' => 'hnw',
                'categoryReviewedAt' => '2024-06-30',
                'assessmentPassed' => true,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('updateOnboardingProfileProvider')]
    public function testUpdateOnboardingProfile(
        OnboardingProfile $obp,
        array $requestBody,
        array $expected,
    ): void {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $user = new User();
        $user->setOnboardingProfile($obp);

        $actual = $this->service->updateOnboardingProfile($user, $requestBody);
        foreach ($expected as $key => $expectedValue) {
            $actualValue = $propertyAccessor->getValue($actual, $key);
            if ($actualValue instanceof \DateTimeInterface) {
                $actualValue = $actualValue->format('Y-m-d');
            }
            if ($actualValue instanceof UserCategory) {
                $actualValue = $actualValue->value;
            }
            $this->assertEquals($expectedValue, $actualValue);
        }
    }

    public static function defaultAssessmentPassedProvider(): \Generator
    {
        // The PS22/10 cutoff is 2023-02-01
        $userPreCutoff = new User();
        $userPreCutoff->setCreatedAt(new \DateTime('2023-01-31'));
        $userPostCutoff = new User();
        $userPostCutoff->setCreatedAt(new \DateTime('2023-02-01'));
        $userNoCreatedAt = new User();
        yield 'Pre cutoff' => [$userPreCutoff, [], true];
        yield 'Post cutoff' => [$userPostCutoff, [], null];
        yield 'No createdAt' => [$userNoCreatedAt, [], null];
        yield 'Overwrite' => [$userPreCutoff, ['assessmentPassed' => false], false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('defaultAssessmentPassedProvider')]
    public function testDefaultAssessmentPassed(
        User $user,
        array $requestBody,
        ?bool $expected,
    ): void {
        /**
         * This is a proxy test as there is no entity test for OnboardingProfile
         * Check the behaviour of the assessmentPassed field when user was created as a certain date
         */
        $actual = $this->service->updateOnboardingProfile($user, $requestBody);
        $this->assertSame($expected, $actual->isAssessmentPassed());
    }

    public static function invalidOnboardingProfileProvider(): \Generator
    {
        /**
         * ValueError::class is from converting to a UserCategory enum with Enum::from()
         * Invalid datetime strings result in a generic Exception
         */
        yield 'Unknown enum' => [ValueError::class, ['category' => 'abc']];
        yield 'Invalid datetime string' => [
            \Exception::class,
            ['categoryReviewedAt' => 'abc'],
        ];
        yield 'Category cannot be null if set' => [
            ValueError::class,
            ['category' => null],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidOnboardingProfileProvider')]
    public function testUpdateOnboardingProfileInvalidValues(
        string $exceptionClassString,
        $input,
    ): void {
        $this->expectException($exceptionClassString);
        $this->service->updateOnboardingProfile(new User(), $input);
    }

    public static function processAllInOneAssessmentProvider(): \Generator
    {
        yield 'Only responses' => [['responses' => []], false, false];
        yield 'Passed' => [['responses' => [], 'complete' => 1], true, true];
        yield 'Fail' => [['responses' => [], 'complete' => 1], true, false];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('processAllInOneAssessmentProvider')]
    public function testProcessAllInOneAssessment(
        array $input,
        bool $isComplete,
        bool $toPass,
    ): void {
        $questionRepoMock = $this->createMock(QuestionRepository::class);
        $choiceRepoMock = $this->createMock(QuestionChoiceRepository::class);
        $expected = [
            14 => 67,
            44 => 155,
            25 => 87,
            4 => 434,
        ];
        $questions = [];
        $correctChoices = [];
        foreach ($expected as $key => $value) {
            $input['responses'][] = [
                'question' => $key,
                'choice' => $value,
            ];
            $q = EntityIdTestUtil::setEntityId(new Question(), $key);
            $qc = EntityIdTestUtil::setEntityId(new QuestionChoice(), $value);
            $qc->setCorrect($toPass);
            $questions[] = $q;
            $correctChoices[] = $qc;
        }
        $questionRepoMock
            ->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(...$questions);
        $choiceRepoMock
            ->expects(self::exactly(4))
            ->method('find')
            ->willReturnOnConsecutiveCalls(...$correctChoices);
        $service = new ApiV1OnboardingService(
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
            $questionRepoMock,
            $choiceRepoMock,
            static::getContainer()->get(AssessmentService::class),
        );
        $actual = $service->processAllInOneAssessment(new User(), $input);
        foreach ($actual->getResponses() as $r) {
            $this->assertEquals(
                $expected[$r->getQuestion()->getId()],
                $r->getChoice()->getId(),
            );
        }
        if ($isComplete) {
            $this->assertEquals($toPass, $actual->getProfile()->isAssessmentPassed());
            $this->assertTrue($actual->isComplete());
            $this->assertEquals($toPass, $actual->isPassed());
        } else {
            $this->assertNull($actual->getProfile()->isAssessmentPassed());
            $this->assertFalse($actual->isComplete());
            $this->assertNull($actual->isPassed());
        }
    }

    public function testProcessAllInOneCategorisation(): void
    {
        $details = [
            'fieldA' => '1245',
            'fieldB' => true,
        ];
        $actual = $this->service->processAllInOneCategorisation(new User(), [
            'category' => UserCategory::Sophisticated->value,
            'details' => $details,
        ]);
        $this->assertEquals(UserCategory::Sophisticated, $actual->getCategory());
        $this->assertEquals($details, $actual->getDetails());
    }

    public static function invalidAllInOneCategorisationProvider(): \Generator
    {
        yield 'Unknown enum' => [
            ValueError::class,
            ['category' => 'abc', 'details' => []],
        ];
        yield 'Invalid datetime string' => [
            \Exception::class,
            ['categoryReviewedAt' => 'abc', 'details' => []],
        ];
        yield 'Missing category' => [\Exception::class, ['details' => []]];
        yield 'Missing details' => [\Exception::class, ['category' => 'hnw']];
        yield 'Missing both required' => [
            \Exception::class,
            ['notes' => "everything's gone"],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'invalidAllInOneCategorisationProvider',
    )]
    public function testProcessAllInOneCategorisationInvalidValues(
        string $exceptionClassString,
        $input,
    ): void {
        $this->expectException($exceptionClassString);
        $this->service->processAllInOneCategorisation(new User(), $input);
    }

    public static function setLegacyCategorisationProvider(): \Generator
    {
        yield 'Restricted' => [
            UserCategory::Restricted,
            [
                'restricted' => true,
                'sophisticated' => false,
                'hnw' => false,
            ],
        ];
        yield 'Sophisticated' => [
            UserCategory::Sophisticated,
            [
                'restricted' => false,
                'sophisticated' => true,
                'hnw' => false,
            ],
        ];
        yield 'Hnw' => [
            UserCategory::HighNetWorth,
            [
                'restricted' => false,
                'sophisticated' => false,
                'hnw' => true,
            ],
        ];
        yield 'None' => [
            UserCategory::None,
            [
                'restricted' => true,
                'sophisticated' => false,
                'hnw' => false,
            ],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('setLegacyCategorisationProvider')]
    public function testSetLegacyCategorisation(
        UserCategory $category,
        array $expected,
    ): void {
        $user = EntityIdTestUtil::setEntityId(new User(), 514);
        // In the "none" case, there should be no changes
        // So set the original investor to whatever the expected is
        if ($category == UserCategory::None) {
            $investor = new Investor();
            $investor->setCxbRestrictedUser($expected['restricted']);
            $investor->setCxbSophisticatedInvestor($expected['sophisticated']);
            $investor->setCxbWorthInvestor($expected['hnw']);
            $user->setInvestor($investor);
        }

        $categorisation = new UserCategorisation();
        $categorisation->setCategory($category);
        $actual = $this->service->setLegacyCategorisation($user, $categorisation);
        $this->assertEquals($actual->getCxbRestrictedUser(), $expected['restricted']);
        $this->assertEquals(
            $actual->getCxbSophisticatedInvestor(),
            $expected['sophisticated'],
        );
        $this->assertEquals($actual->getCxbWorthInvestor(), $expected['hnw']);
    }
}
