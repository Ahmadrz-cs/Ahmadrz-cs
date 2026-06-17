<?php

namespace App\Tests\Entity;

use App\Entity\UserLog;

class UserLogTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $userLog = new UserLog();

        $this->assertNotNull($userLog);

        $this->assertNull($userLog->getId());
    }

    public function testSetUser(): void
    {
        $userLog = new UserLog();
        $test_value = 'user1';
        $this->assertNotNull($test_value);

        $userLog->setUser($test_value);
        $this->assertEquals($test_value, $userLog->getUser());
    }
}
