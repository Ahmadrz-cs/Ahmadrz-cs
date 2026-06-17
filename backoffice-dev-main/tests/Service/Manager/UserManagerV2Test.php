<?php

namespace App\Tests\Service\Manager;

use App\Entity\User;
use App\Service\Manager\UserManagerV2;
use App\Test\FixtureWebTestCase;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use League\Bundle\OAuth2ServerBundle\Security\Authentication\Token\OAuth2Token;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserManagerV2Test extends FixtureWebTestCase
{
    private UserManagerV2 $service;

    public function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserManagerV2::class);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('preAuthenticatedTokenProvider')]
    public function testfindManagerClientForRequest(
        ?string $expected,
        string $clientType,
    ): void {
        if ($expected) {
            $user = $this->searchFixtures(\App\Entity\User::class, [
                'username' => $expected,
            ])[0];
        } else {
            $user = $this->searchFixtures(\App\Entity\User::class, [
                'username' => self::USER_SUPER_ADMIN,
            ])[0];
        }
        /**
         * Do an API login and simulate security context using that token
         * API login with OAuth2 will return an OAuth2Token
         * UserManagerV2 calls findClientByTokenId() which uses the accessToken id to match against a user
         * Test helper generateAccessToken() will do an API login and return the access token id
         * Which is then used to created the OAuth2Token for this test
         */
        $token = new OAuth2Token(
            $user,
            $this->generateAccessToken($clientType),
            '',
            [],
            '',
        );
        $securityContext = static::getContainer()->get(TokenStorageInterface::class);
        $securityContext->setToken($token);
        // Refresh the service after updating the toke storage
        $this->service = static::getContainer()->get(UserManagerV2::class);
        if ($expected) {
            $this->assertSame(
                $expected,
                $this->service
                    ->findManagerClientForRequest()
                    ->getUser()
                    ->getUsername(),
            );
        } else {
            $this->assertNull($this->service->findManagerClientForRequest());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('managerReferralCodeGenerator')]
    public function testGetManagerReferralCode(?string $alias, string $username): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => $username,
        ])[0];
        $userClient = $this->searchFixtures(\App\Entity\UserClient::class, [
            'alias' => 'vendaland',
        ])[0];
        $userClient->setAlias($alias);
        $userClient->setUser($user);
        if (!$alias) {
            $expected = $this->service->generateUserAffiliateCode($user);
        } else {
            $expected = $alias;
        }
        $actual = $this->service->getManagerReferralCode($userClient);
        $this->assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('userAffiliateCodeGenerator')]
    public function testGenerateUserAffiliateCode(
        string $codePrefix,
        string $username,
        string $lastName,
    ): void {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => $username,
        ])[0];
        $user->setLastname($lastName);
        $expected = $codePrefix . $user->getId();
        $actual = $this->service->generateUserAffiliateCode($user);
        $this->assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('companyApprovedProvider')]
    public function testSetCompanyApproved(bool $expected, ?bool $approved): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];
        $actual = $this->service->setCompanyApproved($user, $approved);
        $this->assertSame($expected, $actual);
        $this->assertSame($expected, $user->getInvestor()->getCorporateInvestor());
    }

    public function testGetMangopayUser(): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];
        $mangopayUser = $this->service->getMangopayUser($user);
        $this->assertNotEmpty($mangopayUser);
        $this->assertSame('REGULAR', $mangopayUser->KYCLevel);

        $user = new User();
        $mangopayUser = $this->service->getMangopayUser($user);
        $this->assertNull($mangopayUser);
    }

    public function testGetMangopayUserRegulatory(): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];
        $mangopayRegulatory = $this->service->getMangopayUserRegulatory($user);
        $this->assertNotNull($mangopayRegulatory);

        $user = new User();
        $mangopayRegulatory = $this->service->getMangopayUserRegulatory($user);
        $this->assertNull($mangopayRegulatory);
    }

    public function testGetAllUserMangopayKycDocs(): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];
        $docs = $this->service->getAllUserMangopayKycDocs($user);
        $this->assertNotEmpty($docs);
        foreach ($docs as $doc) {
            $this->assertNotEmpty($doc->Type);
            $this->assertNotEmpty($doc->Status);
        }
    }

    public function testgetKycState(): void
    {
        $user = $this->searchFixtures(\App\Entity\User::class, [
            'username' => 'ben.auto@test.yielderverse.co.uk',
        ])[0];
        $state = $this->service->getKycState($user);
        $this->assertNotEmpty($state);
        $expected = [
            'contegoScore',
            'mangopayDocs',
            'mangopayStatus',
            'mangopayUserCategory',
            'mangopayTermsAccepted',
            'mangopayPersonType',
            'mangopayUserProfile',
            'mangopayRegulatory',
        ];
        $this->assertEqualsCanonicalizing($expected, array_keys($state));
    }

    public function testGetSuperAdmin(): void
    {
        $user = $this->service->getSuperAdmin();
        $this->assertEquals(self::USER_SUPER_ADMIN, $user);
    }

    public function testGetSuperAdminNotFound(): void
    {
        $existingSuperAdmin = $this->service->getSuperAdmin();
        $existingSuperAdmin->setRoles(['ROLE_ADMIN']); // demote user to anything below super admin
        $this->entityManager->flush();

        $this->expectExceptionMessage('Superadmin is not set');
        $this->service->getSuperAdmin();
    }

    public function testGetSuperAdminNotUnique(): void
    {
        /** @var User $anotherAdmin */
        $anotherAdmin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_TECHOPS]);
        $anotherAdmin->setRoles(['ROLE_SUPER_ADMIN']); // prompte another user to super admin
        $this->entityManager->flush();

        $this->expectExceptionMessage('Superadmin is not unique');
        $this->service->getSuperAdmin();
    }

    public static function preAuthenticatedTokenProvider(): \Generator
    {
        yield 'Vendor' => [self::USER_VENDOR, 'vendor'];
        yield 'First Party' => [null, 'first-party'];
    }

    public static function managerReferralCodeGenerator(): \Generator
    {
        yield 'Alias' => ['yieldervendaland', 'holly.auto@test.yielderverse.co.uk'];
        yield 'User fallback' => [null, 'ben.auto@test.yielderverse.co.uk'];
    }

    public static function userAffiliateCodeGenerator(): \Generator
    {
        yield 'Single' => ['LTorfen', 'lorna.auto@test.yielderverse.co.uk', 'Torfen'];
        yield 'Double barrel' => [
            'LEllis-Torfen',
            'lorna.auto@test.yielderverse.co.uk',
            'Ellis-Torfen',
        ];
        yield 'Multi' => [
            'LEllis',
            'lorna.auto@test.yielderverse.co.uk',
            'Ellis Torfen',
        ];
    }

    public static function companyApprovedProvider(): \Generator
    {
        yield 'Toggle' => [true, null];
        yield 'Approved' => [true, true];
        yield 'Unapproved' => [false, false];
    }

    private function generateAccessToken(string $type = ''): string
    {
        // The jti (JWT Id) is the id of the access token generated (not the token itself though!)
        // This token id is stored in the database and used to match against a user id if available
        $accessToken = $this->getAccessToken($type);
        /** @var \Lcobucci\JWT\Token\Plain $token */
        $token = new Parser(new JoseEncoder())->parse((string) $accessToken);
        return $token->claims()->get('jti');
    }

    private function getAccessToken(string $type = ''): ?string
    {
        $clientCredentials = 'vendor' === $type
            ? self::OAUTH2_CLIENT_VENDOR
            : self::OAUTH2_CLIENT_DEFAULT;
        $this->client->request('POST', self::OAUTH2_PATH_TOKEN, [
            'grant_type' => 'client_credentials',
            'scope' => '',
            'client_id' => $clientCredentials['clientId'],
            'client_secret' => $clientCredentials['clientSecret'],
        ]);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        return $response['access_token'];
    }
}
