<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 12/01/17
 * Time: 22:58
 */

namespace App\Tests\Entity\Lifecycle;

use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserLifecycleTest extends KernelTestCase
{
    /** @var \Symfony\Component\Workflow\WorkflowInterface $workflow */
    private $workflow;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->workflow = static::getContainer()->get('state_machine.user');
    }

    public function testInitialState(): void
    {
        $user = new User();
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
    }

    public function testUserStateChangeEmailVerification(): void
    {
        $user = new User();
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_EMAIL_VERIFICATION);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
    }

    public function testUserStateChangeRegistrationComplete(): void
    {
        $user = new User();
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_EMAIL_VERIFICATION);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_REGISTRATION_COMPLETE);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
    }

    public function testUserStateChangeApproval(): void
    {
        $user = new User();
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_NOT_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_EMAIL_VERIFICATION);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_REGISTRATION_COMPLETE);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
        $this->workflow->apply($user, UserLifecycle::TRANSITION_APPROVE);
        $this->assertEquals(UserLifecycle::STATE_APPROVED, $user->getLifecycleStatus());
    }
}
