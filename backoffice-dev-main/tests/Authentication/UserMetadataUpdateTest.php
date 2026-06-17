<?php

namespace App\Tests\Authentication;

use App\Entity\User;
use App\Test\FixtureWebTestCase;

class UserMetadataUpdateTest extends FixtureWebTestCase
{
    public function testLastLoginUpdatedPasswordGrant(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $prevLastLogin = new \DateTime();
        $prevLastLogin->modify('-1 days');
        $user->setLastLogin($prevLastLogin);
        $this->entityManager->flush();

        $this->loginApiClientUser($user->getUsername());

        // Force refresh of user entity
        $this->entityManager->clear();
        $user = $this->entityManager->getRepository(User::class)->find(1);

        // Check last login has updated
        $currentLastLogin = $user->getLastLogin();
        $this->assertGreaterThan($prevLastLogin, $currentLastLogin);
    }

    public function testLastLoginUpdatedLoginForm(): void
    {
        $user = $this->entityManager->getRepository(User::class)->find(1);

        $prevLastLogin = new \DateTime();
        $prevLastLogin->modify('-1 days');
        $user->setLastLogin($prevLastLogin);
        $this->entityManager->flush();

        $this->client->request('GET', '/login');
        $this->client->submitForm('Login', [
            '_username' => $user->getUsername(),
            '_password' => 'HarvestBounty!756',
        ]);

        // Force refresh of user entity
        $this->entityManager->clear();
        $user = $this->entityManager->getRepository(User::class)->find(1);

        // Check last login has updated
        $currentLastLogin = $user->getLastLogin();
        $this->assertGreaterThan($prevLastLogin, $currentLastLogin);
    }
}
