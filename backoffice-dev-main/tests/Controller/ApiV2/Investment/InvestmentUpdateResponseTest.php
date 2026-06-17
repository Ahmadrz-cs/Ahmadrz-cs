<?php

namespace App\Tests\Controller\ApiV2\Investment;

use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class InvestmentUpdateResponseTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<'basic-core fields'|'monetary fields', array{0: array{numberOfShares?: '1000', transactionId?: '12345678', type?: 'normal', status?: 'settled', currency?: 'GBP'}}, mixed, void>
     */
    public static function fieldsetProvider(): \Generator
    {
        yield 'basic-core fields' => [
            [
                'type' => 'normal',
                'status' => 'settled',
                'currency' => 'GBP',
            ],
        ];
        yield 'monetary fields' => [
            [
                'numberOfShares' => '1000',
                //'pricePerShare' => '1',
                'transactionId' => '12345678',
            ],
        ];
    }

    /**
     * @psalm-return \Generator<string, array{0: 'approved'|'open'|'rejected', 1: string}, mixed, void>
     */
    public static function investmentStatusTransitionProvider(): \Generator
    {
        yield 'open to approved' => ['open', 'approved'];
        yield 'open to withdrawn' => ['open', 'withdrawn'];
        yield 'open to rejected' => ['open', 'rejected'];
        yield 'approved to open' => ['approved', 'open'];
        yield 'approved to rejected' => ['approved', 'rejected'];
        yield 'rejected to approved' => ['rejected', 'approved'];
        yield 'approved to withdrawn' => ['approved', 'withdrawn'];
        yield 'approved to settled' => ['approved', 'settled'];
    }

    public function testUpdateInvestment(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
        $content = json_encode([]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $expectedFields = ApiResponseFields::INVESTMENT_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('fieldsetProvider')]
    public function testUpdateInvestmentFieldsets($fieldset): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/investments/1';
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

    #[\PHPUnit\Framework\Attributes\DataProvider('investmentStatusTransitionProvider')]
    public function testUpdateInvestmentFieldsStatus($initialState, $expected): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            \App\Entity\Investment::class,
            ['status' => $initialState],
            true,
        );
        $uri = self::API_PATH_PREFIX_V2 . '/investments/' . $sample[0];
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

    public function testUpdateInvestmentDocument(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(\App\Entity\InvestmentDocuments::class);
        $uri =
            self::API_PATH_PREFIX_V2
            . '/investments/'
            . $sample[0]->getInvestment()->getId()
            . '/documents/'
            . $sample[0]->getId();
        $fieldset = [
            'fileName' => 'a-new-name.pdf',
            'tag' => 'some other tag',
            'description' => 'some file description',
        ];
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
}
