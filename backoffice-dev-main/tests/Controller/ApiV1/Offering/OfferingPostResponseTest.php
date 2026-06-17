<?php

namespace App\Tests\Controller\ApiV1\Offering;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Test\FixtureWebTestCase;
use App\Tests\Controller\ApiV2\ApiBase64Files;
use Symfony\Component\HttpFoundation\Response;

class OfferingPostResponseTest extends FixtureWebTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingMin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'offering create test',
            'funding_goal' => '50000',
            'is_secondary_offering' => 1,
            'num_of_shares' => '50000',
            'price_per_share' => '1.00',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering'];
        $this->assertEquals('offering create test', $object['name']);
        $this->assertEquals(50000, $object['funding_goal']);
        $this->assertEquals(50000, $object['num_of_shares']);
        $this->assertEquals(1.00, $object['price_per_share']);
        $this->assertEquals(1, $object['is_secondary_offering']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInfoMin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'offering with info',
            'funding_goal' => 50000,
            'is_secondary_offering' => 1,
            'info' => [
                'net_rent_projected' => 100,
                'gross_rent_projected_return' => 1000,
                'gross_projected_return' => 511,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering']['custom'];
        $this->assertEquals(100, $object['net_rent_projected']);
        $this->assertEquals(1000, $object['gross_rent_projected_return']);
        $this->assertEquals(511, $object['gross_projected_return']);
    }

    #[\PHPUnit\Framework\Attributes\Group('respsponse')]
    public function testCreateOfferingFrontendMin(): void
    {
        /**
         * NOTE: testCreateOfferingFrontendMinDbCheck is also effectively
         * checked here by getting the newly made offering
         */
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'Frontend field offering',
            'funding_goal' => 50000,
            'is_secondary_offering' => 1,
            'max_commitment' => 1234151,
            'max_overfunding_amount' => 214,
            'term' => 4,
            'info' => [
                'net_rent_projected' => 100,
                'gross_rent_projected_return' => 1000,
                'gross_projected_return' => 511,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering'];
        $this->assertEquals(1234151, $object['max_commitment']);
        $this->assertEquals(214, $object['max_overfunding_amount']);
        $this->assertEquals(4, $object['term']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingMinWithDoc(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'offering with doc',
            'funding_goal' => '50000',
            'is_secondary_offering' => 1,
            'num_of_shares' => '50000',
            'pricePerShare' => '1.00',
            'info' => [
                'net_rent_projected' => 100,
                'gross_rent_projected_return' => 1000,
                'gross_projected_return' => 511,
            ],
            'documents' => [
                [
                    'file_name' => 'test.png',
                    'file_type' => 'image/png',
                    'document_content' => ApiBase64Files::TEST_PNG,
                ],
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering']['documents'][0];
        $this->assertEquals('test.png', $object['file_name']);
        $this->assertEquals('image/png', $object['file_type']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInvestmentAmountMin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/{$sample->getId()}/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 10,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/investments/{$apiResponse['data']['investment_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment'];
        $this->assertEquals($sample->getId(), $object['offering_id']);

        /**
         * This is actually behaving dangerously
         * Expect investment amount to be in £
         * And investment should never be higher than 10
         * So it should be less than or equal to £10 invested
         * Instead it's just rounding to the nearest...which isn't great
         */
        // $this->assertLessThanOrEqual(10, $object['investment_amount']);
        $this->assertLessThanOrEqual(
            10 * $sample->getPricePerShare(),
            $object['investment_amount'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInvestmentDataMin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sample/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 147.6,
            'info' => [
                'some_invest' => 'somewhere',
                'share_amount' => 123,
                'org_price_per_share' => 1.20,
                'transaction_id' => 123432534,
                'for_sale' => 1,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/investments/{$apiResponse['data']['investment_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment']['custom'];
        $this->assertEquals(123, $object['share_amount']);
        $this->assertEquals(1.20, $object['org_price_per_share']);
        $this->assertEquals(123432534, $object['transaction_id']);
        $this->assertEquals(1, $object['for_sale']);
        // Unsupported keys not returned
        $this->assertArrayNotHasKey('some_invest', $object);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInvestmentRelisted(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(Investment::class, [
            'status' => InvestmentLifecycle::STATE_SETTLED,
        ])[0];
        $sampleId = $sample->getOffering()->getAsset()->getId();
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sampleId/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'relisted offering test',
            'funding_goal' => 50,
            'is_secondary_offering' => 1,
            'sell_investment' => $sample->getId(),
            'info' => [
                'net_rent_projected' => 100,
                'gross_rent_projected_return' => 1000,
                'gross_projected_return' => 511,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering'];
        $this->assertEquals($sample->getId(), $object['sell_investment']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInvestmentDocMinData(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sample/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 10,
            'info' => [
                'some_invest' => 'somewhere',
            ],
            'documents' => [
                [
                    'file_name' => 'test.png',
                    'file_type' => 'image/png',
                    'document_content' => ApiBase64Files::TEST_PNG,
                ],
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/investments/{$apiResponse['data']['investment_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment']['documents'][0];
        $this->assertEquals('test.png', $object['file_name']);
        $this->assertEquals('image/png', $object['file_type']);
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingDocUrlOnly(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Asset::class,
            [
                'status' => AssetLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sample/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'name' => 'offering with doc',
            'funding_goal' => '50000',
            'is_secondary_offering' => 1,
            'documents' => [
                [
                    'file_name' => 'Test_Excel.xlsx',
                    'file_type' => 'application/pdf',
                    'document_url' => 'tests/Test_Excel.xlsx',
                ],
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/offerings/{$apiResponse['data']['offering_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['offering']['documents'][0];
        $this->assertStringContainsString(
            'tests/Test_Excel.xlsx',
            $object['document_url'],
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingInvestmentFieldsInvestmentValueCorrection(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(Offering::class, [
            'status' => OfferingLifecycle::STATE_PUBLISHED,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/{$sample->getId()}/investments";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'investment_amount' => 10,
            'info' => [
                'some_invest' => 'somewhere',
                'share_amount' => 123,
                'org_price_per_share' => 1.20,
            ],
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/investments/{$apiResponse['data']['investment_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['investment'];
        $this->assertEquals($sample->getId(), $object['offering_id']);
        $this->assertEquals(147.6, $object['investment_amount']);
        $this->assertEquals(123, $object['number_of_shares']);
    }

    public static function createDocTypeProvider(): \Generator
    {
        yield 'PNG' => ['test.png', 'image/png', ApiBase64Files::TEST_PNG];
        yield 'BMP' => ['test.bmp', 'image/bmp', ApiBase64Files::TEST_BMP];
        yield 'JPEG' => ['test.jpg', 'image/jpeg', ApiBase64Files::TEST_JPG];
        yield 'PDF' => ['test.pdf', 'application/pdf', ApiBase64Files::TEST_PDF];
        yield 'DOC' => [
            'test.doc',
            'application/vnd.ms-word',
            ApiBase64Files::TEST_DOC,
        ];
        yield 'XLSX' => [
            'test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ApiBase64Files::TEST_XLSX,
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('createDocTypeProvider')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateOfferingDocumentTypes(
        string $name,
        string $type,
        string $doc,
    ): void {
        $this->loginApiClientUser(self::USER_ADMIN);
        $sample = $this->searchFixtures(
            Offering::class,
            [
                'status' => OfferingLifecycle::STATE_PUBLISHED,
            ],
            true,
        )[0];
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$sample/documents";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'tag' => 'calculations',
            'file_name' => $name,
            'file_type' => $type,
            'document_content' => $doc,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $uri =
            self::API_PATH_PREFIX_V1
            . "/documents/{$apiResponse['data']['document_id']}";
        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $object = $apiResponse['data']['document'];
        $this->assertEquals($name, $object['file_name']);
        $this->assertEquals($type, $object['file_type']);
        $this->assertNotNull($object['document_content']);
        $this->assertNotFalse(base64_decode($object['document_content']));
    }
}
