<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class OfferingDeleteErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'offering collection'|'offering documents'|'offering single', array{0: '/offerings'|'/offerings/1'|'/offerings/1/documents'}, mixed, void>
     */
    public static function undeletableOfferingEndpointsProvider(): \Generator
    {
        yield 'offering collection' => ['/offerings'];
        yield 'offering single' => ['/offerings/1'];
        yield 'offering documents' => ['/offerings/1/documents'];

        // yield "offering investments" => ["/offeringss/1/investments"];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider(
        'undeletableOfferingEndpointsProvider',
    )]
    public function testDeleteOfferingEndpointsNotAllowed($endpoint): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;

        $this->client->request('DELETE', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testDeleteOfferingDocumentNotExists(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/1/documents/-1';
    //     $this->client->request('DELETE', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    // }
}
