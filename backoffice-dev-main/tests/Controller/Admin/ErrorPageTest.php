<?php

namespace App\Tests\Controller\Admin;

use App\Test\FixtureWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ErrorPageTest extends FixtureWebTestCase
{
    public function testErrorPageLinks(): void
    {
        $this->loginWebClient(self::USER_SUPER_ADMIN);

        // Request the error page preview route (only available in test and dev environments)
        $crawler = $this->client->request('GET', '/_error/500');

        // Validate a successful response and some content
        $this->assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        $homepageLink = $crawler->selectLink('Go back to homepage')->attr('href');
        $this->assertEquals('/', $homepageLink);
    }
}
