<?php

namespace tests\AppBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicControllerTest extends WebTestCase
{
    public function testHealthCheck(): void
    {
        // This calls KernelTestCase::bootKernel(), and creates a
        // "client" that is acting as the browser
        $client = static::createClient();

        // Request a specific page
        $crawler = $client->request('GET', '/healthcheck');

        // Validate a successful response and some content
        $this->assertResponseIsSuccessful();
    }
}
