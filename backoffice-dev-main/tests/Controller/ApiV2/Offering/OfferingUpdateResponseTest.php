<?php

namespace App\Tests\Controller\ApiV2\Offering;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class OfferingUpdateResponseTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'basic-core fields'|'detailed fields', array{0: array{name: 'offering name updated', minCommit?: '100', maxCommit?: '1000000', externalCommitments?: '0', netAnnualYield?: '10', netTotalReturn?: '15', numberOfShares?: '150', isFeatured?: true}}, mixed, void>
     */
    public static function fieldsetProvider(): \Generator
    {
        yield 'basic-core fields' => [
            [
                'name' => 'offering name updated',
                'numberOfShares' => '150',
                'isFeatured' => true,
            ],
        ];
        yield 'detailed fields' => [
            [
                'name' => 'offering name updated',
                'minCommit' => '100',
                'maxCommit' => '1000000',
                'externalCommitments' => '0',
                // 'term' => '5',
                'netAnnualYield' => '10',
                'netTotalReturn' => '15',
            ],
        ];
    }

    /**
     * @psalm-return \Generator<string, array{0: string, 1: string}, mixed, void>
     */
    public static function offeringStatusTransitionProvider(): \Generator
    {
        yield 'draft to submitted' => ['draft', 'submitted'];
        yield 'draft to approved' => ['draft', 'approved'];
        yield 'draft to published' => ['draft', 'published'];
        yield 'draft to cancelled' => ['draft', 'cancelled'];
        yield 'submitted to approved' => ['submitted', 'approved'];
        yield 'approved to published' => ['approved', 'published'];
        yield 'published to cancelled' => ['published', 'cancelled'];
        yield 'published to archived' => ['published', 'archived'];
    }

    public function testUpdateOffering(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
        $content = json_encode([
            'name' => 'testUpdateOffering',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::OFFERING_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fieldsetProvider')]
    public function testUpdateOfferingFieldsSets($fieldset): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/1';
        $content = json_encode($fieldset);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        foreach ($fieldset as $key => $expected) {
            $this->assertEquals($expected, $apiResponse[$key]);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('offeringStatusTransitionProvider')]
    public function testUpdateOfferingFieldsStatus($initialState, $expected): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\Offering::class,
            ['status' => $initialState],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0];
        $content = json_encode([
            'status' => $expected,
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $actual = $apiResponse['status'];
        $this->assertEquals($expected, $actual);
    }

    // public function testUpdateOfferingDocument(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(\App\Entity\OfferingDocuments::class);
    //     $uri = self::API_PATH_PREFIX_V2 . '/offerings/' . $sample[0]->getUser()->getId() . '/documents/' . $sample[0]->getId();
    //     $fieldset = [
    //         "description" => "test basic doc update",
    //         "tag" => "test tag",
    //     ];
    //     $content = json_encode($fieldset);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('PATCH', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    //     $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
    //     foreach ($fieldset as $key => $expected) {
    //         $this->assertEquals($expected, $apiResponse[$key]);
    //     }
    // }
}
