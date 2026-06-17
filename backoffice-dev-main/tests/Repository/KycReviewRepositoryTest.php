<?php

namespace App\Tests\Repository;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Entity\User;
use App\Repository\KycReviewRepository;
use App\Service\KycReviewService;
use App\Test\FixtureTestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

final class KycReviewRepositoryTest extends FixtureTestCase
{
    private KycReviewRepository $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(KycReviewRepository::class);
    }

    public function testFindByWithAssociationsPagination(): void
    {
        $actual = $this->service->findByWithAssociations([], [], 2, 1);
        $this->assertEquals(1, $actual->getCurrentPage());
        $this->assertEquals(2, $actual->getMaxPerPage());
    }

    public function testFindByWithAssociationsOrdering(): void
    {
        // Check ordering by comparing actual with manually sorted
        // default ordering: id ascending
        $expected =
            $actual = $this->convertToIds($this->service->findByWithAssociations([]));
        sort($expected);
        $this->assertEquals($expected, $actual);

        // overriden ordering: id descending
        $expected =
            $actual = $this->convertToIds($this->service->findByWithAssociations([], [
                'id' => 'DESC',
            ]));
        rsort($expected);
        $this->assertEquals($expected, $actual);
    }

    public function testFindByWithAssociationsCriteriaInvalid(): void
    {
        // unsupported filters are just ignored
        $expected = $this->service->findByWithAssociations([
            'verified' => 1,
        ])->getNbResults();
        $actual = $this->service->findByWithAssociations([
            'verified' => 1,
            'abc' => 1,
            'page' => 23,
        ]);
        $this->assertCount($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('findByCriteriaProvider')]
    public function testFindByWithAssociationsCriteria(array $filters): void
    {
        /**
         * Check all results match the criteria
         * Use Symfony component PropertyAccessor for non-relational properties
         */
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $results = $this->service->findByWithAssociations($filters);
        foreach ($results as $object) {
            foreach ($filters as $key => $expected) {
                if (in_array($key, ['subjectUsername'])) {
                    $key = lcfirst(substr($key, 7));
                    $relation = $object->getSubject();
                }
                $actual = $propertyAccessor->getValue($relation ?? $object, $key);
                if (in_array($key, ['username'])) {
                    $this->assertStringContainsStringIgnoringCase($expected, $actual);
                } elseif (is_iterable($expected)) {
                    $this->assertContains($actual, $expected);
                } else {
                    $this->assertEquals($expected, $actual);
                }
                unset($relation);
            }
        }
    }

    public static function findByCriteriaProvider(): \Generator
    {
        yield 'Basic equivalence field' => [['id' => 3]];
        yield 'Decision single' => [['decision' => 0]];
        yield 'User relation single' => [['subjectUsername' => self::USER_REGULAR]];
        yield 'Combination 1' => [[
            'subjectUsername' => self::USER_REGULAR,
            'decision' => 0,
        ]];
    }

    public function testFindOpenReviews(): void
    {
        // Prep
        $kycReviewService = static::getContainer()->get(KycReviewService::class);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $user2 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR_2]);
        // Add a few KycReviews
        $idDocReview = new KycReview(KycReviewType::Recurring, $user);
        $idDocReview = $kycReviewService->applyReviewPreset(
            $idDocReview,
            'mangopay_id_doc_renewal',
        );

        $idDocOtherReview = new KycReview(KycReviewType::Recurring, $user2);
        $idDocOtherReview = $kycReviewService->applyReviewPreset(
            $idDocOtherReview,
            'mangopay_id_doc_renewal',
        );

        $idDocAdhocReview = new KycReview(KycReviewType::Recurring, $user);
        $idDocAdhocReview = $kycReviewService->applyReviewPreset(
            $idDocAdhocReview,
            'mangopay_id_doc_renewal',
        );
        $idDocAdhocReview->setReviewType(KycReviewType::Adhoc);

        $idAddressDocReview = new KycReview(KycReviewType::Recurring, $user);
        $idAddressDocReview = $kycReviewService->applyReviewPreset(
            $idAddressDocReview,
            'mangopay_id_doc_renewal',
        );
        $idAddressDocReview->setAddressReview(true);

        $transactionReview = new KycReview(KycReviewType::Recurring, $user);
        $transactionReview = $kycReviewService->applyReviewPreset(
            $transactionReview,
            'mangopay_id_doc_renewal',
        );
        $transactionReview->setIdentityReview(false);
        $transactionReview->setTransactionsReview(true);

        $idDocCompletedReview = new KycReview(KycReviewType::Recurring, $user);
        $idDocCompletedReview = $kycReviewService->applyReviewPreset(
            $idDocCompletedReview,
            'mangopay_id_doc_renewal',
        );
        $idDocCompletedReview->setStatus(KycReviewStatus::Completed);

        $this->entityManager->persist($idDocReview);
        $this->entityManager->persist($idDocOtherReview);
        $this->entityManager->persist($idDocAdhocReview);
        $this->entityManager->persist($idAddressDocReview);
        $this->entityManager->persist($transactionReview);

        $this->entityManager->flush();

        // Execute
        $actions = ['identityReview', 'kycProviderReview', 'otherUnsupportedrReview'];
        $actual = $this->service->findOpenReviews(
            $user,
            KycReviewType::Recurring,
            $actions,
        );

        // Check
        $this->assertGreaterThanOrEqual(1, count($actual));
        foreach ($actual as $review) {
            $this->assertTrue($review->isIdentityReview());
            $this->assertTrue($review->isKycProviderReview());

            $this->assertFalse($review->isAddressReview());
            $this->assertFalse($review->isCountryReview());
            $this->assertFalse($review->isDueDiligenceLevelReview());
            $this->assertFalse($review->isKycSurveyReview());
            $this->assertFalse($review->isTransactionsReview());

            $this->assertEquals($user->getId(), $review->getSubject()->getId());
            $this->assertEquals(KycReviewType::Recurring, $review->getReviewType());
            $this->assertContains(
                $review->getStatus(),
                KycReviewStatus::editableCases(),
            );
        }
    }
}
