<?php

namespace App\Tests\Controller\ApiV1\User\Email;

use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\User;
use App\Test\MailcatcherTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

class EmailUserPostResponseTest extends MailcatcherTestCase
{
    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserFieldsFrontend(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $userFields = [
            'email' => 'abc' . self::USER_REGULAR,
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
            'username' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
            'term_service_accepted' => 1,
            'gdpr_accepted' => 1,
            'info' => [
                'income_range' => '20,000 - 40,000',
                'referral' => 'testing referral',
                'cxb_worth_investor' => true,
                'cxb_sophisticated_investor' => false,
                'cxb_restricted_investor' => false,
                'cxb_ltd_company_investor' => false,
                'always_go_up' => 'Yes',
                'income_every_month' => '3,000',
                'never_exit' => 'No',
                'words_of_own' => 'top yielder application statement',
                'organization_name' => 'Hall Investments',
                'position' => 'CFO',
                'corporate_investor' => true,
                'company_beneficial_owners' => 'Franklin Hall',
                'company_directors' => 'Franklin Hall',
                'registration_country' => 'GB',
                'business_nature' => 'Investment fund',
                'company_registered_address_1' => '14 Portland Road',
                'company_registered_address_2' => 'Wells Basin',
                'company_registered_address_3' => 'London',
                'telephone' => '000 700 0000',
                'postcode' => '12854',
                'building_name' => 'Halls Place',
                'company_website' => 'http://www.example.com',
                'operating_address' => '14 Portland Road, Wells Basin, London',
                'operating_postcode' => '12854',
                'registration_number' => '8855664421',
            ],
        ];
        $content = json_encode($userFields);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Check user created with relevant fields
        /** @var User $newUser */
        $newUser = $this->searchFixtures(User::class, [
            'username' => 'abc' . self::USER_REGULAR,
        ])[0];
        $this->assertEquals($userFields['email'], $newUser->getUsername());
        $this->assertEquals($userFields['email'], $newUser->getEmail());
        $this->assertEquals($userFields['first_name'], $newUser->getFirstname());
        $this->assertEquals($userFields['last_name'], $newUser->getLastname());
        $this->assertEquals(
            $userFields['term_service_accepted'],
            $newUser->isTermServiceAccepted(),
        );
        $this->assertEquals(
            $userFields['term_service_accepted'],
            $newUser->isGDPRAccepted(),
        );
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $newUser->getLifecycleStatus(),
        );
        $this->assertEquals(OB_STEP_CONSTANT::STEP1_INT, $newUser->getOBStep());

        $this->assertEquals(
            $userFields['info']['income_range'],
            $newUser->getIncomeRange(),
        );
        $this->assertEquals(
            $userFields['info']['referral'],
            $newUser->getReferralCode(),
        );
        $this->assertEquals(
            $userFields['info']['cxb_worth_investor'],
            $newUser->getInvestor()->getCxbWorthInvestor(),
        );
        $this->assertEquals(
            $userFields['info']['cxb_sophisticated_investor'],
            $newUser->getInvestor()->getCxbSophisticatedInvestor(),
        );
        $this->assertEquals(
            $userFields['info']['cxb_restricted_investor'],
            $newUser->getInvestor()->getCxbRestrictedUser(),
        );
        $this->assertEquals(
            $userFields['info']['cxb_ltd_company_investor'],
            $newUser->getInvestor()->getCxbLtdCompInvestor(),
        );
        $this->assertEquals(
            $userFields['info']['always_go_up'],
            $newUser->getInvestor()->getAlwaysGoUp(),
        );
        $this->assertEquals(
            $userFields['info']['income_every_month'],
            $newUser->getInvestor()->getIncomeEveryMonth(),
        );
        $this->assertEquals(
            $userFields['info']['never_exit'],
            $newUser->getInvestor()->getNeverExit(),
        );
        $this->assertEquals(
            $userFields['info']['words_of_own'],
            $newUser->getInvestor()->getWordsOfOwn(),
        );

        $this->assertEquals(
            $userFields['info']['organization_name'],
            $newUser->getCompany()->getName(),
        );
        $this->assertEquals(
            $userFields['info']['position'],
            $newUser->getCompany()->getPosition(),
        );
        $this->assertEquals(
            $userFields['info']['corporate_investor'],
            $newUser->getInvestor()->getCorporateInvestor(),
        );
        $this->assertEquals(
            $userFields['info']['company_beneficial_owners'],
            $newUser->getCompany()->getBeneficialOwners(),
        );
        $this->assertEquals(
            $userFields['info']['company_directors'],
            $newUser->getCompany()->getDirectors(),
        );
        $this->assertEquals(
            $userFields['info']['registration_country'],
            $newUser->getCompany()->getRegCountry(),
        );
        $this->assertEquals(
            $userFields['info']['business_nature'],
            $newUser->getCompany()->getBusinessNature(),
        );
        $this->assertEquals(
            $userFields['info']['company_registered_address_1'],
            $newUser->getCompany()->getRegAddress1(),
        );
        $this->assertEquals(
            $userFields['info']['company_registered_address_2'],
            $newUser->getCompany()->getRegAddress2(),
        );
        $this->assertEquals(
            $userFields['info']['company_registered_address_3'],
            $newUser->getCompany()->getRegAddress3(),
        );
        $this->assertEquals(
            $userFields['info']['telephone'],
            $newUser->getCompany()->getTelephone(),
        );
        $this->assertEquals(
            $userFields['info']['postcode'],
            $newUser->getCompany()->getPostCode(),
        );
        $this->assertEquals(
            $userFields['info']['building_name'],
            $newUser->getCompany()->getBuildingName(),
        );
        $this->assertEquals(
            $userFields['info']['company_website'],
            $newUser->getCompany()->getCompanyWebsite(),
        );
        $this->assertEquals(
            $userFields['info']['operating_address'],
            $newUser->getCompany()->getOperatingAddress(),
        );
        $this->assertEquals(
            $userFields['info']['operating_postcode'],
            $newUser->getCompany()->getOperatingPostCode(),
        );
        $this->assertEquals(
            $userFields['info']['registration_number'],
            $newUser->getCompany()->getRegistrationNumber(),
        );

        // Check an email confirmation has been sent
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);
        $this->assertEquals(
            'Congratulations on becoming a Yielder !',
            $message->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . 'abc' . self::USER_REGULAR . '>',
            $message->recipients,
        );
        $this->assertStringContainsString($userFields['first_name'], $messageContent);
        $this->assertStringContainsString(
            'Please follow the link below to verify your email address and complete your profile',
            $messageContent,
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserFieldsMinimum(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $userFields = [
            'email' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
            'url' => 'http://example.com/verifyme',
        ];
        $content = json_encode($userFields);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        /** @var User $newUser */
        $newUser = $this->searchFixtures(User::class, [
            'username' => 'abc' . self::USER_REGULAR,
        ])[0];
        $this->assertEquals($userFields['email'], $newUser->getUsername());
        $this->assertEquals($userFields['email'], $newUser->getEmail());
        $this->assertEmpty($newUser->getFirstname());
        $this->assertEmpty($newUser->getLastname());

        // new user can login
        $this->sendLoginRequest($userFields['email']);
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertNotEmpty($response['access_token']);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserResendVerificationEmail()
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $userFields = [
            'email' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
        ];
        $content = json_encode($userFields);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Should have 2 emails on account creation
        // One for user, one for admin
        $this->assertCount(2, $this->getMessages());

        $this->loginApiClientUser('abc' . self::USER_REGULAR);
        $uri = self::API_PATH_PREFIX_V1 . '/self/resendVerificationEmail';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'url' => 'http://example.com/verifyme',
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        // Check an email confirmation has been sent
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id);
        $this->assertEquals(
            'Congratulations on becoming a Yielder !',
            $message->subject,
        );
        $this->assertEquals('<noreply@yielders.co.uk>', $message->sender);
        $this->assertContains(
            '<' . 'abc' . self::USER_REGULAR . '>',
            $message->recipients,
        );
        $this->assertStringContainsString($userFields['first_name'], $messageContent);
        $this->assertStringContainsString(
            'Please follow the link below to verify your email address and complete your profile',
            $messageContent,
        );

        // After a resend, should have 4 total (previous 2 resent)
        $this->assertCount(4, $this->getMessages());
        $oldMessage = $this->getMessages()[2];
        $this->assertEquals($message->subject, $oldMessage->subject);
        $this->assertEquals($message->sender, $oldMessage->sender);
        $this->assertEquals($message->recipients, $oldMessage->recipients);
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    #[\PHPUnit\Framework\Attributes\Group('response')]
    public function testCreateUserAndVerifyEmail(): void
    {
        $uri = self::API_PATH_PREFIX_V1 . '/public/users';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $userFields = [
            'email' => 'abc' . self::USER_REGULAR,
            'password' => self::USER_PASSWORD_STANDARD,
            'url' => 'http://example.com/verifyme',
            'first_name' => 'Franklin',
            'last_name' => 'Hall',
        ];
        $content = json_encode($userFields);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        /** @var User $newUser */
        $newUser = $this->searchFixtures(User::class, [
            'username' => 'abc' . self::USER_REGULAR,
        ])[0];
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $newUser->getLifeCycleStatus(),
        );
        $this->assertEquals(OB_STEP_CONSTANT::STEP1_INT, $newUser->getOBStep());

        // Extract the verification url from the confirmation email
        // Example url https://dev-front.yielderverse.co.uk/verify-email?expires=1655920854&id=27&signature=oe5xZu6%2FTDb%2Br0yhkBMDRLolGxo433n18mxPFTUoU5c%3D&token=I4GFYAfhI1PCzKby42YCwrhT9Q2TCIytXSJzKbr1tcA%3D
        $message = $this->getMessages()[0];
        $messageContent = $this->getMessageInFormat($message->id, 'html');
        $crawler = new Crawler($messageContent);
        $verificationUrl = $crawler
            ->filterXPath("//a[text()='Verify Email']")
            ->attr('href');
        $this->assertNotNull($newUser->getPasswordRequestedAt());

        $uri = self::API_PATH_PREFIX_V1 . '/public/verifyEmail';
        $headers = ['CONTENT_TYPE' => 'application/json'];
        $content = json_encode([
            'id' => $newUser->getId(),
            'signedUrl' => $verificationUrl,
        ]);
        $this->client->request('POST', $uri, [], [], $headers, $content);
        $this->assertResponseIsSuccessful();
        $apiResponse = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(Response::HTTP_OK, $apiResponse['status']);
        $this->assertEquals('success', $apiResponse['outcome']);

        $newUser = $this->searchFixtures(User::class, [
            'username' => 'abc' . self::USER_REGULAR,
        ])[0];
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $newUser->getLifeCycleStatus(),
        );
        $this->assertEquals(OB_STEP_CONSTANT::STEP2_INT, $newUser->getOBStep());
    }
}
