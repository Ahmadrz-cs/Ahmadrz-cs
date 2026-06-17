<?php

namespace App\Service\Mailchimp;

use MailchimpTransactional\ApiClient;

final class MailchimpClientFactory
{
    public static function createApiClient(string $apiKey): ApiClient
    {
        $mailchimpApi = new ApiClient();
        $mailchimpApi->setApiKey($apiKey);

        return $mailchimpApi;
    }
}
