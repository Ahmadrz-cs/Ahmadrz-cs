<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 08/12/16
 * Time: 12:15
 */

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MangoPay;
use App\Test\FixtureTestCase;

class MangoPayGetUser extends FixtureTestCase
{
    private MangoPay $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(MangoPay::class);
    }

    public function testMissingMangoId(): void
    {
        $mangoPayUser = $this->service->getUser(null);
        $this->assertEquals($mangoPayUser, 'MangoPay Id is missing');
    }

    public function testNotFoundUser(): void
    {
        //try and find a user 1 which doesn't exist on mangopay
        $mangoPayUser = $this->service->getUser(1);
        $this->assertEquals($mangoPayUser, 'Not found. The ressource does not exist');
    }

    public function testCanGetNaturalUser(): void
    {
        /** @var User $user1 */
        $user1 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'username' => self::USER_SUPER_ADMIN,
            ]);

        $this->assertNotEmpty($user1->getMangoPayUserId());

        //try and find a user 1 which doesn't exist on mangopay
        $mangoPayUser = $this->service->getUser($user1->getMangoPayUserId());

        $this->assertEquals($user1->getFirstname(), $mangoPayUser->FirstName);
        $this->assertEquals($user1->getLastname(), $mangoPayUser->LastName);

        $this->assertNotEmpty($mangoPayUser->Address);
    }

    // public function testCanGetLegalUser(): void
    // {
    //     //todo Once we have a Legal user set up there should be a testcase for it
    //     /** @var App/Entity/User $user1 */
    //     $user1 = $this->entityManager->getRepository(User::class)->findOneBy([
    //         'username' => self::USER_SUPER_ADMIN
    //     ]);
    //     $this->assertNotEmpty($user1->getMangoPayUserId());
    //     //try and find a user 1 which doesn't exist on mangopay
    //     $mangoPayUser = $this->service->getUser($user1->getMangoPayUserId());
    //     $this->assertEquals($user1->getFirstname(), $mangoPayUser->FirstName);
    //     $this->assertEquals($user1->getLastname(), $mangoPayUser->LastName);
    //     $this->assertNotEmpty($mangoPayUser->Address);
    // }
}
