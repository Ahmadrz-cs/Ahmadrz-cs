<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthcheckControllerTest extends WebTestCase
{
    public function testHealthcheck(): void
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
