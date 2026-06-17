<?php

namespace App\Tests\Controller\ApiV1\Self;

use App\Entity\BankAccount;
use App\Entity\User;
use App\Test\ExternalServiceWebTestCase;
use App\Test\FixtureTestCase;
use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SelfGetErrorTest extends ExternalServiceWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfBankAccountRegistrationSingleNotFound(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);

        $uri = self::API_PATH_PREFIX_V1 . '/self/bank-accounts/123456';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(404);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testGetSelfBankAccountRegistrationSingleOtherUserNotFound(): void
    {
        // Trying to get a bank account registration belonging to another user will result in not found

        $this->loginApiClientUser(self::USER_REGULAR);

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_SUPER_ADMIN,
            ]);
        $sample = $this->entityManager
            ->getRepository(BankAccount::class)
            ->findOneBy([
                'user' => $user->getId(),
            ]);
        // Ensure the sample is not empty
        $this->assertNotNull($sample);

        $uri = self::API_PATH_PREFIX_V1 . "/self/bank-accounts/{$sample->getId()}";
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(404);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    #[\PHPUnit\Framework\Attributes\Group('check')]
    public function testGetSelfMangopayPayinNotOwnPayin(): void
    {
        $useRemoteTests = self::$kernel
            ->getContainer()
            ->getParameter('crowdtek_mangopay_remote_test_enable');
        if (!$useRemoteTests) {
            $this->useMangopayServiceMock(ExternalServiceWebTestCase::MANGOPAY_VIEW_PAYIN);
        } else {
            $this->fail('Remote tests not implemented yet');
        }

        // Login as different user to Ben (USER_REGULAR), in this case Holly (USER_REGULAR_2)
        // Should return 404 for the payin since it is for a different wallet
        $this->loginApiClientUser(FixtureTestCase::USER_REGULAR_2);
        $uri =
            FixtureWebTestCase::API_PATH_PREFIX_V1
            . '/self/mangopay/payin/wt_0f44a630-454d-45ae-8de2-2389ea38f7bb';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(404);
    }
}
