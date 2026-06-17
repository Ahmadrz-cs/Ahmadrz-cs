<?php

namespace App\Service\Mangopay;

use MangoPay\MangoPayApi;

final class MangopayClientFactory
{
    // We use mTLS which has a slightly different sandbox endpoint from the standard https://api.sandbox.mangopay.com
    public const string DEFAULT_MTLS_ENDPOINT_URL = 'https://api-mtls.sandbox.mangopay.com';

    public static function createApiClient(
        string $clientId,
        string $clientPassword,
        string $temporaryFolder,
        string $mangopayProdUrl,
        string $mtlsCertB64,
        string $mtlsKeyB64,
        string $mangopayEnvironment = 'sandbox',
    ): MangoPayApi {
        $mangopayApi = new MangoPayApi();
        $mangopayApi->Config->ClientId = $clientId;
        $mangopayApi->Config->ClientPassword = $clientPassword;
        $mangopayApi->Config->TemporaryFolder = $temporaryFolder;
        if ($mangopayEnvironment == 'prod') {
            $mangopayApi->Config->BaseUrl = $mangopayProdUrl;
        } else {
            $mangopayApi->Config->BaseUrl = self::DEFAULT_MTLS_ENDPOINT_URL;
        }

        // Mutual TLS config
        $mangopayApi->Config->ClientCertificateString = $mtlsCertB64;
        $mangopayApi->Config->ClientCertificateKeyString = $mtlsKeyB64;

        return $mangopayApi;
    }
}
