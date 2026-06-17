<?php

namespace App\Tests\Controller\ApiV1\Self\Email;

use App\Test\MailcatcherTestCase;
use Symfony\Component\HttpFoundation\Response;

class EmailSelfPatchResponseTest extends MailcatcherTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testUpdateSelfFieldsTopYielder(): void
    {
        $fieldsToChange = [
            'info' => [
                'words_of_your_own' => 'TOp yielder application contents',
            ],
            'documents' => [
                0 => [
                    'file_name' => 'someProofOfFunds.png',
                    'file_type' => 'text/html',
                    'document_content' => 'eyUgZXh0ZW5kcyAnQXBwQnVuZGx',
                    'tag' => 'proof_of_funds',
                ],
            ],
        ];
        $this->loginApiClientUser(self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode($fieldsToChange);
        $this->client->request('PATCH', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);

        $this->client->request('GET', $uri);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Check an email confirmation has been sent
        $message = $this->getMessages()[0];
        $this->assertEquals('Top Yielders Application Received', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . self::USER_REGULAR . '>', $message->recipients);
    }
}
