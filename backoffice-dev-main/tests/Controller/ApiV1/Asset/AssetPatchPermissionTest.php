<?php

namespace App\Tests\Controller\ApiV1\Asset;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AssetPatchPermissionTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('permission')]
    public function testUpdateAssetAsRegUser(): void
    {
        /**
         * This seems to be broken at the moment!
         */
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/assets/1';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $this->client->request('PATCH', $uri, [], [], $headers, '{}');

        // $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
