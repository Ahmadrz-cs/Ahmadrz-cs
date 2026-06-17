<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class InvestmentDeletePermissionsTest extends FixtureWebTestCase
{
    public function testDeleteInvestmentDocumentAsAdminMissingScope(): void
    {
        $scopes = array_diff($this->permittedScopes, ['investment:write']);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $filter = $this->searchFixtures(\App\Entity\Investment::class, [], true);
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getInvestment()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteInvestmentDocumentOtherAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR_2);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getInvestment()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteInvestmentDocumentOwnAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getInvestment()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteInvestmentDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $userFilter = $this->searchFixtures(
            \App\Entity\User::class,
            ['username' => 'ben.auto@test.yielderverse.co.uk'],
            true,
        );
        $filter = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['user' => $userFilter, 'status' => 'settled'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class, [
            'investment' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getInvestment()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
