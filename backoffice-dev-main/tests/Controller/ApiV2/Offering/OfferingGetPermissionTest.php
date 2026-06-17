<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingGetPermissionTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'published offering', array{0: 'published', 1: 200}, mixed, void>
     */
    public static function nonAdminOfferingStatusProvider(): \Generator
    {
        // yield "draft offering" => ["draft", Response::HTTP_FORBIDDEN];
        // yield "submitted offering" => ["submitted", Response::HTTP_FORBIDDEN];
        // yield "rejected offering" => ["rejected", Response::HTTP_FORBIDDEN];
        // yield "approved offering" => ["approved", Response::HTTP_FORBIDDEN];
        // yield "restricted offering" => ["restricted", Response::HTTP_FORBIDDEN];
        yield 'published offering' => ['published', Response::HTTP_OK];

        // yield "live offering" => ["live", Response::HTTP_FORBIDDEN];
        // yield "closed offering" => ["closed", Response::HTTP_FORBIDDEN];
        // yield "settled offering" => ["settled", Response::HTTP_FORBIDDEN];
        // yield "cancelled offering" => ["cancelled", Response::HTTP_FORBIDDEN];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: array{0: 'offering:read'}}, mixed, void>
     */
    public static function offeringEndpointScopeProvider(): \Generator
    {
        yield 'offering collection' => ['/offerings', ['offering:read']];
        yield 'offering single' => ['/offerings/1', ['offering:read']];
        // yield "offering investments" => ["/offerings/1/investments", ['offering:read', 'investment:read']];
        yield 'offering documents' => ['/offerings/1/documents', ['offering:read']];
        yield 'offering document single' => [
            '/offerings/1/documents/1',
            ['offering:read'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringEndpointScopeProvider')]
    public function testGetOfferingEndpointsAsAdminMissingScope(
        $endpoint,
        $requiredScopes,
    ): void {
        $scopes = array_diff($this->permittedScopes, $requiredScopes);
        $this->loginApiClientUser(self::USER_ADMIN, $scopes);
        $uri = self::API_PATH_PREFIX_V2 . $endpoint;
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetOfferingsAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGetOfferingsAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    // public function testGetOfferingsAsVipUser(): void
    // {
    //     $this->loginApiClientUser(self::USER_VIP);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    // }
    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminOfferingStatusProvider')]
    public function testGetSingleOfferingAsPublic($status, $expectedStatusCode): void
    {
        $this->loginApiClientPublic();
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => $status,
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminOfferingStatusProvider')]
    public function testGetSingleOfferingAsRegUser($status, $expectedStatusCode): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $sample = $this->searchFixtures(\App\Entity\Offering::class, [
            'status' => $status,
        ]);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId();
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetSingleOfferingAsVipUser($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientUser(self::USER_VIP);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId();
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame($expectedStatusCode);
    // }
    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminOfferingStatusProvider')]
    public function testGetOfferingDocumentsAsPublic($status, $expectedStatusCode): void
    {
        $this->loginApiClientPublic();
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => $status],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class, [
            'offering' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/offerings/'
            . $sample[0]->getOffering()->getId()
            . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonAdminOfferingStatusProvider')]
    public function testGetOfferingDocumentsAsRegUser(
        $status,
        $expectedStatusCode,
    ): void {
        $this->loginApiClientUser(self::USER_REGULAR);
        $filter = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => $status],
            true,
        );
        $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class, [
            'offering' => $filter,
        ]);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/offerings/'
            . $sample[0]->getOffering()->getId()
            . '/documents';
        $this->client->request('GET', $uri);
        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetOfferingSingleDocumentAsPublic($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientPublic();
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\OfferingDocuments::class,
    //         ["offering" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getOffering()->getId() . '/documents/' . $sample[0]->getId();
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame($expectedStatusCode);
    // }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetOfferingSingleDocumentAsRegUser($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $filter = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status],
    //         true
    //     );
    //     $sample = $this->searchFixtures(
    //         \App\Entity\OfferingDocuments::class,
    //         ["offering" => $filter]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getOffering()->getId() . '/documents/' . $sample[0]->getId();
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame($expectedStatusCode);
    // }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetOfferingInvestmentsAsPublic($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId() . '/investments';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetOfferingInvestmentsAsRegUser($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId() . '/investments';
    //     $this->client->request('GET', $uri);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    // }

    public function testGetOfferingsQueryViewAdminAsPublic(): void
    {
        $this->loginApiClientPublic();
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEmpty(array_diff($expectedFields, $actualFields));
    }

    public function testGetOfferingsQueryViewAdminAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings';
        $parameters = [
            'view' => 'admin',
        ];
        $this->client->request('GET', $uri, $parameters);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse['data'][0]);
        $this->assertEmpty(array_diff($expectedFields, $actualFields));
    }

    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetSingleOfferingQueryViewAdminAsPublic($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientPublic();
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId();
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame($expectedStatusCode);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
    // /**
    //  * @dataProvider nonAdminOfferingStatusProvider
    //  */
    // public function testGetSingleOfferingQueryViewAdminAsRegUser($status, $expectedStatusCode): void
    // {
    //     $this->loginApiClientUser(self::USER_REGULAR);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["status" => $status]
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getId();
    //     $parameters = [
    //         'view' => 'admin'
    //     ];
    //     $this->client->request('GET', $uri, $parameters);
    //     $this->assertResponseStatusCodeSame($expectedStatusCode);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     $expectedFields = ApiResponseFields::OFFERING_STANDARD;
    //     $actualFields = array_keys($apiResponse);
    //     $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    // }
}
