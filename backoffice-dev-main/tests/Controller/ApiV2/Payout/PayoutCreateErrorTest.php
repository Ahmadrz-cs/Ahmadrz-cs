<?php

namespace App\Tests\Controller\ApiV2\Payout;

use App\Test\FixtureWebTestCase;
use DateTime;
use PHPUnit\Framework\Attributes\RequiresEnvironmentVariable;
use Symfony\Component\HttpFoundation\Response;

#[RequiresEnvironmentVariable('testApiV2', '1')]
class PayoutCreateErrorTest extends FixtureWebTestCase
{
    /**
     * @psalm-return \Generator<string, array{0: string}, mixed, void>
     */
    public static function unsupportedMediaTypeProvider(): \Generator
    {
        yield 'form-data' => ['multipart/form-data'];
        yield 'text' => ['text/plain'];
        yield 'javascript' => ['application/javascript'];
        yield 'html' => ['text/html'];
    }

    /**
     * @psalm-return \Generator<'json'|'xml', array{0: 'application/json'|'application/xml'}, mixed, void>
     */
    public static function supportedMediaTypeProvider(): \Generator
    {
        yield 'json' => ['application/json'];
        yield 'xml' => ['application/xml'];
    }

    public function testCreatePayoutFieldsRequiredMissing(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/payouts';
        $content = json_encode([
            'type' => 'dividend',
        ]);
        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->client->request('POST', $uri, [], [], $headers, $content);
        // $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Not yet implemented, so method not allowed
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    // public function testCreatePayoutFieldsInvalid(): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts';
    //     $content = json_encode([
    //         'investmentId' => '@?!',
    //         'amount' => 'invalid',
    //         'dueDate' => 42
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => 'application/json'
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
    // #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedMediaTypeProvider')]
    // public function testCreatePayoutMediaTypeInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled'],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts';
    //     $date = new DateTime('first of this month');
    //     $content = json_encode([
    //         'investmentId' => $sample[0],
    //         'amount' => 218.70,
    //         'dueDate' => $date->format(DateTime::ATOM)
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    // }
    // #[\PHPUnit\Framework\Attributes\DataProvider('supportedMediaTypeProvider')]
    // public function testCreatePayoutFormatsInvalid($mediaType): void
    // {
    //     $this->loginApiClientUser(self::USER_ADMIN);
    //     $sample = $this->searchFixtures(
    //         \App\Entity\Investment::class,
    //         ["status" => 'settled'],
    //         true
    //     );
    //     $uri = self::API_PATH_PREFIX_V2 . '/payouts';
    //     $date = new DateTime('first of this month');
    //     $content = json_encode([
    //         'investmentId' => $sample[0],
    //         'amount' => 218.70,
    //         'dueDate' => $date->format(DateTime::ATOM)
    //     ]);
    //     $headers = [
    //         'CONTENT_TYPE' => $mediaType
    //     ];
    //     $this->client->request('POST', $uri, [], [], $headers, $content);
    //     $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    // }
}
