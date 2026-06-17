<?php

namespace App\Tests\Entity;

use App\Entity\Status;
use App\Entity\User;

class StatusTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $status = new Status();

        $this->assertNotNull($status);

        $this->assertNull($status->getId());
    }

    /*
     * testSetEmailValidatedOn
     */
    public function testSetEmailValidatedOn(): void
    {
        $status = new Status();
        $test_value = 'qtstest@qts.com';
        $this->assertNotNull($test_value);

        $status->setEmailValidatedOn($test_value);
        $this->assertEquals($test_value, $status->getEmailValidatedOn());
    }

    /*
     * testSetRegCompletedOn
     */
    public function testSetRegCompletedOn(): void
    {
        $status = new Status();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $status->setRegCompletedOn($test_value);
        $this->assertEquals($test_value, $status->getRegCompletedOn());
        $this->assertInstanceOf('DateTime', $status->getRegCompletedOn());
    }

    /*
     * testSetApprovedOn
     */
    public function testSetApprovedOn(): void
    {
        $status = new Status();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $status->setApprovedOn($test_value);
        $this->assertEquals($test_value, $status->getApprovedOn());
        $this->assertInstanceOf('DateTime', $status->getApprovedOn());
    }

    /*
     * testSetBlockedOn
     */
    public function testSetBlockedOn(): void
    {
        $status = new Status();
        $test_value = new \DateTime();
        $this->assertNotNull($test_value);

        $status->setBlockedOn($test_value);
        $this->assertEquals($test_value, $status->getBlockedOn());
        $this->assertInstanceOf('DateTime', $status->getBlockedOn());
    }

    /*
     * testSetIsBlocked
     */
    public function testSetIsBlocked(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setIsBlocked($test_value);
        $this->assertTrue($status->getIsBlocked());
    }

    public function testSetIsRegCompleted(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setIsRegCompleted($test_value);
        $this->assertTrue($status->getIsRegCompleted());
    }

    public function testIsEmailValidated(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setIsEmailValidated($test_value);
        $this->assertTrue($status->getIsEmailValidated());
    }

    /*
     * testSetIsKycApproved
     */
    public function testSetIsKycApproved(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setIsKycApproved($test_value);
        $this->assertTrue($status->getIsKycApproved());
    }

    /*
     * testSetKycStatusOn
     */
    public function testSetKycStatusOn(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setKycStatusOn($test_value);
        $this->assertTrue($status->getKycStatusOn());
    }

    /*
     * testSetAcctMgmtStatusOn
     */
    public function testSetAcctMgmtStatusOn(): void
    {
        $status = new Status();
        $test_value = true;
        $this->assertNotNull($test_value);

        $status->setAcctMgmtStatusOn($test_value);
        $this->assertTrue($status->getAcctMgmtStatusOn());
    }

    public function testSetUser(): void
    {
        $status = new Status();
        $testUser = new User();

        $this->assertNull($status->getUser());
        $status->setUser($testUser);

        $this->assertEquals($testUser, $status->getUser());
    }

    /*
     * public function testSetCreatedBy()
     * {
     * $company = new company();
     * $user = new User();
     *
     * $this->assertNull($company->getCreatedBy());
     * $company->setCreatedBy( $user );
     *
     * $this->assertEquals( $user, $company->getCreatedBy() );
     * }
     */
}
