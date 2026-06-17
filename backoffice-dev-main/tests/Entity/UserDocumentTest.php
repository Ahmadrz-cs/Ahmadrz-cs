<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserDocument;

//use App\Entity\Document;

class UserDocumentTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $userDocument = new UserDocument();

        $this->assertNotNull($userDocument);

        $this->assertNull($userDocument->getId());
    }

    /*
     * testSetUser
     */
    public function testSetUser(): void
    {
        $UserDocument = new UserDocument();
        $test_value = 'user1';
        $this->assertNotNull($test_value);

        $UserDocument->setUser($test_value);
        $this->assertEquals($test_value, $UserDocument->getUser());
    }
}
