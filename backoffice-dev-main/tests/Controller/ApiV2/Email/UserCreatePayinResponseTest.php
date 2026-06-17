<?php

namespace App\Tests\Controller\ApiV2\Email;

use App\Entity\User;
use App\Test\MailcatcherTestCase;
use App\Tests\Controller\ApiV2\ApiResponseFields;
use Symfony\Component\HttpFoundation\Response;

class UserCreatePayinResponseTest extends MailcatcherTestCase
{
    /**
     * @psalm-return \Generator<'notification toggle off'|'notification toggle on', array{0: bool}, mixed, void>
     */
    public static function bankwireConfirmationMail(): \Generator
    {
        yield 'notification toggle off' => [false];
        yield 'notification toggle on' => [true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('bankwireConfirmationMail')]
    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testCreateUserBankwirePayin($notification): void
    {
        $this->cleanMessages();

        $this->loginApiClientUser(self::USER_ADMIN);
        $uri = self::API_PATH_PREFIX_V2 . '/users/1/payin';
        if ($notification) {
            $content = json_encode([
                'amount' => 100,
                'notification' => true,
            ]);
        } else {
            $content = json_encode([
                'amount' => 100,
            ]);
        }

        $headers = [
            'CONTENT_TYPE' => 'application/json',
        ];
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);

        $expectedFields = ApiResponseFields::BANKWIRE_PAYIN_STANDARD;
        $actualFields = array_keys($apiResponse);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        $expectedFields = ApiResponseFields::BANKWIRE_PAYIN_BANK_ACCOUNT;
        $actualFields = array_keys($apiResponse['bankAccount']);
        $this->assertEqualsCanonicalizing($expectedFields, $actualFields);

        if ($notification) {
            // One for admin, one for the user
            $this->assertEquals(2, $this->getEmailCount());

            /** @var User $recipient */
            $recipient = $this->entityManager->getRepository(User::class)->find(1);

            // Check email contents sent to the user (user always get it before the admin)
            $message = $this->getMessages()[0];
            $messageContent = $this->getMessageInFormat($message->id);
            $this->assertEquals('Bankwire Transfer Details', $message->subject);
            $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
            $this->assertContains(
                '<' . $recipient->getEmail() . '>',
                $message->recipients,
            );
            $this->assertStringContainsString(
                'Thank you for indicating that you will be making a bank transfer',
                $messageContent,
            );
            $this->assertStringContainsString(
                $recipient->getFirstname(),
                $messageContent,
            );
        } else {
            $this->assertEquals(0, $this->getEmailCount());
        }
    }
}
