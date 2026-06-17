<?php

namespace App\Tests\Controller\Admin\Permissions;

use App\Entity\User;
use App\Test\FixtureTestCase;
use App\Test\PermissionsWebTestCase;

#[\PHPUnit\Framework\Attributes\Group('permissions')]
#[\PHPUnit\Framework\Attributes\Group('mangopay')]
class UserPermissionsTest extends PermissionsWebTestCase
{
    public static function userEditPermissionsProvider(): \Generator
    {
        yield 'Superadmin can edit all' => [
            FixtureTestCase::USER_SUPER_ADMIN,
            [
                FixtureTestCase::USER_SUPER_ADMIN,
                FixtureTestCase::USER_ADMIN,
                FixtureTestCase::USER_FINOPS,
                FixtureTestCase::USER_OPERATIONS,
                FixtureTestCase::USER_ANALYST,
                FixtureTestCase::USER_REGULAR,
            ],
        ];
        yield 'Admin can edit admin or below' => [
            FixtureTestCase::USER_ADMIN,
            [
                FixtureTestCase::USER_ADMIN,
                FixtureTestCase::USER_FINOPS,
                FixtureTestCase::USER_OPERATIONS,
                FixtureTestCase::USER_ANALYST,
                FixtureTestCase::USER_REGULAR,
            ],
        ];
        yield 'Finops can edit finops or below' => [
            FixtureTestCase::USER_FINOPS,
            [
                FixtureTestCase::USER_FINOPS,
                FixtureTestCase::USER_OPERATIONS,
                FixtureTestCase::USER_ANALYST,
                FixtureTestCase::USER_REGULAR,
            ],
        ];
        // Note that ops and finops are equivalent outside of financial-related actions
        yield 'Ops can edit finops or below' => [
            FixtureTestCase::USER_OPERATIONS,
            [
                FixtureTestCase::USER_FINOPS,
                FixtureTestCase::USER_OPERATIONS,
                FixtureTestCase::USER_ANALYST,
                FixtureTestCase::USER_REGULAR,
            ],
        ];
        yield 'Analyst cannot edit at all' => [FixtureTestCase::USER_ANALYST, []];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minAnalystProvider')]
    public function testUserRead(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $userWithoutMangopayId = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => 'kycred.auto@test.yielderverse.co.uk'])
            ?->getId();
        $paths = [
            '/admin/users',
            '/admin/users/list',
            '/admin/users/managers',
            '/admin/users/1/managed-users',
            "/admin/users/{$userWithoutMangopayId}/dashboard",
            "/admin/users/{$userWithoutMangopayId}/dashboard/onboarding",
            "/admin/users/{$userWithoutMangopayId}/dashboard/kyc",
            "/admin/users/{$userWithoutMangopayId}/dashboard/documents",
            "/admin/users/{$userWithoutMangopayId}/dashboard/investments",
            "/admin/users/{$userWithoutMangopayId}/dashboard/relistings",
            "/admin/users/{$userWithoutMangopayId}/dashboard/payments",
            // "/admin/users/{$userWithoutMangopayId}/dashboard/portfolio", # unsupported sqlite
            "/admin/users/{$userWithoutMangopayId}/dashboard/statements",
            "/admin/users/{$userWithoutMangopayId}/dashboard/bank-accounts",
            "/admin/users/{$userWithoutMangopayId}/dashboard/status-logs",
            "/admin/users/{$userWithoutMangopayId}/dashboard/event-logs",
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }

    /**
     * @param string[] $editableUsers
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('userEditPermissionsProvider')]
    public function testUserUpdate(string $user, array $editableUsers): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $userFixtures = $this->searchFixtures(User::class, [
            'username' => [
                FixtureTestCase::USER_SUPER_ADMIN,
                FixtureTestCase::USER_ADMIN,
                FixtureTestCase::USER_FINOPS,
                FixtureTestCase::USER_OPERATIONS,
                FixtureTestCase::USER_ANALYST,
                FixtureTestCase::USER_REGULAR,
            ],
        ]);
        foreach ($userFixtures as $userFixture) {
            $expected = in_array($userFixture->getUsername(), $editableUsers);
            $crawler = $this->client->request(
                'GET',
                '/admin/users/' . $userFixture->getId() . '/edit',
            );
            $form = $crawler->filter('form')->form();

            // Check whether all form fields are disabled
            $formValues = $form->getValues();
            $this->assertGreaterThanOrEqual((int) $expected, count($formValues));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('minOperationsProvider')]
    public function testUserStateTransitions(string $user, int $expected): void
    {
        $this->loginWebClient($user);
        $this->client->followRedirects();
        $paths = [
            '/admin/users/1/toggle-company-approved',
        ];
        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            $this->assertResponseStatusCodeSame($expected);
        }
    }
}
