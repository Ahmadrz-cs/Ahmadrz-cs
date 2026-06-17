<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingDeleteResponseTest extends FixtureWebTestCase
{
    public function testDeleteOfferingDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $filter = $this->searchFixtures(\App\Entity\Offering::class, [], true);
        $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class, [
            'offering' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/offerings/'
            . $sample[0]->getOffering()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $this->client->request('DELETE', $uri);
        // $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        // $sample = $this->filterOfferingDocs(
        //     $this->getAllOfType(\App\Entity\OfferingDocuments::class),
        //     ["id" => [$sample[0]->getId()]]
        // );
        // $this->assertEquals(0, count($sample));

        // Currently not implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
