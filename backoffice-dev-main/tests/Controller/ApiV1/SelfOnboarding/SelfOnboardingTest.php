<?php

namespace App\Tests\Controller\ApiV1\SelfOnboarding;

use App\Entity\Enum\QuestionType;
use App\Entity\Enum\UserCategory;
use App\Entity\Question;
use App\Entity\QuestionChoice;
use App\Entity\User;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV1\ApiV1ResponseFields;

class SelfOnboardingTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetOnboardingProfile(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/onboarding/profile';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_ONBOARDING_PROFILE,
            array_keys($apiResponse),
        );
        $this->assertNotNull($apiResponse['cooloffEnd']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPatchOnboardingProfile(): void
    {
        $requestBody = [
            'cooloffEnd' => '2024-08-01',
            'cooloffAccepted' => false,
            'riskWarningAccepted' => true,
            'category' => UserCategory::None->value,
            'categoryReviewedAt' => '2024-08-04',
            'assessmentPassed' => false,
        ];
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/onboarding/profile';
        $this->client->request('PATCH', $uri, content: json_encode($requestBody));
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_ONBOARDING_PROFILE,
            array_keys($apiResponse),
        );
        foreach ($requestBody as $key => $expectedValue) {
            if (in_array($key, ['cooloffEnd', 'categoryReviewedAt'])) {
                $expectedValue = new \DateTime(
                    $requestBody[$key],
                )->format(\DateTime::ATOM);
            }
            $this->assertEquals($expectedValue, $apiResponse[$key]);
        }
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPostUserAssessment(): void
    {
        $questions = $this->entityManager
            ->getRepository(Question::class)
            ->findBy([
                'questionType' => QuestionType::Appropriateness,
            ]);
        $requestBody = ['responses' => [], 'complete' => 1];
        $questionCount = 0;
        foreach ($questions as $q) {
            if ($questionCount > 4) {
                break;
            }
            $requestBody['responses'][] = [
                'question' => $q->getId(),
                'choice' => $q
                    ->getChoices()
                    ->filter(fn(QuestionChoice $qc) => $qc?->isCorrect())
                    ->first()
                    ->getId(),
            ];
            $questionCount += 1;
        }
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri = FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/onboarding/assessment';
        $this->client->request('POST', $uri, content: json_encode($requestBody));
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_ASSESSMENT,
            array_keys($apiResponse),
        );
        $this->assertTrue($apiResponse['complete']);
        $this->assertTrue($apiResponse['passed']);
        $this->assertCount(5, $apiResponse['responses']);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => FixtureTestCase::USER_REGULAR,
            ]);
        $this->assertTrue($user->getOnboardingProfile()->isAssessmentPassed());
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testPostUserCategorisation(): void
    {
        $requestBody = [
            'category' => UserCategory::HighNetWorth->value,
            'details' => [
                'fieldA' => '1245',
                'fieldB' => true,
            ],
        ];
        $startime = new \DateTime();
        $startime->modify('-1 second'); // To ensure there is a time difference if updated
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1 . '/self/onboarding/categorisation';
        $this->client->request('POST', $uri, content: json_encode($requestBody));
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::USER_CATEGORISATION,
            array_keys($apiResponse),
        );

        $this->assertEquals(
            UserCategory::HighNetWorth->value,
            $apiResponse['category'],
        );
        $this->assertEquals($requestBody['details'], $apiResponse['details']);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => FixtureTestCase::USER_REGULAR,
            ]);
        $this->assertGreaterThanOrEqual(
            $startime,
            $user->getOnboardingProfile()->getCategoryReviewedAt(),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetGeneratedAssessment(): void
    {
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/onboarding/generated-assessment';
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::QUESTION,
            array_keys($apiResponse[0]),
        );
        $this->assertEqualsCanonicalizing(
            ApiV1ResponseFields::QUESTION_CHOICE,
            array_keys($apiResponse[0]['choices'][0]),
        );
        foreach ($apiResponse as $q) {
            $qid = $q['id'];
            $this->assertEquals(
                QuestionType::Appropriateness->value,
                $q['questionType'],
            );
            $this->assertTrue($q['active']);
            $hasCorrect = false;
            foreach ($q['choices'] as $qc) {
                $this->assertTrue($qc['active']);
                $this->assertEquals($qid, $qc['question']);
                if ($qc['correct']) {
                    $hasCorrect = true;
                }
            }
            // Must have at least 1 correct answer per question
            $this->assertTrue($hasCorrect);
        }
    }
}
