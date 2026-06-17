<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserDeletePermissionsTest extends FixtureWebTestCase
{
    // public function testDeleteUserDocumentAsAdminMissingScope(): void
    // {
    //     $scopes = array_diff($this->permittedScopes, ['user:write']);
    //     $this->loginApiClientUser(self::USER_ADMIN, $scopes);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         [],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getDocument()->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    // public function testDeleteUserOtherDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "holly.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter[0]],
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getDocument()->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testDeleteUserDocumentAsPublic(): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\UserDocument::class);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/users/'
            . $sample[0]->getUser()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testDeleteUserOwnDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["username" => "ben.auto@test.yielderverse.co.uk"],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\UserDocument::class,
    //         ["user" => $filter[0]],
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/users/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getDocument()->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    // }
}
