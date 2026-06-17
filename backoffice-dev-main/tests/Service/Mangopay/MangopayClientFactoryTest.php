<?php

namespace App\Tests\Service\Mangopay;

use App\Service\Mangopay\MangopayClientFactory;
use MangoPay\MangoPayApi;

final class MangopayClientFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateApiClientTypical(): void
    {
        $clientId = 'example_test_client_id';
        $clientPassword = bin2hex(openssl_random_pseudo_bytes(24));
        $temporaryFolder = '/example/temp/directory';
        // Should be the default Mangopay sandbox url
        // $mangopayUrl = 'https://api.sandbox.mangopay.com';
        // We use mTLS, so use a different endpoint with mtls
        $mangopayUrl = 'https://api-mtls.sandbox.mangopay.com';
        $testMtlsCertB64 = bin2hex(openssl_random_pseudo_bytes(24));
        $testMtlsKeyB64 = bin2hex(openssl_random_pseudo_bytes(24));

        $factory = new MangopayClientFactory();
        $apiClient = $factory->createApiClient(
            clientId: $clientId,
            clientPassword: $clientPassword,
            temporaryFolder: $temporaryFolder,
            mtlsCertB64: $testMtlsCertB64,
            mtlsKeyB64: $testMtlsKeyB64,
            mangopayProdUrl: '', // The prodUrl field is ignored when in sandbox mode
            mangopayEnvironment: 'sandbox',
        );

        $this->assertInstanceOf(MangoPayApi::class, $apiClient);
        $this->assertSame($clientId, $apiClient->Config->ClientId);
        $this->assertSame($clientPassword, $apiClient->Config->ClientPassword);
        $this->assertSame($temporaryFolder, $apiClient->Config->TemporaryFolder);
        $this->assertSame($mangopayUrl, $apiClient->Config->BaseUrl);
        $this->assertSame(
            $testMtlsCertB64,
            $apiClient->Config->ClientCertificateString,
        );
        $this->assertSame(
            $testMtlsKeyB64,
            $apiClient->Config->ClientCertificateKeyString,
        );
    }

    public function testCreateApiClientProdMode(): void
    {
        $clientId = 'example_test_client_id';
        $clientPassword = bin2hex(openssl_random_pseudo_bytes(24));
        $temporaryFolder = '/example/temp/directory';
        $mangopayUrl = 'https://example.com';
        $testMtlsCertB64 = bin2hex(openssl_random_pseudo_bytes(24));
        $testMtlsKeyB64 = bin2hex(openssl_random_pseudo_bytes(24));

        $factory = new MangopayClientFactory();
        $apiClient = $factory->createApiClient(
            clientId: $clientId,
            clientPassword: $clientPassword,
            temporaryFolder: $temporaryFolder,
            mtlsCertB64: $testMtlsCertB64,
            mtlsKeyB64: $testMtlsKeyB64,
            mangopayProdUrl: $mangopayUrl,
            mangopayEnvironment: 'prod',
        );

        $this->assertInstanceOf(MangoPayApi::class, $apiClient);
        $this->assertSame($clientId, $apiClient->Config->ClientId);
        $this->assertSame($clientPassword, $apiClient->Config->ClientPassword);
        $this->assertSame($temporaryFolder, $apiClient->Config->TemporaryFolder);
        // Base url should be configured to whatever was passed as the prodUrl in prod mode
        $this->assertSame($mangopayUrl, $apiClient->Config->BaseUrl);
        $this->assertSame(
            $testMtlsCertB64,
            $apiClient->Config->ClientCertificateString,
        );
        $this->assertSame(
            $testMtlsKeyB64,
            $apiClient->Config->ClientCertificateKeyString,
        );
    }
}
