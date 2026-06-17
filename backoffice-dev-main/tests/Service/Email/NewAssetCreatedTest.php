<?php

namespace App\Tests\Service\Email;

use App\Entity\Asset;
use App\Entity\Communication;
use App\Service\MailerService;
use App\Test\MailcatcherTestCase;

class NewAssetCreatedTest extends MailcatcherTestCase
{
    /**
     * Suspect this email is also not in use as it mirrors the user approval email contents...
     */

    /** @var MailerService $service */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MailerService::class);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testEmailAssetCreate(): void
    {
        // the count() method weirdly returns an array in the format [0 => [1 => $actualCount]]
        // Maybe using it wrong, but all type hints indicate it should just be a flat int
        $logEntriesBefore = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);

        /** @var Asset $asset */
        $asset = $this->entityManager->getRepository(Asset::class)->find(1);
        $user = $asset->getContactPoint();

        $sent = $this->service->sendMail($user, MailerService::TYPE_ASSET_NEW, [
            'asset' => $asset,
        ]);
        $this->assertEquals(1, $sent);
        $this->assertEquals(2, $this->getEmailCount());

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);

        $this->assertEquals('New Asset Created', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains('<' . $user->getEmail() . '>', $message->recipients);

        $this->assertStringContainsString('Asset has been created', $messageContent);
        $this->assertStringContainsString($user->getFullName(), $messageContent);
        $this->assertStringContainsString($asset->getName(), $messageContent);

        $logEntriesAfter = $this->entityManager
            ->getRepository(Communication::class)
            ->count([]);
        $this->assertEquals(2, $logEntriesAfter - $logEntriesBefore);

        /** @var Communication $communicationRecord */
        $communicationRecord = $this->entityManager
            ->getRepository(Communication::class)
            ->findOneBy([
                'subject' => 'New Asset Created',
                'user' => $user->getId(),
            ]);
        $this->assertEquals($user->getEmail(), $communicationRecord->getRecipient());
        $this->assertEquals('New Asset Created', $communicationRecord->getSubject());

        // Check admin also receives the email
        $message = $this->getMessages()[1];
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . self::ADMIN_EMAIL_ADDRESS . '>',
            $message->recipients,
        );
        $this->assertEquals('[Admin] New Asset Created', $message->subject);
    }
}
