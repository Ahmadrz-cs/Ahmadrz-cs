<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Entity\User;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
class KycPermissionsTest extends PermissionsWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testKycHub(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $readPaths = [
            '/admin/kyc',
            '/admin/kyc/onboarding',
            '/admin/kyc/vip',
            '/admin/kyc/recurring',
            '/admin/kyc/mangopay/documents',
            '/admin/kyc/reviews',
            '/admin/kyc/reviews/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testKycEdit(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            '/admin/kyc/reviews/create',
            '/admin/kyc/reviews/1/edit',
            '/admin/kyc/recurring/quick-create',
            '/admin/kyc/mangopay/check/document/1',
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testKycReview(string $user, int $expected): void
    {
        $userWithoutMangopayId = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'kycred.auto@test.yielderverse.co.uk']);
        $kycReview = new KycReview(KycReviewType::Adhoc, $userWithoutMangopayId);
        $this->entityManager->persist($kycReview);
        $this->entityManager->flush();

        $this->loginWebClient($user);
        $this->client->followRedirects();
        $readPaths = [
            "/admin/kyc/onboarding/{$userWithoutMangopayId->getId()}",
            "/admin/kyc/vip/{$userWithoutMangopayId->getId()}",
            "/admin/kyc/recurring/{$kycReview->getId()}",
        ];
        foreach ($readPaths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
