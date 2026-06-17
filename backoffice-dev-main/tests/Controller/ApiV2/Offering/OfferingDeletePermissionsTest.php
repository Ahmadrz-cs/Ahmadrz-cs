<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingDeletePermissionsTest extends FixtureWebTestCase
{
    public function testDeleteOfferingDocumentAsAdminMissingScope(): void
    {
        $scopes = array_diff($this->permittedScopes, ['offering:write']);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => 'published'],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class, [
            'offering' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/offerings/'
            . $sample[0]->getOffering()->getId()
            . '/documents/'
            . $sample[0]->getDocument()->getId();
        $this->client->request('DELETE', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        // Currently unimplemented so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testDeleteOfferingDocumentAsPubic(): void
    // {
    //     $this->loginApiClientPublic();
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         [],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\OfferingDocuments::class,
    //         ["offering" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getOffering()->getId() . '/documents/' . $sample[0]->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
    // public function testDeleteOfferingDocumentAsRegUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         [],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\OfferingDocuments::class,
    //         ["offering" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getOffering()->getId() . '/documents/' . $sample[0]->getId();
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }
}
