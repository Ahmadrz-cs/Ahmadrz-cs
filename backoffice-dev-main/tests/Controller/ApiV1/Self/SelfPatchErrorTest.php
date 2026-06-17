<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Entity\User;
use App\Test\FixtureWebTestCase;

class SelfPatchErrorTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfKycReviewNotOwn(): void
    {
        $user = $this->searchFixtures(User::class, [
            'username' => self::USER_REGULAR_2,
        ])[0];

        // Login with a different user to the one the kyc review is for
        $this->loginApiClientUser(self::USER_REGULAR);

        // Create a new KycReview for the user
        $kycReview = new KycReview(KycReviewType::Adhoc, $user);
        $kycReview->setStatus(KycReviewStatus::PendingSubjectAction);
        $this->entityManager->persist($kycReview);
        $this->entityManager->flush();
        $this->assertNotEmpty($kycReview->getId());

        $uri = self::API_PATH_PREFIX_V1 . "/self/kyc-reviews/{$kycReview->getId()}";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $requestBody = [
            'status' => 'ready',
        ];
        $content = json_encode($requestBody);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(403);
    }
}
