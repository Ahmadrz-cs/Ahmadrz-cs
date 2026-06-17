<?php

/**
 * Created by PhpStorm.
 * User: Sayak
 * Date: 17/01/17
 * Time: 17:30
 */

namespace App\Tests\Service\Manager;

use App\Entity\Enum\UserStatus;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserStatusLog;
use App\Repository\UserRepository;
use App\Service\Manager\UserManager;
use App\Test\FixtureTestCase;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;

class UserManagerTest extends FixtureTestCase
{
    private UserManager $service;

    /** @var \Doctrine\ORM\EntityRepository|UserRepository $repository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = static::getContainer()->get(UserManager::class);
        $this->repository = $this->entityManager->getRepository(User::class);
    }

    private $testQueryParams = [
        'offest' => '0',
        'limit' => '1',
        'sort' => '+id,-updatedAt',
        'id' => '1,3,4',
        'status' => '4,5',
        'type' => 'commercial,residential',
        'term' => '1,3,5',
        'biscuits' => 'digestive,shortbread',
        'user' => 'whodat,whatder',
    ];

    /**
     * @psalm-return \Generator<string, array{0: '0'|'1'|0|1|bool}, mixed, void>
     */
    public static function gdprStatusProvider(): \Generator
    {
        yield 'gdprAcceptedStr' => ['1'];
        yield 'gdprRejectedStr' => ['0'];
        yield 'gdprAcceptedInt' => [1];
        yield 'gdprRejectedInt' => [0];
        yield 'gdprAcceptedBool' => [true];
        yield 'gdprRejectedBool' => [false];
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetCriteria(): void
    {
        $expected = ['id'];

        // only return allowed criteria
        $actual = $this->service->getCriteria($this->testQueryParams);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass empty array
        $actual = $this->service->getCriteria([]);
        $this->assertEmpty($actual);
    }

    #[\PHPUnit\Framework\Attributes\Group('collection')]
    #[\PHPUnit\Framework\Attributes\Group('collection-filter')]
    public function testGetAuxiliaryFilters(): void
    {
        $expected = ['status'];

        // only return allowed auxiliary filters
        $actual = $this->service->getAuxiliaryFilters($this->testQueryParams, true);
        $this->assertEmpty(array_diff($expected, array_keys($actual)));

        // pass default status null as admin - may want to improve it to handle empty arrays
        $actual = $this->service->getAuxiliaryFilters(['status' => null], true);
        $this->assertEmpty($actual);
    }

    public function testBuildUser_simple(): void
    {
        $data = '{
        "given_name":"one","family_name":"one"
        }';

        $data = json_decode($data);

        $user = new User();
        $user_new = $this->service->buildUser($data, $user);

        $this->assertNotNull($user_new);
        $this->assertEquals('one', $user_new->getFirstname());
        $this->assertEquals('one', $user_new->getLastname());
    }

    public function testBuildUser_Yielders(): void
    {
        $data = '{"given_name":"one","family_name":"one",
        "phone_1":"+44 2084414789","phone_2":"+447594663214",
        "nationality":"United Kingdom",
        "gender":"Male","birth_date":"1923-01-01",
        "email":"test@userbuild.com",
        "info":{
        "salesforce_id":12345,
        "referral": "Offer",
        "income_range":1,
        "cxb_worth_investor":false,"cxb_sophisticated_investor":false,
        "cxb_restricted_investor":false,"cxb_ltd_company_investor":false,
        "corporate_investor":false,
        "company_beneficial_owners":"[{\"first_name\":\"\",\"last_name\":\"\"}]",
        "company_directors":"[{\"first_name\":\"\",\"last_name\":\"\"}]"},
        "address":{"building":"building1","street_address":"street1","country":"United Kingdom",
        "city":"london","postal_code":"POST CODE"}}';

        $data = json_decode($data);

        $user = new User();
        $user_new = $this->service->buildUser($data, $user);

        $this->assertNotNull($user_new);
        $this->assertEquals('one', $user_new->getFirstname());
        $this->assertEquals('one', $user_new->getLastname());
        $this->assertEquals('+44 2084414789', $user_new->getPhone1());
        $this->assertEquals('+447594663214', $user_new->getPhone2());
        $this->assertEquals('GB', $user_new->getNationality());
        $this->assertEquals('Male', $user_new->getGender());
        $this->assertEquals(new \DateTime('1923-01-01'), $user_new->getBirthDate());
        $this->assertEquals('test@userbuild.com', $user_new->getEmail());
        $this->assertEquals(1, $user_new->getIncomeRange());
        $this->assertFalse($user->getInvestor()->getCxbWorthInvestor());
        $this->assertFalse($user->getInvestor()->getCxbSophisticatedInvestor());
        $this->assertFalse($user->getInvestor()->getCxbRestrictedUser());
        $this->assertFalse($user->getInvestor()->getCxbLtdCompInvestor());
        $this->assertEquals(
            '[{"first_name":"","last_name":""}]',
            $user->getCompany()->getBeneficialOwners(),
        );
        $this->assertEquals(
            '[{"first_name":"","last_name":""}]',
            $user->getCompany()->getDirectors(),
        );
        $this->assertEquals('building1', $user->getMainAddress()->getAddress1());
        $this->assertEquals('street1', $user->getMainAddress()->getAddress2());
        $this->assertEquals('london', $user->getMainAddress()->getCity());
        $this->assertEquals('GB', $user->getMainAddress()->getCountry());
        $this->assertEquals('POST CODE', $user->getMainAddress()->getPostCode());

        $this->assertEquals('Offer', $user->getReferralCode());

        //assert that the users custom fields has a entry for the salesforce_id
        /** @var UserCustomFields $custfield */
        $custfield = $user->findCustomFieldValue('salesforce_id');
        $this->assertNotNull($custfield);
        $this->assertEquals('12345', $custfield);
    }

    // /**
    //  * @group email
    //  */
    // public function testtestUserApproveToMangoPay(): void
    // {
    //     $userRepo = $this->getRepository(User::class);

    //     /** @var UserManager $usermgr */
    //     $usermgr = static::getContainer()->get(\App\Service\Manager\UserManager2::class);

    //     /** @var User $user */
    //     $singleUser = $userRepo->findOneBy(['username' => 'Userfake@test.com']);

    //     $this->assertNotNull($singleUser->getId());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->verifyEmail($singleUser);

    //     // @var User $investor_check
    //     $user_check = $userRepo->findOneBy(['username' => 'Userfake@test.com']);

    //     $this->assertEquals(UserLifecycle::STATE_EMAIL_VERIFIED, $user_check->getLifecycleStatus());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->completeRegistration($singleUser);

    //     // @var User $investor_check
    //     $singleUser = $userRepo->findOneBy(['username' => 'Userfake@test.com']);
    //     $this->assertEquals(UserLifecycle::STATE_REGISTRATION_COMPLETE, $singleUser->getLifecycleStatus());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->approveUser($singleUser);

    //     // @var User $investor_check
    //     $singleUser = $userRepo->findOneBy(['username' => 'Userfake@test.com']);
    //     $this->assertEquals(UserLifecycle::STATE_APPROVED, $singleUser->getLifecycleStatus());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->mangoPayUserFromApprove($singleUser);

    //     // @var User $investor_check
    //     $singleUser = $userRepo->findOneBy(['username' => 'Userfake@test.com']);

    //     $this->assertEquals(UserLifecycle::STATE_MANGOPAY_REGISTERED, $user_check->getLifecycleStatus());
    // }

    // /**
    //  * @group email
    //  */
    // public function testUserApproveToMangoPay()
    // {
    //      //set the investor to blocked
    //     $userRepo = $this->getRepository(User::class);

    //     $singleUser = $this->getValidUser();

    //     $this->assertNotNull($singleUser->getId());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->verifyEmail($singleUser);

    //     // @var User $investor_check
    //     $user_check = $user_rep->find($singleUser->getId());

    //     $this->assertEquals(UserLifecycle::STATE_EMAIL_VERIFIED, $user_check->getLifecycleStatus());
    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->completeRegistration($singleUser);

    //     // @var User $investor_check
    //     $user_check = $user_rep->find($singleUser->getId());

    //     $this->assertEquals(UserLifecycle::STATE_REGISTRATION_COMPLETE, $user_check->getLifecycleStatus());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->approveUser($singleUser);

    //     // @var User $investor_check
    //     $user_check = $user_rep->find($singleUser->getId());

    //     $this->assertEquals(UserLifecycle::STATE_APPROVED, $user_check->getLifecycleStatus());

    //     $result = static::getContainer()->get(\App\Service\Manager\UserManager2::class)->mangoPayUserFromApprove($singleUser);

    //     // @var User $investor_check
    //     $user_check = $user_rep->find($singleUser->getId());

    //     $this->assertEquals(UserLifecycle::STATE_MANGOPAY_REGISTERED, $user_check->getLifecycleStatus());
    // }

    public function testUserEmailVerify(): void
    {
        $user = $this->repository->findOneBy([
            'username' => self::USER_EMAIL_UNVERIFIED,
        ]);
        $this->service->verifyEmail($user);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->assertEquals(OB_STEP_CONSTANT::STEP2_INT, $user->getOBStep());
        $this->assertEquals(UserStatus::Active, $user->getCurrentStatus());

        // Check users who are already beyond this stage in onboarding are not reverted
        $user->setLifecycleStatus(UserLifecycle::STATE_APPROVED);
        $user->setOBStep(OB_STEP_CONSTANT::STEP5_INT);
        $this->service->verifyEmail($user);
        $this->assertEquals(UserLifecycle::STATE_APPROVED, $user->getLifecycleStatus());
        $this->assertEquals(OB_STEP_CONSTANT::STEP5_INT, $user->getOBStep());
        $this->assertEquals(UserStatus::Active, $user->getCurrentStatus());

        // Check blocked users are not reverted
        $user->setLifecycleStatus(UserLifecycle::STATE_BLOCKED);
        $user->addStatusLog(new UserStatusLog(status: UserStatus::Closed));
        $this->service->verifyEmail($user);
        $this->assertEquals(UserLifecycle::STATE_BLOCKED, $user->getLifecycleStatus());
        $this->assertEquals(OB_STEP_CONSTANT::STEP5_INT, $user->getOBStep());
        $this->assertEquals(UserStatus::Closed, $user->getCurrentStatus());
    }

    public function testUserCompleteRegistration(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['username' => self::USER_EMAIL_VERIFIED]);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->service->completeRegistration($user);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
    }

    public function testUserCompleteRegistrationReapply(): void
    {
        $this->expectException(NotEnabledTransitionException::class);

        /** @var User $user */
        $user = $this->repository->findOneBy(['username' => self::USER_EMAIL_VERIFIED]);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->service->completeRegistration($user);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
        $this->service->completeRegistration($user);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
    }

    public function testUserRegistrationToBlock(): void
    {
        /** @var User $user */
        $user = $this->repository->findOneBy(['username' => self::USER_EMAIL_VERIFIED]);
        $this->assertEquals(
            UserLifecycle::STATE_EMAIL_VERIFIED,
            $user->getLifecycleStatus(),
        );
        $this->service->completeRegistration($user);
        $this->assertEquals(
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            $user->getLifecycleStatus(),
        );
        $this->service->blockUserFromRegistration($user);
        $this->assertEquals(UserLifecycle::STATE_BLOCKED, $user->getLifecycleStatus());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('gdprStatusProvider')]
    public function testBuildUserFieldGdprAccepted($gdpr): void
    {
        $data = new \stdClass();
        $data->gdpr_accepted = $gdpr;

        $user = $this->service->buildUser($data);
        $this->assertNotNull($user);
        $this->assertEquals($gdpr, $user->isGDPRAccepted());
    }
}
