<?php

namespace App\Tests\Controller\ApiV1\Offering\Email;

use App\Entity\Asset;
use App\Entity\Investment;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Entity\Lifecycle\InvestmentLifecycle;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\User;
use App\Test\MailcatcherTestCase;

class OfferingEmailTest extends MailcatcherTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testOfferingCreationSendMailAsRegUser(): void
    {
        $this->loginApiClientUser(self::USER_REGULAR);
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_REGULAR]);
        $sample = $this->searchFixtures(Asset::class, [
            'name' => 'Royal Way Gardens - Cambridge',
            'status' => AssetLifecycle::STATE_PUBLISHED,
        ])[0];
        $sampleId = $sample->getId();
        $sampleInvestment = $this->searchFixtures(Investment::class, [
            'user' => $user->getId(),
            'asset' => $sampleId,
            'status' => InvestmentLifecycle::STATE_SETTLED,
        ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sampleId/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            // minimum fields permitted to create offering
            'name' => 'offering email test',
            'funding_goal' => '50000',
            'sell_investment' => $sampleInvestment->getId(),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $offeringId = $apiResponse['data']['offering_id'];
        $this->markOfferingAsPaid($offeringId);

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);
        $this->assertEquals('Request to sell shares', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        // For user creation, the new offering author receives the email
        $this->assertContains('<' . self::USER_REGULAR . '>', $message->recipients);
        $this->assertStringContainsString('offering email test', $messageContent);
        $this->assertStringContainsString($sample->getName(), $messageContent);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testOfferingCreationSendMailAsAdmin(): void
    {
        $this->loginApiClientUser(self::USER_ADMIN);
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);
        $sample = $this->searchFixtures(Asset::class, [
            'name' => 'Royal Way Gardens - Cambridge',
            'status' => AssetLifecycle::STATE_PUBLISHED,
        ])[0];
        $sampleId = $sample->getId();
        // $sampleInvestment = $this->searchFixtures(Investment::class, [
        //     'user' => $user->getId(),
        //     'asset' => $sampleId,
        //     'status' => InvestmentLifecycle::STATE_SETTLED
        // ])[0];
        $uri = self::API_PATH_PREFIX_V1 . "/assets/$sampleId/offerings";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            // minimum fields permitted to create offering
            'name' => 'offering admin email test',
            'funding_goal' => '50000',
            'life_cycle_stage' => OfferingLifecycle::STATE_SUBMITTED,
            // 'sell_investment' => $sampleInvestment->getId(),
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();

        // Admins can bypass the relisting requirement for payment-outcome route
        // $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        // $offeringId = $apiResponse['data']['offering_id'];
        // $this->markOfferingAsPaid($offeringId);

        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);
        $this->assertEquals('New Offering created', $message->subject);
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        // For admin creation, the contact point receives the email
        $this->assertContains(
            '<' . $sample->getContactPoint()->getEmail() . '>',
            $message->recipients,
        );
        $this->assertStringContainsString('offering admin email test', $messageContent);
        $this->assertStringContainsString($sample->getName(), $messageContent);
    }

    private function markOfferingAsPaid(string $offeringId): void
    {
        $uri = self::API_PATH_PREFIX_V1 . "/offerings/$offeringId/payment-outcome";
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'success' => true,
            'verify' => false,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
    }
}
