<?php

namespace App\Tests\Controller\ApiV2\User;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserDeleteResponseTest extends FixtureWebTestCase
{
    public function testDeleteUserDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(\App\Entity\User::class, [], true);
        $sample = $this->searchFixtures(\App\Entity\UserDocument::class, [
            'User' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/users/'
            . $sample[0]->getUser()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $this->client->request('DELETE', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        // $sample = $this->filterUserDocs(
        //     $this->getAllOfType(\App\Entity\UserDocument::class),
        //     ["id" => $sample[0]->getId()]
        // );
        // $this->assertEquals(0, count($sample));

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
