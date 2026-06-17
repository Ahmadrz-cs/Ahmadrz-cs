<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Entity\UserCustomFields;

class CustomFieldTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $customField = new UserCustomFields();

        $this->assertNotNull($customField);

        $this->assertNull($customField->getId());
    }

    /*
     * testSetKey
     */
    public function testSetFieldKey(): void
    {
        $customField = new UserCustomFields();
        $test_value = 'key1';
        $this->assertNotNull($test_value);

        $customField->setFieldKey($test_value);
        $this->assertEquals($test_value, $customField->getFieldKey());
    }

    /*
     * testSetFieldValue
     */
    public function testSetFieldValue(): void
    {
        $customField = new UserCustomFields();
        $test_value = 'value1';
        $this->assertNotNull($test_value);

        $customField->setFieldValue($test_value);
        $this->assertEquals($test_value, $customField->getFieldValue());
    }

    /*
     * testSetCreatedAt
     */
    public function testSetCreatedAt(): void
    {
        $customField = new UserCustomFields();
        $test_value = new \DateTime();
        $this->assertNull($customField->getCreatedAt());

        $customField->setCreatedAt($test_value);
        $this->assertEquals($test_value, $customField->getCreatedAt());
        $this->assertInstanceOf('DateTime', $customField->getCreatedAt());
    }

    /*
     * testSetUser
     */
    public function testSetUser(): void
    {
        $customField = new UserCustomFields();
        $user = new User();
        $this->assertNull($customField->getUser());

        $customField->setUser($user);
        $this->assertEquals($user, $customField->getUser());
    }

    /*
     * testSetCreatedBy
     */
    public function testSetCreatedBy(): void
    {
        $customField = new UserCustomFields();
        $user = new User();
        $this->assertNull($customField->getCreatedBy());

        $customField->setCreatedBy($user);
        $this->assertEquals($user, $customField->getCreatedBy());
    }
}
