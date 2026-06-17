<?php

namespace App\Test;

/**
 * @group email
 *
 * Automaticaly sets tests to phpunit group email
 *
 * Consider switching to mailer component for testing
 * https://symfony.com/blog/new-in-symfony-4-4-phpunit-assertions-for-email-messages
 */
abstract class MailcatcherTestCase extends ExternalServiceWebTestCase
{
    protected const ADMIN_EMAIL_ADDRESS = 'adminteam@yielders.co.uk';

    protected \GuzzleHttp\Client $mailcatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailcatcher = new \GuzzleHttp\Client([
            'base_uri' => $_ENV['MAILCATCHER_URL'] ?: 'http://127.0.0.1:1080',
        ]);
        $this->cleanMessages();
    }

    public function cleanMessages(): void
    {
        $this->mailcatcher->delete('/messages');
    }

    public function getMessages(): mixed
    {
        $jsonResponse = $this->mailcatcher->get('/messages');
        return json_decode($jsonResponse->getBody());
    }

    public function getMessageInFormat(int $id, string $format = 'plain'): string
    {
        if (!in_array($format, ['plain', 'json', 'html'])) {
            $format = 'plain';
        }
        $response = $this->mailcatcher->get("/messages/{$id}.{$format}");
        return $response->getBody()->getContents();
    }

    public function getEmailCount(): int
    {
        $messages = $this->getMessages();
        if (empty($messages)) {
            return 0;
        }
        return count($messages);
    }
}
