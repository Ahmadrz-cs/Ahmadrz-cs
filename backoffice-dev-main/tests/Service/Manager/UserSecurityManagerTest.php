<?php

namespace App\Tests\Service\Manager;

use App\Entity\User;
use App\Service\Manager\UserSecurityManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserSecurityManagerTest extends KernelTestCase
{
    private UserSecurityManager $service;

    public function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(UserSecurityManager::class);
    }

    /**
     * Only include tests that are specifically doing something a 3rd party bundle isn't doing
     * So this excludes
     * - Any database interaction (handled by FosUserBUndle and Doctrine)
     * - Any interaction with 2FA TOTP codes (handled by Scheb two-factor bundle)
     */
    public function testServiceExists(): void
    {
        $this->assertInstanceOf(UserSecurityManager::class, $this->service);
    }

    public function testGetUserMfaConfig(): void
    {
        $expected = ['email', 'totp'];
        $actual = array_keys($this->service->getUserMfaConfig(new User()));
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function testTotpCodesEmptyFalse(): void
    {
        /**
         * Check that if no codes provided, returns false
         */
        $this->assertFalse($this->service->checkTotpCodes(new User(), []));
    }

    public function testEnableMfaInvalidFactor(): void
    {
        /**
         * Check that if an invalid auth factor given, returns false
         */
        $this->assertFalse($this->service->enableMfa(new User(), 'biscuit'));
    }

    public function testDisbleMfaInvalidFactor(): void
    {
        /**
         * Check that if an invalid auth factor given, returns false
         */
        $this->assertFalse($this->service->disableMfa(new User(), 'biscuit'));
    }

    public function testSetMfaPreferenceInvalidFactor(): void
    {
        /**
         * Check that if an invalid auth factor given, returns false
         */
        $this->assertFalse($this->service->setMfaPreference(new User(), 'biscuit'));
    }
}
