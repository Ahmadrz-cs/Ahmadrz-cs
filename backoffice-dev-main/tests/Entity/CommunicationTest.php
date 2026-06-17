<?php

namespace App\Tests\Entity;

use App\Entity\Communication;
use App\Entity\User;

class CommunicationTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $communication = new Communication();

        $this->assertNotNull($communication);

        $this->assertNull($communication->getId());
    }

    public function testRecipient(): void
    {
        $communication = new Communication();

        $testRecipient = new User();

        $this->assertNull($communication->getRecipient());

        $communication->setRecipient($testRecipient);

        $this->assertEquals($testRecipient, $communication->getRecipient());
    }

    public function testSubject(): void
    {
        $communication = new Communication();

        $testSubject = 'Test Subject';

        $this->assertNull($communication->getSubject());

        $communication->setSubject($testSubject);

        $this->assertEquals($testSubject, $communication->getSubject());
    }

    public function testContent(): void
    {
        $communication = new Communication();

        $testContent = 'Test Subject';

        $this->assertNull($communication->getContent());

        $communication->setContent($testContent);

        $this->assertEquals($testContent, $communication->getContent());
    }

    public function testCreatedAt(): void
    {
        $communication = new Communication();

        $testDatetime = new \DateTime();

        $this->assertNull($communication->getCreatedAt());

        $communication->setCreatedAt($testDatetime);

        $this->assertEquals($testDatetime, $communication->getCreatedAt());

        $this->assertInstanceOf('DateTime', $communication->getCreatedAt());
    }

    public function testCreatedBy(): void
    {
        $communication = new Communication();

        $testUser = new User();

        $this->assertNull($communication->getCreatedBy());

        $communication->setCreatedBy($testUser);

        $this->assertEquals($testUser, $communication->getCreatedBy());
    }
}
