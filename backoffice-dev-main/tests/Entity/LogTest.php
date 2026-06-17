<?php

namespace App\Tests\Entity;

use App\Entity\Log;
use App\Entity\User;

class LogTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $log = new Log();

        $this->assertNotNull($log);

        $this->assertNull($log->getId());
    }

    public function testKey(): void
    {
        $log = new Log();

        $testKey = 'Test';

        $this->assertNull($log->getKey());

        $log->setKey($testKey);

        $this->assertEquals($testKey, $log->getKey());
    }

    public function testOldValue(): void
    {
        $log = new Log();

        $testValue = 'Test';

        $this->assertNull($log->getOldValue());

        $log->setOldValue($testValue);

        $this->assertEquals($testValue, $log->getOldValue());
    }

    public function testNewValue(): void
    {
        $log = new Log();

        $testValue = 'Test';

        $this->assertNull($log->getNewValue());

        $log->setNewValue($testValue);

        $this->assertEquals($testValue, $log->getNewValue());
    }

    public function testCreatedOn(): void
    {
        $log = new Log();

        $testDatetime = new \DateTime();

        $this->assertNull($log->getCreatedAt());

        $log->setCreatedAt($testDatetime);

        $this->assertEquals($testDatetime, $log->getCreatedAt());

        $this->assertInstanceOf('DateTime', $log->getCreatedAt());
    }

    public function testCreatedBy(): void
    {
        $log = new Log();

        $testUser = new User();

        $this->assertNull($log->getCreatedBy());

        $log->setCreatedBy($testUser);

        $this->assertEquals($testUser, $log->getCreatedBy());
    }
}
